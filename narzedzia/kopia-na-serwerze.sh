#!/bin/bash
# Kopia zapasowa strony na serwerze: baza danych, plik .env i wgrane zdjęcia (storage/app/public).
# Uruchamia go instaluj-na-serwerze.sh (cron co kilka minut):
#   - raz na dobę po 3:00 w nocy:            kopia-na-serwerze.sh
#   - przed instalacją nowej paczki:          kopia-na-serwerze.sh --przed-instalacja (tylko baza i .env)
# Nowa kopia powstaje tylko wtedy, gdy coś się zmieniło od poprzedniej (porównanie sum kontrolnych).
# Kopie leżą w folderze kopie-joker OBOK folderu strony (np. domains/DOMENA/kopie-joker), żeby przetrwały
# podmianę albo zepsucie folderu strony. Nie jest on dostępny z internetu (public_html wskazuje na joker/public).
# Przebieg zapisuje w kopie-joker/kopie.log.
#
# Przywracanie bazy: phpMyAdmin -> baza strony -> Import -> plik baza-*.sql.gz.
# Przywracanie zdjęć: rozpakuj zdjecia-*.tar w folderze joker/storage/app (powstanie folder public).

cd "$(dirname "$0")/.." || exit 1
APP="$(pwd)"
KOPIE="${KOPIE_DIR:-$(dirname "$APP")/kopie-joker}"
TRZYMAJ_BAZY="${TRZYMAJ_BAZY:-14}"
TRZYMAJ_ZDJECIA="${TRZYMAJ_ZDJECIA:-3}"
TRYB="${1:-nocna}"

mkdir -p "$KOPIE" || exit 1
chmod 700 "$KOPIE" 2>/dev/null
LOG="$KOPIE/kopie.log"
TERAZ="$(date '+%Y-%m-%d_%H%M%S')"
log() { echo "$(date '+%Y-%m-%d %H:%M:%S') $*" >>"$LOG"; }

# Jedna kopia naraz: pakowanie zdjęć może trwać dłużej niż odstęp między uruchomieniami crona.
BLOKADA="$KOPIE/.trwa-kopia"
if ! mkdir "$BLOKADA" 2>/dev/null; then
    # Blokada starsza niż 2 godziny to pozostałość po przerwanej kopii.
    if [ -n "$(find "$BLOKADA" -maxdepth 0 -mmin +120 2>/dev/null)" ]; then
        rmdir "$BLOKADA" 2>/dev/null
        mkdir "$BLOKADA" 2>/dev/null || exit 0
    else
        exit 0
    fi
fi
trap 'rm -f "$KOPIE"/.tmp-* 2>/dev/null; rmdir "$BLOKADA" 2>/dev/null' EXIT

if [ ! -f .env ]; then
    log "Pominięto kopię: brak pliku .env w $APP."
    exit 1
fi

env_wartosc() {
    local linia
    linia="$(grep -E "^$1=" .env | tail -n 1)"
    linia="${linia#*=}"
    linia="${linia%$'\r'}"
    if [ "${linia:0:1}" = '"' ] && [ "${linia: -1}" = '"' ]; then
        linia="${linia:1:${#linia}-2}"
        linia="${linia//\\\"/\"}"
    fi
    if [ "${linia:0:1}" = "'" ] && [ "${linia: -1}" = "'" ]; then linia="${linia:1:${#linia}-2}"; fi
    printf '%s' "$linia"
}

suma() { sha256sum "$1" 2>/dev/null | cut -d' ' -f1; }

# Zapisuje plik jako nową kopię tylko wtedy, gdy różni się od ostatniej kopii tego rodzaju.
# $1 plik tymczasowy, $2 rodzaj (baza/env/zdjecia), $3 rozszerzenie, $4 suma do porównania (domyślnie z pliku)
zapisz_jesli_zmiana() {
    local tymczasowy="$1" rodzaj="$2" rozszerzenie="$3" nowa="${4:-$(suma "$1")}"
    local plik_sumy="$KOPIE/.suma-$rodzaj"
    if [ -f "$plik_sumy" ] && [ "$(cat "$plik_sumy")" = "$nowa" ] && ls "$KOPIE/$rodzaj-"* >/dev/null 2>&1; then
        rm -f "$tymczasowy"
        log "$rodzaj: bez zmian od ostatniej kopii."
        return 1
    fi
    mv "$tymczasowy" "$KOPIE/$rodzaj-$TERAZ.$rozszerzenie"
    chmod 600 "$KOPIE/$rodzaj-$TERAZ.$rozszerzenie"
    echo "$nowa" >"$plik_sumy"
    log "$rodzaj: zapisano $rodzaj-$TERAZ.$rozszerzenie ($(du -h "$KOPIE/$rodzaj-$TERAZ.$rozszerzenie" | cut -f1))."
    return 0
}

# Usuwa najstarsze kopie danego rodzaju, zostawia $2 najnowszych.
sprzataj() {
    ls -1t "$KOPIE/$1-"* 2>/dev/null | tail -n +"$(($2 + 1))" | while read -r stary; do
        rm -f "$stary" && log "$1: usunięto starą kopię $(basename "$stary")."
    done
}

BLAD=0

# --- .env (hasła do bazy i poczty) ---
cp .env "$KOPIE/.tmp-env"
zapisz_jesli_zmiana "$KOPIE/.tmp-env" env txt
sprzataj env "$TRZYMAJ_BAZY"

# --- baza danych ---
DB_RODZAJ="$(env_wartosc DB_CONNECTION)"
if [ "$DB_RODZAJ" = "sqlite" ]; then
    PLIK_BAZY="$(env_wartosc DB_DATABASE)"
    [ -n "$PLIK_BAZY" ] || PLIK_BAZY="database/database.sqlite"
    if [ -f "$PLIK_BAZY" ]; then
        gzip -c -n "$PLIK_BAZY" >"$KOPIE/.tmp-baza"
        zapisz_jesli_zmiana "$KOPIE/.tmp-baza" baza sqlite.gz "$(suma "$PLIK_BAZY")"
    else
        log "BŁĄD: nie znalazłem pliku bazy SQLite $PLIK_BAZY."
        BLAD=1
    fi
else
    ZRZUT=""
    for P in mysqldump mariadb-dump /usr/bin/mysqldump /usr/local/bin/mysqldump /usr/local/mysql/bin/mysqldump; do
        if command -v "$P" >/dev/null 2>&1; then ZRZUT="$P"; break; fi
    done
    if [ -z "$ZRZUT" ]; then
        log "BŁĄD: na serwerze nie ma programu mysqldump - kopia bazy pominięta."
        BLAD=1
    else
        DB_NAZWA="$(env_wartosc DB_DATABASE)"
        # Hasło w pliku z prawami tylko dla właściciela, a nie w poleceniu (widocznym na liście procesów).
        DANE_LOGOWANIA="$KOPIE/.tmp-mysql.cnf"
        (umask 077; {
            echo "[client]"
            H="$(env_wartosc DB_HOST)"; echo "host=${H:-localhost}"
            P="$(env_wartosc DB_PORT)"; echo "port=${P:-3306}"
            echo "user=$(env_wartosc DB_USERNAME)"
            echo "password=\"$(env_wartosc DB_PASSWORD | sed 's/\\/\\\\/g; s/"/\\"/g')\""
        } >"$DANE_LOGOWANIA")
        # Sesje i pamięć podręczna zmieniają się co chwilę i nie są potrzebne do odtworzenia strony:
        # z tych tabel tylko struktura, bez danych. Bez daty w zrzucie, żeby te same dane dawały tę samą sumę.
        POMIJANE=(sessions cache cache_locks)
        POMIN=()
        for T in "${POMIJANE[@]}"; do POMIN+=("--ignore-table=$DB_NAZWA.$T"); done
        OPCJE=(--defaults-extra-file="$DANE_LOGOWANIA" --single-transaction --skip-lock-tables --skip-dump-date
            --default-character-set=utf8mb4 --no-tablespaces)
        if { "$ZRZUT" "${OPCJE[@]}" "${POMIN[@]}" "$DB_NAZWA" \
                && "$ZRZUT" "${OPCJE[@]}" --no-data "$DB_NAZWA" "${POMIJANE[@]}"; } \
                >"$KOPIE/.tmp-baza.sql" 2>"$KOPIE/.tmp-bledy"; then
            SUMA_BAZY="$(suma "$KOPIE/.tmp-baza.sql")"
            gzip -n -c "$KOPIE/.tmp-baza.sql" >"$KOPIE/.tmp-baza"
            rm -f "$KOPIE/.tmp-baza.sql"
            zapisz_jesli_zmiana "$KOPIE/.tmp-baza" baza sql.gz "$SUMA_BAZY"
        else
            log "BŁĄD zrzutu bazy $DB_NAZWA: $(tr '\n' ' ' <"$KOPIE/.tmp-bledy" | cut -c1-500)"
            BLAD=1
        fi
        rm -f "$KOPIE/.tmp-baza.sql" "$KOPIE/.tmp-bledy" "$DANE_LOGOWANIA"
    fi
fi
sprzataj baza "$TRZYMAJ_BAZY"

# --- zdjęcia i dokumenty (tylko w kopii nocnej; przed instalacją paczki zdjęcia się nie zmieniają) ---
if [ "$TRYB" != "--przed-instalacja" ] && [ -d storage/app/public ]; then
    # Spis plików z rozmiarem i datą zmiany: zmienia się, gdy ktoś doda, podmieni albo usunie zdjęcie.
    SUMA_ZDJEC="$(cd storage/app && find public -type f -printf '%p %s %T@\n' | LC_ALL=C sort | sha256sum | cut -d' ' -f1)"
    if [ -f "$KOPIE/.suma-zdjecia" ] && [ "$(cat "$KOPIE/.suma-zdjecia")" = "$SUMA_ZDJEC" ] && ls "$KOPIE/zdjecia-"* >/dev/null 2>&1; then
        log "zdjecia: bez zmian od ostatniej kopii."
    elif tar -cf "$KOPIE/.tmp-zdjecia" -C storage/app public 2>"$KOPIE/.tmp-bledy"; then
        zapisz_jesli_zmiana "$KOPIE/.tmp-zdjecia" zdjecia tar "$SUMA_ZDJEC"
    else
        log "BŁĄD pakowania zdjęć: $(tr '\n' ' ' <"$KOPIE/.tmp-bledy" | cut -c1-500)"
        BLAD=1
    fi
    rm -f "$KOPIE/.tmp-zdjecia" "$KOPIE/.tmp-bledy"
    sprzataj zdjecia "$TRZYMAJ_ZDJECIA"
fi

# Nocna kopia odhaczona także po błędzie, żeby cron nie powtarzał jej co kilka minut do rana (błąd jest w kopie.log).
if [ "$TRYB" != "--przed-instalacja" ]; then
    date '+%Y-%m-%d' >"$KOPIE/.ostatnia-nocna"
fi
exit "$BLAD"

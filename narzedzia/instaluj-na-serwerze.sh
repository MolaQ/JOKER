#!/bin/bash
# Instalacja i aktualizacja strony na hostingu bez SSH (cyber_Folks, DirectAdmin).
# Uruchamia go jedno zadanie cron w panelu, co 5 minut (*/5 * * * *):
#   bash /home/UZYTKOWNIK/domains/DOMENA/joker/narzedzia/instaluj-na-serwerze.sh
# Instaluje tylko wtedy, gdy wgrano nową paczkę albo zmieniono .env (nowsze niż ostatnia instalacja);
# w pozostałych uruchomieniach wykonuje tylko zadania okresowe Laravela (schedule:run).
# Przebieg instalacji zapisuje w storage/logs/instalacja.log (podgląd w menedżerze plików).

# Aktualizacja z panelu podmienia też ten skrypt, a bash czyta plik w trakcie pracy: gdy czeka flaga,
# skrypt uruchamia się z własnej kopii (JOKER_APP wskazuje wtedy folder strony) i na koniec ją usuwa.
if [ -n "$JOKER_APP" ]; then
    cd "$JOKER_APP" || exit 1
    trap 'rm -f "$0"' EXIT
else
    cd "$(dirname "$0")/.." || exit 1
fi
APP="$(pwd)"
AKT="$APP/storage/app/aktualizacja"
if [ -z "$JOKER_APP" ] && [ -f "$AKT/flaga.json" ]; then
    KOPIA_SKRYPTU="$APP/storage/framework/instalator-$$.sh"
    if cp "$0" "$KOPIA_SKRYPTU" 2>/dev/null; then
        JOKER_APP="$APP" exec bash "$KOPIA_SKRYPTU"
    fi
fi
DOMENA="$(dirname "$APP")"
ZNACZNIK="$APP/storage/framework/instalacja-zrobiona"
# Numer paczki zapisany przez paczka-na-serwer.bat: inny niż przy ostatniej instalacji = nowa paczka,
# nawet gdy menedżer plików zostawi przy rozpakowaniu stare daty plików.
WERSJA="$APP/bootstrap/wersja-paczki.txt"
LOG="$APP/storage/logs/instalacja.log"

# Paczka rozpakowana o poziom za głęboko (np. joker/joker, joker/oker): przenosi pliki na właściwe miejsce.
for ZAGLEBIONY in "$APP"/*/; do
    ZAGLEBIONY="${ZAGLEBIONY%/}"
    [ -f "$ZAGLEBIONY/artisan" ] && [ -d "$ZAGLEBIONY/vendor" ] || continue
    cp -a "$ZAGLEBIONY/." "$APP/" && rm -rf "$ZAGLEBIONY"
    mkdir -p "$APP/storage/logs"
    echo "$(date '+%Y-%m-%d %H:%M:%S') Paczka była rozpakowana w $ZAGLEBIONY - przeniesiono pliki do $APP." >>"$APP/storage/logs/instalacja.log"
done

# Paczka rozpakowana z uprawnieniami 777 (zip z Windows) daje błąd 403/500: serwer nie uruchamia plików,
# które może zmieniać każdy. Foldery 755, pliki 644 (skrypty .sh zostają wykonywalne).
if [ -n "$(find "$APP" -maxdepth 2 -perm -o+w ! -type l -print -quit 2>/dev/null)" ]; then
    find "$APP" ! -type l -type d -exec chmod 755 {} + 2>/dev/null
    find "$APP" ! -type l -type f -exec chmod 644 {} + 2>/dev/null
    find "$APP/narzedzia" -name '*.sh' -exec chmod 755 {} + 2>/dev/null
    echo "$(date '+%Y-%m-%d %H:%M:%S') Poprawiono uprawnienia plików (foldery 755, pliki 644)." >>"$APP/storage/logs/instalacja.log"
fi

mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views storage/app/public bootstrap/cache

# PHP 8.2 lub nowszy: domyślne polecenie php bywa starsze niż wersja wybrana dla strony w panelu.
PHPBIN=""
for P in "$PHP" /usr/local/bin/php84 /usr/local/bin/php83 /usr/local/bin/php82 /usr/local/bin/php /opt/alt/php84/usr/bin/php /opt/alt/php83/usr/bin/php /opt/alt/php82/usr/bin/php \
         /usr/local/php84/bin/php /usr/local/php83/bin/php /usr/local/php82/bin/php php; do
    [ -n "$P" ] && command -v "$P" >/dev/null 2>&1 || continue
    if "$P" -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' 2>/dev/null; then
        PHPBIN="$P"
        break
    fi
done
if [ -z "$PHPBIN" ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') BŁĄD: nie znalazłem PHP 8.2 lub nowszego. Ustaw wersję PHP w panelu i wklej ten log Claude." >>"$LOG"
    exit 1
fi

# Aktualizacja wgrana przez panel (System → Aktualizacja strony). Rusza tylko z plikiem flagą, który administrator
# zatwierdził hasłem; flaga niesie sumę kontrolną paczki, więc niepełna albo podmieniona paczka nie zostanie rozpakowana.
stan_aktualizacji() {
    printf '{"stan":"%s","opis":"%s","czas":"%s"}\n' "$1" "$2" "$(date '+%Y-%m-%d %H:%M:%S')" >"$AKT/stan.json"
}
if [ -f "$AKT/flaga.json" ]; then
    # Blokada po przerwanej instalacji (starsza niż 30 minut) nie może zatrzymać aktualizacji na zawsze.
    if [ -d "$AKT/blokada" ] && [ -n "$(find "$AKT/blokada" -maxdepth 0 -mmin +30 2>/dev/null)" ]; then
        rmdir "$AKT/blokada" 2>/dev/null
    fi
    if ! mkdir "$AKT/blokada" 2>/dev/null; then
        exit 0
    fi
    {
        echo
        echo "=== $(date '+%Y-%m-%d %H:%M:%S') aktualizacja z panelu ==="
    } >>"$LOG"
    KOPIA_KODU="$DOMENA/kopie-joker/kod-poprzedni.tar.gz"
    rm -rf "$AKT/nowa"
    if grep -q '"typ":"cofnij"' "$AKT/flaga.json"; then
        if [ -f "$KOPIA_KODU" ]; then
            "$PHPBIN" artisan down --render="errors::503" --retry=30 --refresh=30 >>"$LOG" 2>&1
            if tar -xzf "$KOPIA_KODU" -C "$APP" >>"$LOG" 2>&1; then
                echo "Przywrócono poprzednią wersję kodu z $KOPIA_KODU." >>"$LOG"
                rm -f "$ZNACZNIK"
                stan_aktualizacji instalacja "Przywrócono poprzednią wersję, trwa instalacja"
            else
                echo "BŁĄD: nie udało się rozpakować kopii poprzedniej wersji." >>"$LOG"
                stan_aktualizacji blad "Nie udało się przywrócić poprzedniej wersji"
            fi
        else
            echo "BŁĄD: brak kopii poprzedniej wersji ($KOPIA_KODU)." >>"$LOG"
            stan_aktualizacji blad "Brak kopii poprzedniej wersji"
        fi
    elif "$PHPBIN" narzedzia/rozpakuj-paczke.php "$AKT" >>"$LOG" 2>&1 \
        && [ -f "$AKT/nowa/artisan" ] && [ -f "$AKT/nowa/public/index.php" ] && [ -f "$AKT/nowa/vendor/autoload.php" ]; then
        # Poprzednia wersja kodu zostaje jako jedna kopia do cofnięcia (bez zdjęć, logów i .env).
        mkdir -p "$DOMENA/kopie-joker"
        if tar -czf "$KOPIA_KODU.nowa" -C "$APP" --exclude=./storage --exclude=./node_modules --exclude=./public/storage --exclude=./.env . 2>>"$LOG"; then
            mv -f "$KOPIA_KODU.nowa" "$KOPIA_KODU"
        else
            rm -f "$KOPIA_KODU.nowa"
            echo "UWAGA: nie udało się zachować poprzedniej wersji kodu (brak miejsca?). Aktualizacja idzie dalej." >>"$LOG"
        fi
        "$PHPBIN" artisan down --render="errors::503" --retry=30 --refresh=30 >>"$LOG" 2>&1
        if cp -a "$AKT/nowa/." "$APP/" 2>>"$LOG"; then
            echo "Pliki nowej paczki są na miejscu." >>"$LOG"
            rm -f "$ZNACZNIK"
            stan_aktualizacji instalacja "Pliki podmienione, trwa instalacja"
            rm -f "$AKT/joker-kod.zip" "$AKT/paczka.json"
        else
            echo "BŁĄD: kopiowanie plików nowej paczki przerwane." >>"$LOG"
            stan_aktualizacji blad "Kopiowanie plików przerwane, szczegóły w logu"
        fi
    else
        echo "Aktualizacja z panelu odrzucona: nic nie zostało zmienione." >>"$LOG"
        stan_aktualizacji blad "Paczka odrzucona, szczegóły w logu"
    fi
    rm -rf "$AKT/nowa"
    rm -f "$AKT/flaga.json"
    rmdir "$AKT/blokada" 2>/dev/null
fi

# Kopia zapasowa (baza, .env, zdjęcia) raz na dobę po 3:00, tylko gdy coś się zmieniło - szczegóły w kopia-na-serwerze.sh.
if [ -f .env ] && [ -f narzedzia/kopia-na-serwerze.sh ] && [ "$(date +%H)" -ge 3 ] \
    && [ "$(cat "$DOMENA/kopie-joker/.ostatnia-nocna" 2>/dev/null)" != "$(date +%Y-%m-%d)" ]; then
    bash narzedzia/kopia-na-serwerze.sh
fi

# Strona zainstalowana i nic nowego nie wgrano: tylko zadania okresowe (terminarze co 3 godziny, sprzątanie statystyk).
if [ -f "$ZNACZNIK" ] && [ "$ZNACZNIK" -nt vendor/autoload.php ] && [ "$ZNACZNIK" -nt .env ] \
    && { [ ! -f "$WERSJA" ] || cmp -s "$WERSJA" "$ZNACZNIK"; }; then
    "$PHPBIN" artisan schedule:run --no-interaction >/dev/null 2>&1
    exit 0
fi

exec >>"$LOG" 2>&1
echo
echo "=== $(date '+%Y-%m-%d %H:%M:%S') instalacja w $APP ==="

echo "PHP: $PHPBIN ($("$PHPBIN" -r 'echo PHP_VERSION;'))"

if [ ! -f .env ]; then
    echo "BŁĄD: brak pliku .env. Skopiuj narzedzia/env-serwer.txt jako .env w folderze $APP i uzupełnij dane bazy."
    exit 1
fi
if grep -q '^DB_[A-Z]*=UZUPELNIJ' .env; then
    echo "BŁĄD: w pliku .env są jeszcze linie DB_ z UZUPELNIJ. Wpisz dane bazy z panelu."
    exit 1
fi

# Folder strony: public_html wskazuje na folder public aplikacji (stary folder zostaje jako kopia).
# Tylko w folderze domeny DirectAdmin (obok jest public_html); private_html bywa już dowiązaniem do public_html.
for KATALOG in public_html private_html; do
    CEL="$DOMENA/$KATALOG"
    if [ -L "$CEL" ] || [ ! -d "$CEL" ]; then
        continue
    fi
    mv "$CEL" "${CEL}_stary_$(date +%Y%m%d%H%M%S)"
    ln -s "$(basename "$APP")/public" "$CEL"
    echo "$KATALOG wskazuje teraz na $(basename "$APP")/public"
done

# Po rozpakowaniu w menedżerze plików foldery bywają 777; serwer (suPHP/LiteSpeed) odmawia wtedy uruchomienia strony (błąd 403/500).
find "$APP" -path "$APP/storage/app/public" -prune -o -type d ! -perm 755 -exec chmod 755 {} +
find "$APP" -path "$APP/storage/app/public" -prune -o -type f ! -perm 644 -exec chmod 644 {} +

# Pliki z paczki mają datę z 1980 roku: OPcache serwera uznałby zmieniony plik PHP za ten sam
# i dalej uruchamiał starą wersję. Świeża data wymusza wczytanie nowego kodu.
find "$APP" \( -path "$APP/storage" -o -path "$APP/node_modules" \) -prune -o -type f -name '*.php' -exec touch {} +

# Kopia bazy i .env przed migracjami nowej paczki (do folderu kopie-joker obok strony).
if [ -f narzedzia/kopia-na-serwerze.sh ]; then
    echo "--- kopia zapasowa bazy przed instalacją"
    bash narzedzia/kopia-na-serwerze.sh --przed-instalacja && echo "zrobiona (szczegóły w $DOMENA/kopie-joker/kopie.log)" \
        || echo "UWAGA: kopia nie wyszła - szczegóły w $DOMENA/kopie-joker/kopie.log. Instalacja idzie dalej."
fi

BLAD=0
krok() {
    echo "--- $*"
    "$PHPBIN" artisan "$@" --no-interaction || { echo "BŁĄD w kroku: $*"; BLAD=1; }
}

grep -q '^APP_KEY=base64:' .env || krok key:generate --force
# Najpierw tylko zapamiętana konfiguracja (dane bazy z nowego .env), pełne czyszczenie po migracjach,
# bo potrzebuje tabeli cache w bazie.
krok config:clear
krok migrate --force
krok optimize:clear
[ -L public/storage ] || krok storage:link
krok joker:optimize-photos
krok optimize

# Po aktualizacji z panelu strona stoi na planszy „Przerwa techniczna”: zdejmujemy ją niezależnie od wyniku.
if [ -f storage/framework/down ]; then
    "$PHPBIN" artisan up --no-interaction
fi
if [ -f "$AKT/stan.json" ] && grep -q '"stan":"instalacja"' "$AKT/stan.json"; then
    if [ "$BLAD" -eq 0 ]; then
        stan_aktualizacji gotowe "Aktualizacja zainstalowana"
    else
        stan_aktualizacji blad "Instalacja zakończona z błędami, szczegóły w logu"
    fi
fi

if [ "$BLAD" -eq 0 ]; then
    if [ -f "$WERSJA" ]; then cp "$WERSJA" "$ZNACZNIK"; fi
    touch "$ZNACZNIK"
    echo "Paczka: $(cat "$WERSJA" 2>/dev/null || echo bez numeru)"
    echo "=== GOTOWE ==="
else
    echo "=== Były błędy (powyżej). Wklej ten log Claude. Kolejna próba przy następnym uruchomieniu crona. ==="
fi

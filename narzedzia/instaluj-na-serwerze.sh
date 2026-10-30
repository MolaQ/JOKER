#!/bin/bash
# Instalacja i aktualizacja strony na hostingu bez SSH (cyber_Folks, DirectAdmin).
# Uruchamia go jedno zadanie cron w panelu, co 5 minut (*/5 * * * *):
#   bash /home/UZYTKOWNIK/domains/DOMENA/joker/narzedzia/instaluj-na-serwerze.sh
# Instaluje tylko wtedy, gdy wgrano nową paczkę albo zmieniono .env (nowsze niż ostatnia instalacja);
# w pozostałych uruchomieniach wykonuje tylko zadania okresowe Laravela (schedule:run).
# Przebieg instalacji zapisuje w storage/logs/instalacja.log (podgląd w menedżerze plików).

cd "$(dirname "$0")/.." || exit 1
APP="$(pwd)"
DOMENA="$(dirname "$APP")"
ZNACZNIK="$APP/storage/framework/instalacja-zrobiona"
# Numer paczki zapisany przez paczka-na-serwer.bat: inny niż przy ostatniej instalacji = nowa paczka,
# nawet gdy menedżer plików zostawi przy rozpakowaniu stare daty plików.
WERSJA="$APP/bootstrap/wersja-paczki.txt"
LOG="$APP/storage/logs/instalacja.log"

# Paczka rozpakowana o poziom za głęboko (joker/joker): przenosi pliki na właściwe miejsce.
if [ -f "$APP/joker/artisan" ]; then
    cp -a "$APP/joker/." "$APP/" && rm -rf "$APP/joker"
    mkdir -p "$APP/storage/logs"
    echo "$(date '+%Y-%m-%d %H:%M:%S') Paczka była rozpakowana w joker/joker - przeniesiono pliki do $APP." >>"$APP/storage/logs/instalacja.log"
fi

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

if [ "$BLAD" -eq 0 ]; then
    if [ -f "$WERSJA" ]; then cp "$WERSJA" "$ZNACZNIK"; fi
    touch "$ZNACZNIK"
    echo "Paczka: $(cat "$WERSJA" 2>/dev/null || echo bez numeru)"
    echo "=== GOTOWE ==="
else
    echo "=== Były błędy (powyżej). Wklej ten log Claude. Kolejna próba przy następnym uruchomieniu crona. ==="
fi

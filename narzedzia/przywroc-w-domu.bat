@echo off
rem Uruchomienie kopii strony na innym komputerze z XAMPP: wczytuje baze z _baza\joker.sql
rem i odtwarza dowiazanie public\storage. Uruchom dwuklikiem z folderu narzedzia skopiowanej strony.
rem Wczesniej wlacz Apache i MySQL w panelu XAMPP.
chcp 65001 >nul
setlocal EnableDelayedExpansion

set "STRONA=%~dp0.."
set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
set "PHP=C:\xampp\php\php.exe"

cd /d "%STRONA%"
if not exist "artisan" (
    echo Uruchom ten plik z folderu narzedzia wewnatrz skopiowanej strony.
    pause
    exit /b 1
)

for /f "usebackq eol=# tokens=1,* delims==" %%A in (".env") do (
    if "%%A"=="DB_DATABASE" set "DB_NAZWA=%%~B"
    if "%%A"=="DB_USERNAME" set "DB_UZYTKOWNIK=%%~B"
    if "%%A"=="DB_PASSWORD" set "DB_HASLO=%%~B"
)
if not defined DB_UZYTKOWNIK set "DB_UZYTKOWNIK=root"
set "HASLO="
if defined DB_HASLO set "HASLO=-p!DB_HASLO!"

echo [1/3] Tworze baze !DB_NAZWA! i wczytuje dane ...
"%MYSQL%" -u "!DB_UZYTKOWNIK!" !HASLO! -e "CREATE DATABASE IF NOT EXISTS `!DB_NAZWA!` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
"%MYSQL%" -u "!DB_UZYTKOWNIK!" !HASLO! --default-character-set=utf8mb4 "!DB_NAZWA!" < "_baza\joker.sql"
if errorlevel 1 (
    echo Wczytanie bazy nie powiodlo sie. Czy MySQL w XAMPP jest uruchomiony?
    pause
    exit /b 1
)

echo [2/3] Odtwarzam dowiazanie public\storage ...
"%PHP%" artisan storage:link

echo [3/3] Czyszcze pamiec podreczna ...
"%PHP%" artisan optimize:clear

echo.
echo Gotowe. Jesli strona jest pod innym adresem niz w pracy, zmien APP_URL w pliku .env.
pause

@echo off
rem Uruchomienie kopii strony na innym komputerze z XAMPP: wczytuje baze z _baza\joker.sql
rem i odtwarza dowiazanie public\storage. Uruchom dwuklikiem z folderu narzedzia skopiowanej strony.
rem Wczesniej wlacz Apache i MySQL w panelu XAMPP.
chcp 65001 >nul
setlocal EnableDelayedExpansion

set "STRONA=%~dp0.."

cd /d "%STRONA%"
if not exist "artisan" (
    echo Uruchom ten plik z folderu narzedzia wewnatrz skopiowanej strony.
    pause
    exit /b 1
)

for /f "usebackq eol=# tokens=1,* delims==" %%A in (".env") do (
    if "%%A"=="DB_CONNECTION" set "DB_RODZAJ=%%~B"
    if "%%A"=="DB_DATABASE" set "DB_NAZWA=%%~B"
    if "%%A"=="DB_USERNAME" set "DB_UZYTKOWNIK=%%~B"
    if "%%A"=="DB_PASSWORD" set "DB_HASLO=%%~B"
    if "%%A"=="DB_PORT" set "DB_PORT=%%~B"
)

rem XAMPP: najpierw na dysku ze strona, potem C:\xampp, potem PATH.
set "XAMPP="
for %%P in ("%CD:~0,2%\xampp" "C:\xampp" "D:\xampp") do if not defined XAMPP if exist "%%~P\php\php.exe" set "XAMPP=%%~P"
if not defined XAMPP (
    echo Nie znalazlem XAMPP. Wpisz jego folder w linii set "XAMPP=..." w tym pliku.
    pause
    exit /b 1
)
set "PHP=%XAMPP%\php\php.exe"
set "MYSQL=%XAMPP%\mysql\bin\mysql.exe"

if /i "!DB_RODZAJ!"=="sqlite" (
    echo [1/3] Kopiuje baze SQLite ...
    copy /y "_baza\database.sqlite" "database\database.sqlite" >nul
    goto pliki
)

if not defined DB_UZYTKOWNIK set "DB_UZYTKOWNIK=root"
if not defined DB_PORT set "DB_PORT=3306"
set "HASLO="
if defined DB_HASLO set "HASLO=-p!DB_HASLO!"

echo [1/3] Tworze baze !DB_NAZWA! i wczytuje dane ...
"%MYSQL%" -h 127.0.0.1 -P !DB_PORT! -u "!DB_UZYTKOWNIK!" !HASLO! -e "CREATE DATABASE IF NOT EXISTS `!DB_NAZWA!` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
"%MYSQL%" -h 127.0.0.1 -P !DB_PORT! -u "!DB_UZYTKOWNIK!" !HASLO! --default-character-set=utf8mb4 "!DB_NAZWA!" < "_baza\joker.sql"
if errorlevel 1 (
    echo Wczytanie bazy nie powiodlo sie. Komunikat MySQL jest powyzej - wklej go Claude.
    pause
    exit /b 1
)

:pliki
echo [2/3] Odtwarzam dowiazanie public\storage ...
"%PHP%" artisan storage:link

echo [3/3] Czyszcze pamiec podreczna ...
"%PHP%" artisan optimize:clear

echo.
echo Gotowe. Jesli strona jest pod innym adresem niz w pracy, zmien APP_URL w pliku .env.
pause

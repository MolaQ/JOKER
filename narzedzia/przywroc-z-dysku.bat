@echo off
rem Przywraca do tej strony zmiany zrobione gdzie indziej (np. w domu), zapisane na dysku
rem skryptem kopia-na-dysk.bat: pliki strony, wgrane zdjecia i baze danych.
rem Uruchom dwuklikiem z folderu narzedzia strony, DO KTOREJ przywracasz (np. w pracy).
rem Wczesniej wlacz MySQL w panelu XAMPP. Plik .env tej strony zostaje bez zmian.
chcp 65001 >nul
setlocal EnableDelayedExpansion

rem Strona: folder nad narzedzia, w ktorym jest ten plik; gdy go tam nie ma (np. plik pobrany osobno) - pytamy.
for %%I in ("%~dp0..") do set "STRONA=%%~fI"
if not exist "!STRONA!\artisan" if exist "%CD%\artisan" set "STRONA=%CD%"
if not exist "!STRONA!\artisan" (
    echo Nie znalazlem strony obok tego pliku ^(szukalem w !STRONA!^).
    set "STRONA="
    set /p "STRONA=Wpisz folder strony, DO KTOREJ przywracasz, np. C:\xampp\www\molak\JOKER: "
)
for %%I in ("!STRONA!") do set "STRONA=%%~fI"
if not exist "!STRONA!\artisan" (
    echo W folderze !STRONA! nie ma strony ^(brak pliku artisan^).
    pause
    exit /b 1
)
pushd "!STRONA!"

set "ZRODLO_DOMYSLNE=D:\JOKER"
set "ZRODLO="
set /p "ZRODLO=Skad przywrocic strone? [Enter = %ZRODLO_DOMYSLNE%]: "
if not defined ZRODLO set "ZRODLO=%ZRODLO_DOMYSLNE%"
for %%I in ("%ZRODLO%") do set "ZRODLO=%%~fI"
if /i "%ZRODLO%"=="!STRONA!" (
    echo To jest ta sama strona - podaj folder z kopia ^(np. pendrive^).
    pause
    exit /b 1
)
if not exist "%ZRODLO%\artisan" (
    echo W %ZRODLO% nie ma kopii strony.
    pause
    exit /b 1
)

echo.
echo Pliki i baza z %ZRODLO% zastapia te w %STRONA%.
echo Obecna baza zostanie najpierw zapisana w _baza\przed-przywroceniem-*.sql.
set "ODP="
set /p "ODP=Kontynuowac? [T/N]: "
if /i not "%ODP%"=="T" exit /b 0

for /f "usebackq eol=# tokens=1,* delims==" %%A in (".env") do (
    if "%%A"=="DB_CONNECTION" set "DB_RODZAJ=%%~B"
    if "%%A"=="DB_DATABASE" set "DB_NAZWA=%%~B"
    if "%%A"=="DB_USERNAME" set "DB_UZYTKOWNIK=%%~B"
    if "%%A"=="DB_PASSWORD" set "DB_HASLO=%%~B"
    if "%%A"=="DB_PORT" set "DB_PORT=%%~B"
)

rem XAMPP: najpierw na dysku ze strona, potem C:\xampp, potem D:\xampp.
set "XAMPP="
for %%P in ("%STRONA:~0,2%\xampp" "C:\xampp" "D:\xampp") do if not defined XAMPP if exist "%%~P\php\php.exe" set "XAMPP=%%~P"
if not defined XAMPP for %%P in ("%STRONA:~0,2%\xampp" "C:\xampp" "D:\xampp") do if not defined XAMPP if exist "%%~P\apps\php\php.exe" set "XAMPP=%%~P\apps"
if not defined XAMPP (
    echo Nie znalazlem XAMPP. Wpisz jego folder w linii set "XAMPP=..." w tym pliku.
    pause
    exit /b 1
)
set "PHP=%XAMPP%\php\php.exe"
set "MYSQL=%XAMPP%\mysql\bin\mysql.exe"
set "MYSQLDUMP=%XAMPP%\mysql\bin\mysqldump.exe"
if not exist "%MYSQL%" set "MYSQL=%XAMPP%\apps\mysql\bin\mysql.exe"
if not exist "%MYSQLDUMP%" set "MYSQLDUMP=%XAMPP%\apps\mysql\bin\mysqldump.exe"

if not defined DB_UZYTKOWNIK set "DB_UZYTKOWNIK=root"
if not defined DB_PORT set "DB_PORT=3306"
set "HASLO="
if defined DB_HASLO set "HASLO=-p!DB_HASLO!"
for /f %%D in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HHmm"') do set "TERAZ=%%D"
if not exist "_baza" mkdir "_baza"

echo [1/5] Zapisuje obecna baze ...
if /i "!DB_RODZAJ!"=="sqlite" (
    if exist "database\database.sqlite" copy /y "database\database.sqlite" "_baza\przed-przywroceniem-!TERAZ!.sqlite" >nul
) else (
    "!MYSQLDUMP!" -h 127.0.0.1 -P !DB_PORT! -u "!DB_UZYTKOWNIK!" !HASLO! --default-character-set=utf8mb4 --routines --single-transaction --result-file="_baza\przed-przywroceniem-!TERAZ!.sql" "!DB_NAZWA!"
    if errorlevel 1 (
        echo Nie udalo sie zapisac obecnej bazy - przerywam, nic nie zostalo zmienione.
        echo Komunikat MySQL jest powyzej - wklej go Claude.
        pause
        exit /b 1
    )
)

echo [2/5] Kopiuje pliki z %ZRODLO% ...
rem Bez /MIR: pliki usuniete w kopii zostaja tutaj. .env, .git i pamiec podreczna tej strony bez zmian.
robocopy "%ZRODLO%" "%STRONA%" /E /XJ /R:1 /W:1 /NFL /NDL /NP ^
    /XF ".env" ^
    /XD "%ZRODLO%\.git" "%ZRODLO%\node_modules" "%ZRODLO%\public\storage" "%ZRODLO%\storage\framework" "%ZRODLO%\storage\logs"
if %ERRORLEVEL% GEQ 8 (
    echo Kopiowanie plikow nie powiodlo sie ^(kod %ERRORLEVEL%^). Baza nie zostala zmieniona.
    pause
    exit /b 1
)

echo [3/5] Wczytuje baze z kopii ...
if /i "!DB_RODZAJ!"=="sqlite" (
    copy /y "_baza\database.sqlite" "database\database.sqlite" >nul
) else (
    if not exist "_baza\joker.sql" (
        echo W kopii nie ma pliku _baza\joker.sql - baza zostaje bez zmian.
    ) else (
        "%MYSQL%" -h 127.0.0.1 -P !DB_PORT! -u "!DB_UZYTKOWNIK!" !HASLO! -e "CREATE DATABASE IF NOT EXISTS `!DB_NAZWA!` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        "%MYSQL%" -h 127.0.0.1 -P !DB_PORT! -u "!DB_UZYTKOWNIK!" !HASLO! --default-character-set=utf8mb4 "!DB_NAZWA!" < "_baza\joker.sql"
        if errorlevel 1 (
            echo Wczytanie bazy nie powiodlo sie. Poprzednia baza jest w _baza\przed-przywroceniem-!TERAZ!.sql
            echo Komunikat MySQL jest powyzej - wklej go Claude.
            pause
            exit /b 1
        )
    )
)

echo [4/5] Uzupelniam baze o nowe tabele (migracje) ...
"%PHP%" artisan migrate --force

echo [5/5] Odtwarzam public\storage i czyszcze pamiec podreczna ...
"%PHP%" artisan storage:link
"%PHP%" artisan optimize:clear

echo.
echo Gotowe. Poprzednia baza tej strony jest w _baza\przed-przywroceniem-!TERAZ!.sql
pause

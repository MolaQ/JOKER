@echo off
rem Kopia strony Jokera na inny dysk (np. pendrive albo dysk D), zeby uruchomic ja w domu.
rem Kopiuje pliki (bez dowiazania public\storage, cache i node_modules) oraz zrzut bazy MySQL.
rem Uruchom dwuklikiem. Sciezki zmienisz w dwoch liniach ponizej.
chcp 65001 >nul
setlocal EnableDelayedExpansion

set "ZRODLO=C:\xampp\www\molak\JOKER"
set "CEL=D:\JOKER"
set "MYSQLDUMP=C:\xampp\mysql\bin\mysqldump.exe"

if not exist "%ZRODLO%\artisan" (
    echo Nie znaleziono strony w %ZRODLO%. Popraw sciezke ZRODLO w tym pliku.
    pause
    exit /b 1
)

echo [1/2] Kopiuje pliki z %ZRODLO% do %CEL% ...
rem /E podfoldery, /XJ pomija dowiazania (public\storage), /R:1 /W:1 nie czeka na zablokowane pliki
robocopy "%ZRODLO%" "%CEL%" /E /XJ /R:1 /W:1 /NFL /NDL /NP ^
    /XD "%ZRODLO%\node_modules" "%ZRODLO%\public\storage" "%ZRODLO%\storage\framework\cache" "%ZRODLO%\storage\framework\sessions" "%ZRODLO%\storage\framework\views" "%ZRODLO%\storage\logs" "%ZRODLO%\.git"
if %ERRORLEVEL% GEQ 8 (
    echo Kopiowanie plikow nie powiodlo sie ^(kod %ERRORLEVEL%^).
    pause
    exit /b 1
)
for %%F in (cache sessions views) do if not exist "%CEL%\storage\framework\%%F" mkdir "%CEL%\storage\framework\%%F"
if not exist "%CEL%\storage\logs" mkdir "%CEL%\storage\logs"

echo [2/2] Zrzut bazy danych ...
for /f "usebackq eol=# tokens=1,* delims==" %%A in ("%ZRODLO%\.env") do (
    if "%%A"=="DB_DATABASE" set "DB_NAZWA=%%~B"
    if "%%A"=="DB_USERNAME" set "DB_UZYTKOWNIK=%%~B"
    if "%%A"=="DB_PASSWORD" set "DB_HASLO=%%~B"
)
if not defined DB_UZYTKOWNIK set "DB_UZYTKOWNIK=root"
if not exist "%CEL%\_baza" mkdir "%CEL%\_baza"
if defined DB_HASLO (
    "%MYSQLDUMP%" -u "!DB_UZYTKOWNIK!" -p"!DB_HASLO!" --default-character-set=utf8mb4 --routines --single-transaction "!DB_NAZWA!" > "%CEL%\_baza\joker.sql"
) else (
    "%MYSQLDUMP%" -u "!DB_UZYTKOWNIK!" --default-character-set=utf8mb4 --routines --single-transaction "!DB_NAZWA!" > "%CEL%\_baza\joker.sql"
)
if errorlevel 1 (
    echo Zrzut bazy nie powiodl sie. Czy MySQL w XAMPP jest uruchomiony?
    pause
    exit /b 1
)

echo.
echo Gotowe. Pliki sa w %CEL%, baza w %CEL%\_baza\joker.sql
echo W domu uruchom narzedzia\przywroc-w-domu.bat z folderu, do ktorego skopiujesz strone.
pause

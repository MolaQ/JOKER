@echo off
rem Kopia strony Jokera na inny dysk (np. pendrive albo dysk D) - w pracy przed wyjsciem do domu
rem i w domu przed powrotem do pracy. Kopiuje strone, z ktorej uruchomiono ten plik
rem (bez dowiazania public\storage, cache i node_modules) oraz zrzut bazy MySQL.
rem Uruchom dwuklikiem z folderu narzedzia strony. Domyslny dysk docelowy zmienisz ponizej.
chcp 65001 >nul
setlocal EnableDelayedExpansion

rem Strona: folder nad narzedzia, w ktorym jest ten plik; gdy go tam nie ma (np. plik pobrany osobno) - pytamy.
for %%I in ("%~dp0..") do set "ZRODLO=%%~fI"
if not exist "!ZRODLO!\artisan" if exist "%CD%\artisan" set "ZRODLO=%CD%"
if not exist "!ZRODLO!\artisan" (
    echo Nie znalazlem strony obok tego pliku ^(szukalem w !ZRODLO!^).
    set "ZRODLO="
    set /p "ZRODLO=Wpisz folder strony do skopiowania, np. C:\xampp\www\molak\JOKER: "
)
for %%I in ("!ZRODLO!") do set "ZRODLO=%%~fI"
if not exist "!ZRODLO!\artisan" (
    echo W folderze !ZRODLO! nie ma strony ^(brak pliku artisan^).
    pause
    exit /b 1
)
set "CEL_DOMYSLNY=D:\JOKER"
set "CEL="
set /p "CEL=Dokad skopiowac strone? [Enter = %CEL_DOMYSLNY%]: "
if not defined CEL set "CEL=%CEL_DOMYSLNY%"
for %%I in ("%CEL%") do set "CEL=%%~fI"
if /i "%CEL%"=="%ZRODLO%" (
    echo Strona juz jest w %CEL% - wybierz inny folder ^(np. E:\JOKER na pendrive^).
    pause
    exit /b 1
)
rem Sciezke do mysqldump.exe skrypt znajdzie sam; mozesz ja tez wpisac tutaj:
set "MYSQLDUMP_RECZNIE="


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
    if "%%A"=="DB_CONNECTION" set "DB_RODZAJ=%%~B"
    if "%%A"=="DB_DATABASE" set "DB_NAZWA=%%~B"
    if "%%A"=="DB_USERNAME" set "DB_UZYTKOWNIK=%%~B"
    if "%%A"=="DB_PASSWORD" set "DB_HASLO=%%~B"
    if "%%A"=="DB_PORT" set "DB_PORT=%%~B"
)
if not exist "%CEL%\_baza" mkdir "%CEL%\_baza"

rem Baza w pliku SQLite: wystarczy skopiowac plik.
if /i "!DB_RODZAJ!"=="sqlite" (
    copy /y "%ZRODLO%\database\database.sqlite" "%CEL%\_baza\database.sqlite" >nul
    echo Baza SQLite skopiowana do %CEL%\_baza\database.sqlite
    goto koniec
)

if not defined DB_UZYTKOWNIK set "DB_UZYTKOWNIK=root"
if not defined DB_PORT set "DB_PORT=3306"
if not defined DB_NAZWA (
    echo W pliku %ZRODLO%\.env nie ma DB_DATABASE.
    pause
    exit /b 1
)

rem mysqldump: najpierw XAMPP z dysku, na ktorym jest strona, potem C:\xampp, potem PATH.
set "MYSQLDUMP=%MYSQLDUMP_RECZNIE%"
for %%P in ("C:\xampp\apps\mysql\bin\mysqldump.exe" "%ZRODLO:~0,2%\xampp\apps\mysql\bin\mysqldump.exe" "%ZRODLO:~0,2%\xampp\mysql\bin\mysqldump.exe" "C:\xampp\mysql\bin\mysqldump.exe" "D:\xampp\mysql\bin\mysqldump.exe") do (
    if not defined MYSQLDUMP if exist %%P set "MYSQLDUMP=%%~P"
)
if not defined MYSQLDUMP for /f "delims=" %%P in ('where mysqldump 2^>nul') do if not defined MYSQLDUMP set "MYSQLDUMP=%%P"
if not defined MYSQLDUMP (
    echo Nie znalazlem programu mysqldump.exe. Wpisz jego sciezke w linii set "MYSQLDUMP_RECZNIE=..." na gorze pliku.
    pause
    exit /b 1
)

echo Baza: !DB_NAZWA!, uzytkownik: !DB_UZYTKOWNIK!, port: !DB_PORT!
echo Program: !MYSQLDUMP!
set "HASLO="
if defined DB_HASLO set "HASLO=-p!DB_HASLO!"
"!MYSQLDUMP!" -h 127.0.0.1 -P !DB_PORT! -u "!DB_UZYTKOWNIK!" !HASLO! --default-character-set=utf8mb4 --routines --single-transaction --result-file="%CEL%\_baza\joker.sql" "!DB_NAZWA!"
if errorlevel 1 (
    echo.
    echo Zrzut bazy nie powiodl sie. Komunikat MySQL jest powyzej - wklej go Claude.
    pause
    exit /b 1
)
echo Baza zapisana w %CEL%\_baza\joker.sql

:koniec
echo.
echo Gotowe. Pliki sa w %CEL%, baza w %CEL%\_baza\joker.sql
echo W domu: pierwszy raz uruchom narzedzia\przywroc-w-domu.bat w skopiowanej stronie.
echo Powrot zmian do pracy: w pracy uruchom narzedzia\przywroc-z-dysku.bat ze strony w pracy.
pause

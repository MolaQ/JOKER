@echo off
rem Paczka strony Jokera na hosting bez SSH (cyber_Folks). Robi w wybranym folderze trzy pliki:
rem   joker-kod.zip      - strona z bibliotekami (composer --no-dev) i zbudowanym wygladem, bez .env
rem   joker-zdjecia.zip  - wgrane zdjecia i pliki (storage\app\public)
rem   joker.sql          - zrzut bazy do importu w phpMyAdmin na serwerze
rem Uruchom dwuklikiem z folderu narzedzia strony (w pracy, gdzie jest aktualna baza).
rem Potrzebne: composer, npm (Node.js) i tar (jest w Windows 10 i 11).
chcp 65001 >nul
setlocal EnableDelayedExpansion

for %%I in ("%~dp0..") do set "ZRODLO=%%~fI"
if not exist "!ZRODLO!\artisan" if exist "%CD%\artisan" set "ZRODLO=%CD%"
if not exist "!ZRODLO!\artisan" (
    echo Nie znalazlem strony obok tego pliku ^(szukalem w !ZRODLO!^).
    set "ZRODLO="
    set /p "ZRODLO=Wpisz folder strony, np. C:\xampp\www\molak\JOKER: "
)
for %%I in ("!ZRODLO!") do set "ZRODLO=%%~fI"
if not exist "!ZRODLO!\artisan" (
    echo W folderze !ZRODLO! nie ma strony ^(brak pliku artisan^).
    pause
    exit /b 1
)

rem Prawdziwy pulpit (bywa przeniesiony do OneDrive\Pulpit), a nie zawsze %USERPROFILE%\Desktop.
set "PULPIT=%USERPROFILE%\Desktop"
for /f "usebackq delims=" %%D in (`powershell -NoProfile -Command "[Environment]::GetFolderPath('Desktop')"`) do if exist "%%D" set "PULPIT=%%D"
set "CEL_DOMYSLNY=!PULPIT!\joker-na-serwer"
set "CEL="
set /p "CEL=Gdzie zapisac paczke? [Enter = !CEL_DOMYSLNY!]: "
if not defined CEL set "CEL=!CEL_DOMYSLNY!"
for %%I in ("%CEL%") do set "CEL=%%~fI"
if not exist "%CEL%" mkdir "%CEL%"
rem Sciezke do mysqldump.exe skrypt znajdzie sam; mozesz ja tez wpisac tutaj:
set "MYSQLDUMP_RECZNIE="

for %%P in (composer npm tar) do (
    where %%P >nul 2>nul || (
        echo Brak programu %%P w systemie. Zainstaluj go albo wklej ten komunikat Claude.
        pause
        exit /b 1
    )
)

set "ROBOCZY=%TEMP%\joker-paczka"
if exist "%ROBOCZY%" rmdir /s /q "%ROBOCZY%"

echo [1/5] Buduje wyglad strony (npm run build) ...
pushd "%ZRODLO%"
rem Zawsze, bo nowe wersje strony moga dodac biblioteki. npm ci instaluje dokladnie wersje z package-lock.json i go nie zmienia, wiec git pull nie bedzie blokowany.
call npm ci --no-audit --no-fund
call npm run build
if errorlevel 1 (
    popd
    echo Budowanie wygladu nie powiodlo sie - komunikat jest powyzej.
    pause
    exit /b 1
)
popd

echo [2/5] Kopiuje pliki strony ...
robocopy "%ZRODLO%" "%ROBOCZY%\joker" /E /XJ /R:1 /W:1 /NFL /NDL /NP ^
    /XD "%ZRODLO%\node_modules" "%ZRODLO%\.git" "%ZRODLO%\vendor" "%ZRODLO%\tests" "%ZRODLO%\_baza" "%ZRODLO%\public\storage" "%ZRODLO%\storage\app\public" "%ZRODLO%\storage\logs" "%ZRODLO%\storage\framework\cache" "%ZRODLO%\storage\framework\sessions" "%ZRODLO%\storage\framework\views" ^
    /XF .env .env.backup .phpunit.result.cache hot *.sqlite
if %ERRORLEVEL% GEQ 8 (
    echo Kopiowanie plikow nie powiodlo sie ^(kod %ERRORLEVEL%^).
    pause
    exit /b 1
)
rem Zapamietane ustawienia z tego komputera nie moga trafic na serwer.
del /q "%ROBOCZY%\joker\bootstrap\cache\*.php" 2>nul
for %%F in (app\public framework\cache\data framework\sessions framework\views logs) do if not exist "%ROBOCZY%\joker\storage\%%F" mkdir "%ROBOCZY%\joker\storage\%%F"

echo [3/5] Pobieram biblioteki bez narzedzi programisty (composer install --no-dev) ...
pushd "%ROBOCZY%\joker"
call composer install --no-dev --optimize-autoloader --no-interaction --no-progress
if errorlevel 1 (
    popd
    echo Composer zglosil blad - komunikat jest powyzej.
    pause
    exit /b 1
)
popd

echo [4/5] Pakuje do %CEL% ...
del /q "%CEL%\joker-kod.zip" "%CEL%\joker-zdjecia.zip" 2>nul
tar -a -c -f "%CEL%\joker-kod.zip" -C "%ROBOCZY%" joker
if errorlevel 1 (
    echo Pakowanie strony nie powiodlo sie.
    pause
    exit /b 1
)
if exist "%ZRODLO%\storage\app\public" (
    tar -a -c -f "%CEL%\joker-zdjecia.zip" -C "%ZRODLO%\storage\app" public
)
rmdir /s /q "%ROBOCZY%"

echo [5/5] Zrzut bazy danych ...
for /f "usebackq eol=# tokens=1,* delims==" %%A in ("%ZRODLO%\.env") do (
    if "%%A"=="DB_DATABASE" set "DB_NAZWA=%%~B"
    if "%%A"=="DB_USERNAME" set "DB_UZYTKOWNIK=%%~B"
    if "%%A"=="DB_PASSWORD" set "DB_HASLO=%%~B"
    if "%%A"=="DB_PORT" set "DB_PORT=%%~B"
)
if not defined DB_UZYTKOWNIK set "DB_UZYTKOWNIK=root"
if not defined DB_PORT set "DB_PORT=3306"

set "MYSQLDUMP=%MYSQLDUMP_RECZNIE%"
for %%P in ("C:\xampp\apps\mysql\bin\mysqldump.exe" "%ZRODLO:~0,2%\xampp\apps\mysql\bin\mysqldump.exe" "%ZRODLO:~0,2%\xampp\mysql\bin\mysqldump.exe" "C:\xampp\mysql\bin\mysqldump.exe" "D:\xampp\mysql\bin\mysqldump.exe") do (
    if not defined MYSQLDUMP if exist %%P set "MYSQLDUMP=%%~P"
)
if not defined MYSQLDUMP for /f "delims=" %%P in ('where mysqldump 2^>nul') do if not defined MYSQLDUMP set "MYSQLDUMP=%%P"
if not defined MYSQLDUMP (
    echo Nie znalazlem mysqldump.exe. Wpisz jego sciezke w linii set "MYSQLDUMP_RECZNIE=..." na gorze pliku
    echo albo wyeksportuj baze !DB_NAZWA! w phpMyAdmin do pliku %CEL%\joker.sql.
    echo Do zwyklej aktualizacji strony zrzut bazy NIE jest potrzebny - joker-kod.zip jest gotowy.
    goto koniec
)
set "HASLO="
if defined DB_HASLO set "HASLO=-p!DB_HASLO!"
"!MYSQLDUMP!" -h 127.0.0.1 -P !DB_PORT! -u "!DB_UZYTKOWNIK!" !HASLO! --default-character-set=utf8mb4 --single-transaction --skip-lock-tables --result-file="%CEL%\joker.sql" "!DB_NAZWA!"
if errorlevel 1 (
    echo Zrzut bazy nie powiodl sie ^(komunikat MySQL powyzej^).
    echo Do zwyklej aktualizacji strony zrzut bazy NIE jest potrzebny - joker-kod.zip jest gotowy.
    goto koniec
)
rem Nowsze MariaDB dopisuja linie i porownania znakow, ktorych starszy MySQL/MariaDB na serwerze nie zna.
powershell -NoProfile -Command "$p='%CEL%\joker.sql'; $t=[IO.File]::ReadAllText($p); $t=$t -replace '(?m)^/\*M!999999.*\r?\n','' -replace 'utf8mb4_uca1400_ai_ci','utf8mb4_unicode_ci'; [IO.File]::WriteAllText($p,$t,(New-Object Text.UTF8Encoding $false))"

:koniec
echo.
echo Gotowe. Paczka jest w folderze:
echo   %CEL%
echo Do aktualizacji strony wgraj tylko joker-kod.zip. joker.sql NIE importuj na serwer przy aktualizacji.
start "" explorer "%CEL%"
pause

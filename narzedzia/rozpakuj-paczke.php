<?php

/**
 * Sprawdzenie i rozpakowanie paczki joker-kod.zip wgranej przez panel (Aktualizacja strony).
 * Działa bez Laravela: uruchamia go instalator z crona (instaluj-na-serwerze.sh), gdy w folderze
 * storage/app/aktualizacja leży plik flaga.json zatwierdzony przez administratora.
 *   php narzedzia/rozpakuj-paczke.php /sciezka/do/storage/app/aktualizacja
 * Te same funkcje wykorzystuje panel do sprawdzenia paczki zaraz po wgraniu.
 */

/** Pliki, które musi mieć każda paczka strony, od razu w głównym folderze zipa. */
const JOKER_PACZKA_WYMAGANE = ['artisan', 'public/index.php', 'vendor/autoload.php', 'bootstrap/wersja-paczki.txt'];

/**
 * Nazwa wpisu bez „./” na początku (tak pakuje zapasowy tar w paczka-na-serwer.bat) i z ukośnikami jak w Linuksie.
 */
function joker_zip_nazwa(string $nazwa): string
{
    $nazwa = str_replace('\\', '/', $nazwa);

    while (str_starts_with($nazwa, './')) {
        $nazwa = substr($nazwa, 2);
    }

    return $nazwa;
}

/**
 * Czy wpis z zipa wolno rozpakować: bez wychodzenia poza folder i bez nadpisywania ustawień, zdjęć i logów.
 */
function joker_paczka_wpis_dozwolony(string $nazwa): bool
{
    // „./.env” to też .env: najpierw zdejmujemy „./” z początku.
    $nazwa = joker_zip_nazwa($nazwa);

    if ($nazwa === '' || $nazwa[0] === '/' || preg_match('#(^|/)\.\.(/|$)#', $nazwa) || preg_match('#^[A-Za-z]:#', $nazwa)) {
        return false;
    }

    foreach (['.env', 'storage/', 'public/storage', 'node_modules/', '.git/'] as $pomijane) {
        if ($nazwa === rtrim($pomijane, '/') || str_starts_with($nazwa, $pomijane)) {
            return false;
        }
    }

    return true;
}

/**
 * Spis wpisów zipa czytany wprost z pliku (bez rozszerzenia PHP „zip”, którego bywa brak na hostingu).
 * Obsługuje zipy z Windows (tar/PowerShell), zwykłe i ZIP64; metody: bez kompresji (0) i deflate (8).
 *
 * @return list<array{nazwa: string, metoda: int, skompresowany: int, rozmiar: int, offset: int, crc: int}>
 *
 * @throws RuntimeException
 */
function joker_zip_wpisy(string $zip): array
{
    $plik = @fopen($zip, 'rb');
    $dlugosc = $plik ? (int) filesize($zip) : 0;

    if (! $plik || $dlugosc < 22) {
        throw new RuntimeException('Plik nie jest poprawnym archiwum ZIP (mógł się wgrać tylko w części).');
    }

    // Koniec katalogu centralnego leży w ostatnich 64 KB (22 bajty + komentarz).
    $ogon = min($dlugosc, 65557);
    fseek($plik, $dlugosc - $ogon);
    $koniec = (string) fread($plik, $ogon);
    $pozycja = strrpos($koniec, "PK\x05\x06");

    if ($pozycja === false) {
        fclose($plik);

        throw new RuntimeException('Plik nie jest poprawnym archiwum ZIP (mógł się wgrać tylko w części).');
    }

    $eocd = unpack('vdysk/vdyskcd/vwpisyDysk/vwpisy/Vrozmiar/Voffset', substr($koniec, $pozycja + 4, 16));
    $ile = $eocd['wpisy'];
    $offsetCd = $eocd['offset'];
    $rozmiarCd = $eocd['rozmiar'];

    // ZIP64: prawdziwe liczby w osobnym rekordzie, na który wskazuje lokalizator tuż przed końcem katalogu.
    if ($ile === 0xFFFF || $offsetCd === 0xFFFFFFFF || $rozmiarCd === 0xFFFFFFFF) {
        $lokalizator = $pozycja - 20;

        if ($lokalizator < 0 || substr($koniec, $lokalizator, 4) !== "PK\x06\x07") {
            fclose($plik);

            throw new RuntimeException('Nie udało się odczytać spisu plików w paczce (ZIP64).');
        }

        $zip64 = unpack('P', substr($koniec, $lokalizator + 8, 8))[1];
        fseek($plik, $zip64);
        $rekord = (string) fread($plik, 56);

        if (substr($rekord, 0, 4) !== "PK\x06\x06") {
            fclose($plik);

            throw new RuntimeException('Nie udało się odczytać spisu plików w paczce (ZIP64).');
        }

        $ile = unpack('P', substr($rekord, 32, 8))[1];
        $rozmiarCd = unpack('P', substr($rekord, 40, 8))[1];
        $offsetCd = unpack('P', substr($rekord, 48, 8))[1];
    }

    fseek($plik, $offsetCd);
    $katalog = (string) fread($plik, max(0, $rozmiarCd));
    fclose($plik);

    $wpisy = [];
    $i = 0;

    for ($n = 0; $n < $ile; $n++) {
        if (substr($katalog, $i, 4) !== "PK\x01\x02") {
            throw new RuntimeException('Spis plików w paczce jest uszkodzony.');
        }

        $naglowek = unpack('vmetoda/vczas/vdata/Vcrc/Vskompresowany/Vrozmiar/vdlNazwy/vdlExtra/vdlKomentarza/vdysk/vatrWew/VatrZew/Voffset', substr($katalog, $i + 10, 36));
        $nazwa = substr($katalog, $i + 46, $naglowek['dlNazwy']);
        $extra = substr($katalog, $i + 46 + $naglowek['dlNazwy'], $naglowek['dlExtra']);
        $wpis = ['nazwa' => $nazwa, 'metoda' => $naglowek['metoda'], 'skompresowany' => $naglowek['skompresowany'], 'rozmiar' => $naglowek['rozmiar'], 'offset' => $naglowek['offset'], 'crc' => $naglowek['crc']];

        // Pole ZIP64 (0x0001) podaje kolejno te wartości, które w nagłówku mają 0xFFFFFFFF.
        for ($e = 0; $e + 4 <= strlen($extra);) {
            ['id' => $id, 'dl' => $dl] = unpack('vid/vdl', substr($extra, $e, 4));

            if ($id === 0x0001) {
                $dane = substr($extra, $e + 4, $dl);
                $k = 0;

                foreach (['rozmiar', 'skompresowany', 'offset'] as $pole) {
                    if ($wpis[$pole] === 0xFFFFFFFF && $k + 8 <= strlen($dane)) {
                        $wpis[$pole] = unpack('P', substr($dane, $k, 8))[1];
                        $k += 8;
                    }
                }
            }

            $e += 4 + $dl;
        }

        $wpisy[] = $wpis;
        $i += 46 + $naglowek['dlNazwy'] + $naglowek['dlExtra'] + $naglowek['dlKomentarza'];
    }

    return $wpisy;
}

/**
 * Zawartość jednego pliku z zipa (odczyt bez rozszerzenia „zip”).
 *
 * @param  resource  $plik  otwarty zip
 * @param  array{nazwa: string, metoda: int, skompresowany: int, rozmiar: int, offset: int, crc: int}  $wpis
 *
 * @throws RuntimeException
 */
function joker_zip_czytaj($plik, array $wpis): string
{
    fseek($plik, $wpis['offset']);
    $lokalny = (string) fread($plik, 30);

    if (substr($lokalny, 0, 4) !== "PK\x03\x04") {
        throw new RuntimeException('Paczka jest uszkodzona (plik '.$wpis['nazwa'].').');
    }

    ['dlNazwy' => $dlNazwy, 'dlExtra' => $dlExtra] = unpack('vdlNazwy/vdlExtra', substr($lokalny, 26, 4));
    fseek($plik, $wpis['offset'] + 30 + $dlNazwy + $dlExtra);
    $surowe = $wpis['skompresowany'] > 0 ? (string) fread($plik, $wpis['skompresowany']) : '';

    $tresc = match ($wpis['metoda']) {
        0 => $surowe,
        8 => function_exists('gzinflate') ? @gzinflate($surowe) : throw new RuntimeException('Na serwerze brakuje rozszerzenia PHP „zlib” do rozpakowania paczki.'),
        default => throw new RuntimeException('Nieobsługiwany sposób kompresji w paczce (plik '.$wpis['nazwa'].'). Zbuduj ją skryptem paczka-na-serwer.bat.'),
    };

    if ($tresc === false || strlen($tresc) !== $wpis['rozmiar'] || (crc32($tresc) & 0xFFFFFFFF) !== $wpis['crc']) {
        throw new RuntimeException('Paczka jest uszkodzona (plik '.$wpis['nazwa'].'). Wgraj ją ponownie.');
    }

    return $tresc;
}

/**
 * Sprawdza, czy zip jest paczką strony w poprawnym układzie.
 *
 * @return array{wersja: string, pliki: int, migracje: list<string>}
 *
 * @throws RuntimeException z komunikatem dla administratora
 */
function joker_sprawdz_paczke(string $zip): array
{
    if (! is_file($zip)) {
        throw new RuntimeException('Plik nie jest poprawnym archiwum ZIP (mógł się wgrać tylko w części).');
    }

    $wpisy = [];

    foreach (joker_zip_wpisy($zip) as $wpis) {
        if (($nazwa = joker_zip_nazwa($wpis['nazwa'])) !== '') {
            $wpisy[$nazwa] = $wpis;
        }
    }

    foreach (JOKER_PACZKA_WYMAGANE as $wymagany) {
        if (! isset($wpisy[$wymagany])) {
            $zagniezdzony = array_filter(array_keys($wpisy), fn (string $nazwa) => str_ends_with($nazwa, '/'.$wymagany));

            throw new RuntimeException($zagniezdzony
                ? 'Paczka ma dodatkowy folder nadrzędny ('.dirname(reset($zagniezdzony), substr_count($wymagany, '/') + 1).'). Zbuduj ją skryptem paczka-na-serwer.bat.'
                : 'To nie jest paczka strony: brakuje pliku '.$wymagany.'. Zbuduj ją skryptem paczka-na-serwer.bat.');
        }
    }

    $plik = fopen($zip, 'rb');
    $wersja = trim(joker_zip_czytaj($plik, $wpisy['bootstrap/wersja-paczki.txt']));
    fclose($plik);
    $migracje = [];

    foreach (array_keys($wpisy) as $nazwa) {
        if (preg_match('#^database/migrations/([^/]+\.php)$#', $nazwa, $trafienie)) {
            $migracje[] = $trafienie[1];
        }
    }

    sort($migracje);
    $pliki = count(array_filter(array_keys($wpisy), fn (string $nazwa) => ! str_ends_with($nazwa, '/')));

    if ($wersja === '') {
        throw new RuntimeException('Paczka nie ma numeru wersji (pusty plik bootstrap/wersja-paczki.txt).');
    }

    return ['wersja' => $wersja, 'pliki' => $pliki, 'migracje' => $migracje];
}

/**
 * Rozpakowuje paczkę do pustego folderu, pomijając .env, storage i public/storage.
 * Z rozszerzeniem „zip” przez ZipArchive, bez niego czytając zip wprost (joker_zip_wpisy).
 *
 * @return int liczba rozpakowanych plików
 */
function joker_rozpakuj_paczke(string $zip, string $cel, bool $bezRozszerzenia = false): int
{
    if (! is_dir($cel) && ! mkdir($cel, 0755, true)) {
        throw new RuntimeException('Nie udało się utworzyć folderu '.$cel.'.');
    }

    if (class_exists(ZipArchive::class) && ! $bezRozszerzenia) {
        $archiwum = new ZipArchive;

        if ($archiwum->open($zip) !== true) {
            throw new RuntimeException('Nie udało się otworzyć paczki.');
        }

        $wpisy = [];

        for ($i = 0; $i < $archiwum->numFiles; $i++) {
            $nazwa = (string) $archiwum->getNameIndex($i);

            if (joker_paczka_wpis_dozwolony($nazwa)) {
                $wpisy[] = $nazwa;
            }
        }

        if (! $archiwum->extractTo($cel, $wpisy)) {
            $archiwum->close();

            throw new RuntimeException('Rozpakowanie przerwane (brak miejsca na dysku?).');
        }

        $archiwum->close();

        return count(array_filter($wpisy, fn (string $nazwa) => ! str_ends_with($nazwa, '/')));
    }

    $plik = fopen($zip, 'rb');
    $pliki = 0;

    try {
        foreach (joker_zip_wpisy($zip) as $wpis) {
            $nazwa = joker_zip_nazwa($wpis['nazwa']);

            if ($nazwa === '' || ! joker_paczka_wpis_dozwolony($nazwa)) {
                continue;
            }

            $sciezka = $cel.'/'.$nazwa;

            if (str_ends_with($nazwa, '/')) {
                is_dir($sciezka) || mkdir($sciezka, 0755, true);

                continue;
            }

            $folder = dirname($sciezka);

            if (! is_dir($folder) && ! mkdir($folder, 0755, true)) {
                throw new RuntimeException('Nie udało się utworzyć folderu '.$folder.'.');
            }

            if (file_put_contents($sciezka, joker_zip_czytaj($plik, $wpis)) === false) {
                throw new RuntimeException('Rozpakowanie przerwane (brak miejsca na dysku?).');
            }

            $pliki++;
        }
    } finally {
        fclose($plik);
    }

    return $pliki;
}

if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    $folder = rtrim((string) ($argv[1] ?? ''), '/');
    $zip = $folder.'/joker-kod.zip';
    $flaga = json_decode((string) @file_get_contents($folder.'/flaga.json'), true);

    try {
        if (! is_array($flaga) || ($flaga['typ'] ?? '') !== 'instaluj' || empty($flaga['sha256'])) {
            throw new RuntimeException('Brak poprawnego pliku flagi: instalacji nikt nie zatwierdził w panelu.');
        }

        if (! is_file($zip) || ! hash_equals((string) $flaga['sha256'], (string) hash_file('sha256', $zip))) {
            throw new RuntimeException('Paczka na serwerze różni się od tej, którą zatwierdzono w panelu (inna suma kontrolna). Wgraj ją ponownie.');
        }

        $paczka = joker_sprawdz_paczke($zip);
        $pliki = joker_rozpakuj_paczke($zip, $folder.'/nowa');

        echo 'Paczka '.$paczka['wersja'].': rozpakowano '.$pliki.' plików.'.PHP_EOL;
        exit(0);
    } catch (Throwable $blad) {
        echo 'BŁĄD paczki z panelu: '.$blad->getMessage().PHP_EOL;
        exit(1);
    }
}

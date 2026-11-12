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
 * Czy wpis z zipa wolno rozpakować: bez wychodzenia poza folder i bez nadpisywania ustawień, zdjęć i logów.
 */
function joker_paczka_wpis_dozwolony(string $nazwa): bool
{
    $nazwa = str_replace('\\', '/', $nazwa);

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
 * Sprawdza, czy zip jest paczką strony w poprawnym układzie.
 *
 * @return array{wersja: string, pliki: int, migracje: list<string>}
 *
 * @throws RuntimeException z komunikatem dla administratora
 */
function joker_sprawdz_paczke(string $zip): array
{
    if (! class_exists(ZipArchive::class)) {
        throw new RuntimeException('Na serwerze brakuje rozszerzenia PHP „zip”. Wgraj paczkę przez menedżer plików.');
    }

    $archiwum = new ZipArchive;

    if (! is_file($zip) || $archiwum->open($zip) !== true) {
        throw new RuntimeException('Plik nie jest poprawnym archiwum ZIP (mógł się wgrać tylko w części).');
    }

    $nazwy = [];

    for ($i = 0; $i < $archiwum->numFiles; $i++) {
        $nazwy[str_replace('\\', '/', (string) $archiwum->getNameIndex($i))] = true;
    }

    foreach (JOKER_PACZKA_WYMAGANE as $wymagany) {
        if (! isset($nazwy[$wymagany])) {
            $zagniezdzony = array_filter(array_keys($nazwy), fn (string $nazwa) => str_ends_with($nazwa, '/'.$wymagany));
            $archiwum->close();

            throw new RuntimeException($zagniezdzony
                ? 'Paczka ma dodatkowy folder nadrzędny ('.dirname(reset($zagniezdzony), substr_count($wymagany, '/') + 1).'). Zbuduj ją skryptem paczka-na-serwer.bat.'
                : 'To nie jest paczka strony: brakuje pliku '.$wymagany.'. Zbuduj ją skryptem paczka-na-serwer.bat.');
        }
    }

    $wersja = trim((string) $archiwum->getFromName('bootstrap/wersja-paczki.txt'));
    $migracje = [];

    foreach (array_keys($nazwy) as $nazwa) {
        if (preg_match('#^database/migrations/([^/]+\.php)$#', $nazwa, $trafienie)) {
            $migracje[] = $trafienie[1];
        }
    }

    sort($migracje);
    $pliki = count(array_filter(array_keys($nazwy), fn (string $nazwa) => ! str_ends_with($nazwa, '/')));
    $archiwum->close();

    if ($wersja === '') {
        throw new RuntimeException('Paczka nie ma numeru wersji (pusty plik bootstrap/wersja-paczki.txt).');
    }

    return ['wersja' => $wersja, 'pliki' => $pliki, 'migracje' => $migracje];
}

/**
 * Rozpakowuje paczkę do pustego folderu, pomijając .env, storage i public/storage.
 *
 * @return int liczba rozpakowanych plików
 */
function joker_rozpakuj_paczke(string $zip, string $cel): int
{
    $archiwum = new ZipArchive;

    if ($archiwum->open($zip) !== true) {
        throw new RuntimeException('Nie udało się otworzyć paczki.');
    }

    if (! is_dir($cel) && ! mkdir($cel, 0755, true)) {
        throw new RuntimeException('Nie udało się utworzyć folderu '.$cel.'.');
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

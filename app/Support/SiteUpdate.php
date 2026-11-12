<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Aktualizacja strony z panelu: administrator wgrywa paczkę joker-kod.zip w kawałkach, panel ją sprawdza,
 * a po zatwierdzeniu zostawia plik flagę. Rozpakowuje i instaluje dopiero cron (narzedzia/instaluj-na-serwerze.sh):
 * bez flagi z sumą kontrolną tej samej paczki nie rusza niczego.
 */
class SiteUpdate
{
    /** Największa paczka, jaką przyjmie panel. */
    public const MAX_BYTES = 400 * 1024 * 1024;

    /** Kawałek wysyłany jednym żądaniem: mniejszy niż typowe limity wysyłania plików na hostingu. */
    public const CHUNK_BYTES = 1024 * 1024;

    /** Inny folder roboczy (testy). */
    public static ?string $dirOverride = null;

    public static function dir(): string
    {
        return self::$dirOverride ?? storage_path('app/aktualizacja');
    }

    public static function zipPath(): string
    {
        return self::dir().'/joker-kod.zip';
    }

    public static function rollbackPath(): string
    {
        return (self::$dirOverride ?? dirname(base_path()).'/kopie-joker').'/kod-poprzedni.tar.gz';
    }

    public static function installedVersion(): ?string
    {
        $file = base_path('bootstrap/wersja-paczki.txt');

        return is_file($file) ? (trim((string) file_get_contents($file)) ?: null) : null;
    }

    /**
     * Cron właśnie rozpakowuje albo instaluje paczkę: w tym czasie panel niczego nie zmienia.
     */
    public static function locked(): bool
    {
        return is_dir(self::dir().'/blokada');
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function json(string $name): ?array
    {
        $file = self::dir().'/'.$name;
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        return is_array($data) ? $data : null;
    }

    /**
     * Wgrana i sprawdzona paczka czekająca na decyzję.
     *
     * @return array{wersja: string, pliki: int, rozmiar: int, sha256: string, migracje: list<string>, starsza: bool, wgrano: string, kto: string}|null
     */
    public static function package(): ?array
    {
        return is_file(self::zipPath()) ? self::json('paczka.json') : null;
    }

    /**
     * Zatwierdzona instalacja albo cofnięcie, które czeka na cron.
     *
     * @return array<string, mixed>|null
     */
    public static function flag(): ?array
    {
        return self::json('flaga.json');
    }

    /**
     * Ostatni wynik zapisany przez cron.
     *
     * @return array{stan: string, opis: string, czas: string}|null
     */
    public static function status(): ?array
    {
        return self::json('stan.json');
    }

    /**
     * Przyjmuje kolejny kawałek paczki. Po ostatnim sprawdza całość i zwraca opis paczki.
     *
     * @return array<string, mixed>|null opis paczki po ostatnim kawałku
     *
     * @throws RuntimeException z komunikatem dla administratora
     */
    public static function receiveChunk(string $uploadId, int $index, int $total, UploadedFile $chunk, User $user): ?array
    {
        if (! preg_match('/^[a-z0-9]{8,40}$/', $uploadId) || $total < 1 || $index < 0 || $index >= $total) {
            throw new RuntimeException('Niepoprawne dane wysyłania. Odśwież stronę i spróbuj ponownie.');
        }

        if (self::locked() || self::flag()) {
            throw new RuntimeException('Trwa instalacja poprzedniej paczki. Poczekaj, aż się skończy.');
        }

        File::ensureDirectoryExists(self::dir());
        $part = self::dir().'/wgrywanie-'.$uploadId.'.part';
        $counter = self::dir().'/wgrywanie-'.$uploadId.'.nr';

        if ($index === 0) {
            foreach (File::glob(self::dir().'/wgrywanie-*') as $stale) {
                File::delete($stale);
            }
            File::delete([self::zipPath(), self::dir().'/paczka.json']);
            File::put($part, '');
        }

        $expected = $index === 0 ? 0 : (int) (is_file($counter) ? file_get_contents($counter) : -1);

        if ($expected !== $index || ! is_file($part)) {
            throw new RuntimeException('Kawałki paczki przyszły w złej kolejności. Wyślij plik jeszcze raz.');
        }

        if (filesize($part) + $chunk->getSize() > self::MAX_BYTES) {
            File::delete([$part, $counter]);

            throw new RuntimeException('Paczka jest za duża (limit '.(int) (self::MAX_BYTES / 1024 / 1024).' MB).');
        }

        $source = fopen($chunk->getRealPath(), 'rb');
        $target = fopen($part, 'ab');
        stream_copy_to_stream($source, $target);
        fclose($source);
        fclose($target);
        File::put($counter, (string) ($index + 1));

        if ($index + 1 < $total) {
            return null;
        }

        File::delete($counter);
        File::move($part, self::zipPath());

        return self::inspect($user);
    }

    /**
     * Sprawdza wgraną paczkę i zapisuje jej opis; złą paczkę od razu usuwa.
     *
     * @return array<string, mixed>
     */
    public static function inspect(User $user): array
    {
        require_once base_path('narzedzia/rozpakuj-paczke.php');

        try {
            $package = joker_sprawdz_paczke(self::zipPath());
        } catch (RuntimeException $error) {
            File::delete(self::zipPath());

            throw $error;
        }

        $installed = self::installedVersion();
        $known = collect(File::files(database_path('migrations')))->map->getFilename();
        $package += [
            'rozmiar' => (int) filesize(self::zipPath()),
            'sha256' => (string) hash_file('sha256', self::zipPath()),
            'starsza' => $installed !== null && strcmp($package['wersja'], $installed) < 0,
            'wgrano' => now()->format('Y-m-d H:i:s'),
            'kto' => $user->name,
        ];
        $package['migracje'] = collect($package['migracje'])->diff($known)->values()->all();

        File::put(self::dir().'/paczka.json', json_encode($package, JSON_UNESCAPED_UNICODE));
        AuditLog::record('site_update.uploaded', 'Aktualizacja strony: wgrano paczkę '.$package['wersja'], null, ['sha256' => $package['sha256'], 'rozmiar' => $package['rozmiar']]);

        return $package;
    }

    /**
     * Zatwierdza instalację wgranej paczki: zostawia flagę dla crona.
     */
    public static function requestInstall(User $user): void
    {
        $package = self::package();

        if (! $package || self::flag() || self::locked()) {
            throw new RuntimeException('Nie ma paczki do zainstalowania albo instalacja już czeka.');
        }

        self::writeFlag(['typ' => 'instaluj', 'wersja' => $package['wersja'], 'sha256' => $package['sha256']], $user);
        AuditLog::record('site_update.approved', 'Aktualizacja strony: zatwierdzono instalację paczki '.$package['wersja'], null, ['sha256' => $package['sha256']]);
    }

    /**
     * Zleca przywrócenie poprzedniej wersji kodu (kopia robiona przed każdą aktualizacją z panelu).
     */
    public static function requestRollback(User $user): void
    {
        if (! is_file(self::rollbackPath()) || self::flag() || self::locked()) {
            throw new RuntimeException('Nie ma kopii poprzedniej wersji albo inna operacja już czeka.');
        }

        self::writeFlag(['typ' => 'cofnij'], $user);
        AuditLog::record('site_update.rollback', 'Aktualizacja strony: zlecono przywrócenie poprzedniej wersji kodu');
    }

    /**
     * @param  array<string, string>  $data
     */
    private static function writeFlag(array $data, User $user): void
    {
        File::ensureDirectoryExists(self::dir());
        File::delete(self::dir().'/stan.json');
        File::put(self::dir().'/flaga.json', json_encode($data + ['kto' => $user->name, 'czas' => now()->format('Y-m-d H:i:s')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Wycofuje zatwierdzenie albo usuwa wgraną paczkę, dopóki cron nie zaczął pracy.
     */
    public static function cancel(): void
    {
        if (self::locked()) {
            throw new RuntimeException('Instalacja już trwa, nie można jej przerwać.');
        }

        File::delete([self::dir().'/flaga.json', self::zipPath(), self::dir().'/paczka.json']);
        AuditLog::record('site_update.cancelled', 'Aktualizacja strony: usunięto wgraną paczkę');
    }

    /**
     * Ostatnie linie logu instalatora.
     */
    public static function logTail(int $lines = 30): string
    {
        $file = storage_path('logs/instalacja.log');

        if (! is_file($file)) {
            return '';
        }

        $handle = fopen($file, 'rb');
        fseek($handle, max(0, filesize($file) - 8192));
        $tail = (string) stream_get_contents($handle);
        fclose($handle);

        return implode("\n", array_slice(explode("\n", trim($tail)), -$lines));
    }
}

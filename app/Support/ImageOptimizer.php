<?php

namespace App\Support;

use GdImage;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Zdjęcia wgrywane prosto z telefonu (galerie) potrafią mieć po kilka MB. Po wgraniu zmniejszamy je
 * do rozsądnego rozmiaru, obracamy według EXIF, usuwamy metadane (np. GPS) i robimy miniaturę do siatek galerii.
 * Działa na GD; gdy coś pójdzie nie tak, plik zostaje bez zmian.
 */
class ImageOptimizer
{
    /** Najdłuższy bok zdjęcia na pełnym ekranie. */
    public const MAX_SIZE = 2000;

    /** Najdłuższy bok miniatury w siatce galerii. */
    public const THUMB_SIZE = 640;

    public const QUALITY = 82;

    /**
     * Zmniejsza zdjęcie na dysku public i zapisuje miniaturę obok (folder „thumbs”).
     *
     * @return bool czy plik został przetworzony
     */
    public static function process(string $path): bool
    {
        $disk = Storage::disk('public');

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! function_exists('imagecreatefromstring') || ! in_array($extension, ['jpg', 'jpeg', 'png'], true) || ! $disk->exists($path)) {
            return false;
        }

        try {
            $full = $disk->path($path);

            // Zdjęcie 24 Mpx z telefonu zajmuje w GD ok. 100 MB pamięci; domyślne 128 MB w XAMPP to za mało.
            if ((int) ini_get('memory_limit') !== -1) {
                @ini_set('memory_limit', '512M');
            }

            $image = @imagecreatefromstring((string) file_get_contents($full));

            if (! $image instanceof GdImage) {
                return false;
            }

            $oriented = self::orient($image, $full);
            $rotated = $oriented !== $image;
            $image = $oriented;
            $png = $extension === 'png';

            // JPEG zapisujemy zawsze od nowa: znika EXIF z lokalizacją GPS (zdjęcia dzieci).
            if ($rotated || ! $png || max(imagesx($image), imagesy($image)) > self::MAX_SIZE || filesize($full) > 600 * 1024) {
                self::save(self::fit($image, self::MAX_SIZE), $full, $png);
            }

            $thumb = self::thumbPath($path);
            $disk->makeDirectory(dirname($thumb));
            self::save(self::fit($image, self::THUMB_SIZE), $disk->path($thumb), $png);

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * „galleries/5/abc.jpg” → „galleries/5/thumbs/abc.jpg”.
     */
    public static function thumbPath(string $path): string
    {
        return trim(dirname($path), '/.').'/thumbs/'.basename($path);
    }

    private static function fit(GdImage $image, int $max): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $max / max($width, $height));

        if ($scale >= 1) {
            return $image;
        }

        $resized = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, imagesx($resized), imagesy($resized), $width, $height);

        return $resized;
    }

    private static function save(GdImage $image, string $file, bool $png): void
    {
        if ($png) {
            imagesavealpha($image, true);
            imagepng($image, $file, 8);

            return;
        }

        imageinterlace($image, true);
        imagejpeg($image, $file, self::QUALITY);
    }

    /**
     * Zdjęcia z telefonu bywają zapisane „bokiem” z informacją o obrocie w EXIF.
     */
    private static function orient(GdImage $image, string $file): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($file)['Orientation'] ?? 1);

        return match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }
}

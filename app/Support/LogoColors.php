<?php

namespace App\Support;

/**
 * Podpowiedź kolorów strony z herbu klubu: najczęstsze wyraziste kolory obrazka (bez tła i szarości).
 */
class LogoColors
{
    /**
     * @return array<string, string>|null klucze ustawień z grupy „kolory” → #rrggbb; null, gdy obrazka nie da się odczytać albo jest bezbarwny
     */
    public static function suggest(string $path): ?array
    {
        $colors = self::dominant($path);

        if ($colors === []) {
            return null;
        }

        // Kolor główny: najczęstszy wyrazisty, ale nie za ciemny (ma być widać go na ciemnym tle nagłówka).
        $accent = collect($colors)->first(fn (array $c) => self::lightness($c) >= 0.3 && self::lightness($c) <= 0.75) ?? $colors[0];
        // Drugi kolor (pasek nad menu): inny odcień z herbu i nie za ciemny; herb jednobarwny = kolor główny.
        $second = collect($colors)->first(fn (array $c) => self::hueGap($c, $accent) > 45 && self::lightness($c) >= 0.3) ?? $accent;
        // Granat klubowy: najciemniejszy wyrazisty z herbu albo przyciemniony główny.
        $deep = collect($colors)->sortBy(fn (array $c) => self::lightness($c))->first();
        $navy = self::lightness($deep) < 0.35 ? $deep : self::mix($accent, [10, 14, 30], 0.6);
        $dark = self::mix($navy, [8, 10, 16], 0.75);

        return [
            'color_accent' => self::hex($accent),
            'color_like' => self::hex($accent),
            'color_venue' => self::hex($accent),
            'color_gym' => self::hex($accent),
            'color_panel' => self::hex(self::lightness($accent) > 0.6 ? self::mix($accent, [0, 0, 0], 0.3) : $accent),
            'color_club_navy' => self::hex($navy),
            'color_dark' => self::hex($dark),
            'color_dark_alt' => self::hex(self::mix($dark, [255, 255, 255], 0.08)),
            'color_topbar_bg' => self::hex($second),
            'color_topbar_text' => self::readableOn($second),
            'color_nav_button_bg' => self::hex($second),
            'color_nav_button_text' => self::readableOn($second),
            'color_countdown' => self::hex($second),
        ];
    }

    /**
     * Wyraziste kolory obrazka od najczęstszego.
     *
     * @return list<array{0: int, 1: int, 2: int}>
     */
    public static function dominant(string $path): array
    {
        if (! is_file($path) || ! function_exists('imagecreatefromstring')) {
            return [];
        }

        $image = @imagecreatefromstring((string) file_get_contents($path));

        if (! $image) {
            return [];
        }

        $size = 64;
        $small = imagecreatetruecolor($size, $size);
        imagealphablending($small, false);
        imagesavealpha($small, true);
        imagefill($small, 0, 0, imagecolorallocatealpha($small, 0, 0, 0, 127));
        imagecopyresampled($small, $image, 0, 0, 0, 0, $size, $size, imagesx($image), imagesy($image));
        imagedestroy($image);

        $buckets = [];

        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size; $y++) {
                $rgba = imagecolorat($small, $x, $y);

                if ((($rgba >> 24) & 0x7F) > 64) {
                    continue; // przezroczyste tło
                }

                $rgb = [($rgba >> 16) & 0xFF, ($rgba >> 8) & 0xFF, $rgba & 0xFF];

                if (self::saturation($rgb) < 0.25 || self::lightness($rgb) > 0.92 || self::lightness($rgb) < 0.06) {
                    continue; // biel, czerń i szarości to zwykle tło albo kontury
                }

                $key = implode(',', array_map(fn (int $v) => intdiv($v, 32), $rgb));
                $buckets[$key] ??= ['count' => 0, 'sum' => [0, 0, 0]];
                $buckets[$key]['count']++;
                $buckets[$key]['sum'] = [$buckets[$key]['sum'][0] + $rgb[0], $buckets[$key]['sum'][1] + $rgb[1], $buckets[$key]['sum'][2] + $rgb[2]];
            }
        }

        imagedestroy($small);

        $minimum = max(3, (int) (array_sum(array_column($buckets, 'count')) * 0.02));

        $colors = collect($buckets)
            ->filter(fn (array $bucket) => $bucket['count'] >= $minimum)
            ->sortByDesc('count')
            ->map(fn (array $bucket) => array_map(fn (int $sum) => (int) round($sum / $bucket['count']), $bucket['sum']))
            ->values();

        // Sąsiednie przedziały tego samego koloru liczą się raz.
        $distinct = [];

        foreach ($colors as $color) {
            if (collect($distinct)->every(fn (array $kept) => self::distance($kept, $color) > 60)) {
                $distinct[] = $color;
            }
        }

        return $distinct;
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private static function lightness(array $rgb): float
    {
        return (max($rgb) + min($rgb)) / 510;
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private static function saturation(array $rgb): float
    {
        $max = max($rgb) / 255;
        $min = min($rgb) / 255;
        $l = ($max + $min) / 2;

        return $max === $min ? 0.0 : ($max - $min) / (1 - abs(2 * $l - 1));
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $a
     * @param  array{0: int, 1: int, 2: int}  $b
     */
    private static function hueGap(array $a, array $b): float
    {
        $gap = abs(self::hue($a) - self::hue($b));

        return min($gap, 360 - $gap);
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private static function hue(array $rgb): float
    {
        [$r, $g, $b] = array_map(fn (int $v) => $v / 255, $rgb);
        $max = max($r, $g, $b);
        $delta = $max - min($r, $g, $b);

        if ($delta == 0) {
            return 0.0;
        }

        $hue = match ($max) {
            $r => fmod(($g - $b) / $delta, 6),
            $g => ($b - $r) / $delta + 2,
            default => ($r - $g) / $delta + 4,
        };

        return fmod($hue * 60 + 360, 360);
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $a
     * @param  array{0: int, 1: int, 2: int}  $b
     */
    private static function distance(array $a, array $b): float
    {
        return sqrt(($a[0] - $b[0]) ** 2 + ($a[1] - $b[1]) ** 2 + ($a[2] - $b[2]) ** 2);
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $a
     * @param  array{0: int, 1: int, 2: int}  $b
     * @return array{0: int, 1: int, 2: int}
     */
    private static function mix(array $a, array $b, float $amount): array
    {
        return [
            (int) round($a[0] + ($b[0] - $a[0]) * $amount),
            (int) round($a[1] + ($b[1] - $a[1]) * $amount),
            (int) round($a[2] + ($b[2] - $a[2]) * $amount),
        ];
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private static function readableOn(array $rgb): string
    {
        return $rgb[0] * 0.299 + $rgb[1] * 0.587 + $rgb[2] * 0.114 < 150 ? '#ffffff' : '#12141a';
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private static function hex(array $rgb): string
    {
        return sprintf('#%02x%02x%02x', ...$rgb);
    }
}

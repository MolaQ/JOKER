<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Podpowiedź odmiany krótkiej nazwy klubu („Joker” → „Jokera”, „Jokerze”). To tylko punkt wyjścia:
 * nazwy z ruchomym „e” (Orzeł → Orła) albo obce kreator pokazuje do poprawienia.
 */
class ClubNameForms
{
    /** @var array<string, string> końcówka → miejscownik (od najdłuższych) */
    private const LOCATIVE = [
        'st' => 'ście', 'sł' => 'śle', 'ch' => 'chu', 'k' => 'ku', 'g' => 'gu', 'r' => 'rze', 't' => 'cie', 'd' => 'dzie', 'ł' => 'le',
        'n' => 'nie', 's' => 'sie', 'z' => 'zie', 'w' => 'wie', 'm' => 'mie',
        'p' => 'pie', 'b' => 'bie', 'f' => 'fie', 'l' => 'lu', 'j' => 'ju', 'c' => 'cu', 'x' => 'ksie',
    ];

    /**
     * @return array{genitive: string, locative: string}
     */
    public static function suggest(string $name): array
    {
        $name = trim($name);

        if ($name === '' || str_contains($name, ' ')) {
            return ['genitive' => $name, 'locative' => $name];
        }

        $lower = Str::lower($name);

        // Rodzaj żeński na -a: Wisła → Wisły, Wiśle; Polonia → Polonii, Polonii.
        if (str_ends_with($lower, 'ia')) {
            return ['genitive' => mb_substr($name, 0, -1).'i', 'locative' => mb_substr($name, 0, -1).'i'];
        }

        if (str_ends_with($lower, 'a')) {
            $stem = mb_substr($name, 0, -1);
            $genitive = preg_match('/[kg]$/u', $stem) ? $stem.'i' : $stem.'y';

            return ['genitive' => $genitive, 'locative' => self::locativeStem($stem, 'a')];
        }

        // Nazwy kończące się samogłoską (np. Lotto, Dragoni) zwykle się nie odmieniają.
        if (preg_match('/[eiouyó]$/u', $lower)) {
            return ['genitive' => $name, 'locative' => $name];
        }

        return ['genitive' => $name.'a', 'locative' => self::locativeStem($name, '')];
    }

    private static function locativeStem(string $stem, string $ending): string
    {
        foreach (self::LOCATIVE as $from => $to) {
            if (str_ends_with(Str::lower($stem), $from)) {
                $base = mb_substr($stem, 0, -mb_strlen($from));
                $to = $ending === 'a' && in_array($from, ['k', 'g', 'ch', 'l', 'j', 'c'], true) ? ['k' => 'ce', 'g' => 'dze', 'ch' => 'sze', 'l' => 'li', 'j' => 'ji', 'c' => 'cy'][$from] : $to;

                return $base.$to;
            }
        }

        return $stem.'u';
    }
}

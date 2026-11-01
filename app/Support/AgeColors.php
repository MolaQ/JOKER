<?php

namespace App\Support;

use App\Models\Team;
use Illuminate\Support\Str;

/**
 * Kolory kategorii wiekowych w jednym miejscu (Ustawienia → Moduły): te same w obłożeniu sali na treningach
 * i w obłożeniu sektorów na turnieju. Kategoria turnieju dostaje kolor po nazwie (np. „Kadeci” → kadeci).
 */
class AgeColors
{
    /** @var array<string, array{0: string, 1: string}> klucz → [nazwa, domyślny kolor] */
    public const GROUPS = [
        'senior' => ['Seniorzy', '#45a0e0'],
        'junior' => ['Juniorzy', '#f59e0b'],
        'junior_mlodszy' => ['Kadeci (juniorzy młodsi)', '#10b981'],
        'mlodzik' => ['Młodzicy', '#8b5cf6'],
        'mini' => ['Minisiatkówka i dzieci', '#f43f5e'],
    ];

    /** @var array<string, list<string>> słowa w nazwie (bez polskich znaków), sprawdzane po kolei */
    private const WORDS = [
        'junior_mlodszy' => ['juniorzy mlodsi', 'juniorki mlodsze', 'junior mlodszy', 'kadet'],
        'junior' => ['junior'],
        'mlodzik' => ['mlodzi', 'mlodzik', 'trampkar'],
        'mini' => ['mini', 'dzieci', 'zak', 'skrzat'],
        'senior' => ['senior', 'open', 'liga mezczyzn', 'liga kobiet'],
    ];

    public static function key(string $group): string
    {
        return 'color_age_'.$group;
    }

    public static function color(string $group): string
    {
        $default = self::GROUPS[$group][1] ?? '#45a0e0';
        $value = (string) app(SiteSettings::class)->get(self::key($group));

        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $default;
    }

    /**
     * Kategoria wiekowa po nazwie (kategorii turnieju albo drużyny), np. „Kadetki” → junior_mlodszy.
     */
    public static function groupForName(?string $name): ?string
    {
        $name = Str::lower(Str::ascii((string) $name));

        foreach (self::WORDS as $group => $words) {
            foreach ($words as $word) {
                if (str_contains($name, $word)) {
                    return $group;
                }
            }
        }

        return null;
    }

    public static function forTeam(?Team $team): string
    {
        $group = $team?->category && isset(self::GROUPS[$team->category]) ? $team->category : self::groupForName($team?->name);

        return self::color($group ?? 'senior');
    }
}

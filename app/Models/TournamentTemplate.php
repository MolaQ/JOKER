<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Szablon kategorii turnieju (struktura bez drużyn), zob. App\Support\TournamentTemplates.
 */
class TournamentTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'structure',
    ];

    protected function casts(): array
    {
        return [
            'structure' => 'array',
        ];
    }

    /**
     * Krótki opis zawartości: „2 grupy · 16 meczów · 2 dni”.
     */
    public function summary(): string
    {
        $structure = $this->structure ?? [];

        return collect([
            self::count(count($structure['groups'] ?? []), 'grupa', 'grupy', 'grup'),
            self::count(count($structure['matches'] ?? []), 'mecz', 'mecze', 'meczów'),
            self::count(count($structure['days'] ?? []), 'dzień', 'dni', 'dni'),
            self::count(count($structure['teams'] ?? []), 'drużyna', 'drużyny', 'drużyn'),
        ])->filter()->implode(' · ');
    }

    private static function count(int $n, string $one, string $few, string $many): ?string
    {
        if ($n === 0) {
            return null;
        }

        $isFew = $n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20);

        return $n.' '.($n === 1 ? $one : ($isFew ? $few : $many));
    }
}

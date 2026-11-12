<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ustawienia głosowania kibiców w jednym meczu: wyłączenie, wcześniejsze zamknięcie i zawodnicy zdjęci z listy.
 */
class FanMvpPoll extends Model
{
    protected $fillable = [
        'match_type',
        'match_id',
        'disabled',
        'closed_at',
        'excluded',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'disabled' => 'boolean',
            'closed_at' => 'datetime',
            'excluded' => 'array',
            'extra' => 'array',
        ];
    }

    /**
     * @return array<int, int>
     */
    public function excludedIds(): array
    {
        return array_values(array_map('intval', $this->excluded ?? []));
    }

    /**
     * @return array<int, int>
     */
    public function extraIds(): array
    {
        return array_values(array_map('intval', $this->extra ?? []));
    }
}

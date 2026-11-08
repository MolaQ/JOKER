<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nagroda indywidualna wpisana ręcznie w panelu zawodnika (impreza spoza turniejów na stronie).
 * Nagrody z turniejów na stronie biorą się z TournamentAward; w profilu obie są w „Nagrodach indywidualnych”.
 */
class PlayerAward extends Model
{
    protected $fillable = [
        'player_id',
        'name',
        'event',
        'awarded_on',
        'place',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'awarded_on' => 'date',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}

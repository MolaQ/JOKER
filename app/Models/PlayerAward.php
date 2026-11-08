<?php

namespace App\Models;

use App\Support\SearchIndex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nagroda indywidualna zawodnika: wpisana w panelu zawodnika albo kopia wyróżnienia z turnieju na stronie
 * (tournament_award_id). Każdą można w panelu zawodnika poprawić i przestawić; kolejność = kolejność w profilu.
 */
class PlayerAward extends Model
{
    protected $fillable = [
        'player_id',
        'tournament_award_id',
        'name',
        'event',
        'awarded_on',
        'place',
        'note',
        'display_order',
    ];

    protected static function booted(): void
    {
        // Nagrody są w wyszukiwarce.
        static::saved(fn () => SearchIndex::forget());
        static::deleted(fn () => SearchIndex::forget());
    }

    protected function casts(): array
    {
        return [
            'awarded_on' => 'date',
        ];
    }

    public function tournamentAward(): BelongsTo
    {
        return $this->belongsTo(TournamentAward::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}

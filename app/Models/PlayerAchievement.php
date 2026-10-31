<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerAchievement extends Model
{
    protected $fillable = [
        'player_id',
        'tournament_award_id',
        'season',
        'title',
        'display_order',
    ];

    public function tournamentAward(): BelongsTo
    {
        return $this->belongsTo(TournamentAward::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}

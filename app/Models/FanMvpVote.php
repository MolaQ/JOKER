<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Głos kibica na zawodnika meczu (jeden na konto w jednym meczu).
 */
class FanMvpVote extends Model
{
    protected $fillable = [
        'match_type',
        'match_id',
        'user_id',
        'player_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}

<?php

namespace App\Models;

use Database\Factories\RivalTeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RivalTeam extends Model
{
    /** @use HasFactory<RivalTeamFactory> */
    use HasFactory;

    protected $fillable = [
        'full_name',
        'short_name',
        'category',
        'logo_path',
    ];

    public function standings(): MorphMany
    {
        return $this->morphMany(LeagueStanding::class, 'competitor');
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class, 'opponent_team_id');
    }

    public function displayName(): string
    {
        return $this->short_name ?: $this->full_name;
    }
}

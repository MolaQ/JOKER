<?php

namespace App\Models;

use App\Models\Concerns\Likeable;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory, Likeable;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'birth_year',
        'logo_path',
        'trainer_id',
        'display_order',
    ];

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Dodatkowi (pomocniczy) trenerzy drużyny, oprócz trenera głównego.
     */
    public function assistantTrainers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_trainers')->withTimestamps();
    }

    /**
     * Wszyscy trenerzy drużyny (główny + pomocniczy), bez duplikatów.
     *
     * @return Collection<int, User>
     */
    public function allTrainers()
    {
        return collect([$this->trainer])
            ->merge($this->assistantTrainers)
            ->filter()
            ->unique('id')
            ->values();
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function activePlayers(): HasMany
    {
        return $this->hasMany(Player::class)->where('is_active', true);
    }

    public function competitions(): BelongsToMany
    {
        return $this->belongsToMany(Competition::class, 'competition_team')->withTimestamps();
    }

    /**
     * Skład drużyny w danym sezonie (domyślnie sezon aktualny).
     */
    public function seasonRoster(?int $seasonId = null): BelongsToMany
    {
        $seasonId ??= Season::current()->value('id');

        return $this->belongsToMany(Player::class, 'team_player_season')
            ->withPivot(['season_id', 'jersey_number', 'is_captain'])
            ->withPivotValue('season_id', $seasonId)
            ->withTimestamps();
    }

    public function standings(): HasMany
    {
        return $this->hasMany(LeagueStanding::class);
    }
}

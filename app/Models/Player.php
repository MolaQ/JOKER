<?php

namespace App\Models;

use App\Models\Concerns\Likeable;
use App\PlayerPosition;
use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory, Likeable;

    protected $fillable = [
        'first_name',
        'last_name',
        'jersey_number',
        'position',
        'birth_date',
        'height',
        'weight',
        'reach',
        'spike_reach',
        'bio',
        'photo_path',
        'team_id',
        'user_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
            'position' => PlayerPosition::class,
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Wszystkie drużyny, w których zawodnik występuje (może być kilka - np. młodszy
     * zawodnik grywający dodatkowo ze starszym rocznikiem), z podziałem na sezony.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_player_season')
            ->withPivot(['season_id', 'jersey_number', 'is_captain'])
            ->withTimestamps();
    }

    public function teamsForSeason(?int $seasonId = null): BelongsToMany
    {
        $seasonId ??= Season::current()->value('id');

        return $this->teams()->wherePivot('season_id', $seasonId);
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}"
        );
    }

    public function age(): ?int
    {
        return $this->birth_date?->age;
    }
}

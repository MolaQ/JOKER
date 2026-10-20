<?php

namespace App\Models;

use App\Models\Concerns\HasReadableRouteKey;
use App\Models\Concerns\Likeable;
use App\PlayerPosition;
use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory, Likeable;

    use HasReadableRouteKey;

    protected $fillable = [
        'first_name',
        'last_name',
        'jersey_number',
        'position',
        'birth_date',
        'show_birthday',
        'height',
        'weight',
        'reach',
        'spike_reach',
        'block_reach',
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
            'show_birthday' => 'boolean',
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
     * Rodzice (opiekunowie) powiązani z zawodnikiem przez administratora.
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'player_guardian')->withTimestamps();
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(PlayerAchievement::class)->orderBy('display_order')->orderByDesc('season');
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(PlayerChangeRequest::class);
    }

    /**
     * Czy użytkownik może proponować zmiany profilu i zdjęcia: sam zawodnik lub jego rodzic.
     */
    public function isEditableProposalBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($this->user_id === $user->id) {
            return true;
        }

        return $this->guardians()->whereKey($user->id)->exists();
    }

    /**
     * Czy użytkownik może zatwierdzać zmiany tego zawodnika: administrator albo trener jego drużyny.
     */
    public function isReviewableBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isTrainer() && $this->team_id !== null
            && $user->allCoachedTeams()->contains('id', $this->team_id);
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

    protected function routeSlugSource(): string
    {
        return (string) $this->full_name;
    }
}

<?php

namespace App\Models;

use App\BirthdayDisplay;
use App\Models\Concerns\CountsViews;
use App\Models\Concerns\HasReadableRouteKey;
use App\Models\Concerns\Likeable;
use App\Permission;
use App\PlayerPosition;
use App\Support\SiteSettings;
use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

class Player extends Model
{
    use CountsViews;

    /** @use HasFactory<PlayerFactory> */
    use HasFactory, Likeable;

    use HasReadableRouteKey;

    protected static function booted(): void
    {
        // Rodzic traci dostęp, gdy żadne jego dziecko nie jest już aktywne, i odzyskuje go po powrocie dziecka.
        static::saved(function (Player $player) {
            if ($player->wasChanged('is_active')) {
                $player->guardians->each->syncParentActivity();
            }
        });
    }

    protected $fillable = [
        'first_name',
        'last_name',
        'jersey_number',
        'position',
        'extra_positions',
        'birth_date',
        'birthday_display',
        'height',
        'weight',
        'reach',
        'spike_reach',
        'block_reach',
        'bio',
        'interests',
        'social_links',
        'photo_path',
        'team_id',
        'user_id',
        'is_active',
        'image_consent',
        'image_consent_changed_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
            'image_consent' => 'boolean',
            'image_consent_changed_at' => 'datetime',
            'birthday_display' => BirthdayDisplay::class,
            'position' => PlayerPosition::class,
            'extra_positions' => 'array',
            'social_links' => 'array',
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
        if ($user === null || ! $user->is_active) {
            return false;
        }

        if ($this->user_id === $user->id) {
            return true;
        }

        return $this->guardians()->whereKey($user->id)->exists();
    }

    /**
     * Czy użytkownik może zatwierdzać zmiany tego zawodnika: ktoś z prawem zatwierdzania zmian, kto prowadzi drużynę zawodnika (albo wszystkie drużyny).
     */
    public function isReviewableBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasPermission(Permission::Proposals) && $user->managesTeam($this->team_id);
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

    /**
     * Wszystkie pozycje zawodnika: główna i dodatkowe (np. libero i przyjmujący).
     *
     * @return array<int, PlayerPosition>
     */
    public function positions(): array
    {
        $extra = collect($this->extra_positions ?? [])
            ->map(fn (string $value) => PlayerPosition::tryFrom($value))
            ->filter()
            ->reject(fn (PlayerPosition $position) => $position === $this->position);

        return collect([$this->position])->filter()->concat($extra)->unique(fn (PlayerPosition $position) => $position->value)->values()->all();
    }

    /**
     * Pozycje do wyświetlenia, np. „Libero · Przyjmujący”.
     */
    public function positionsLabel(): ?string
    {
        $positions = $this->positions();

        return $positions === [] ? null : collect($positions)->map(fn (PlayerPosition $position) => $position->label())->implode(' · ');
    }

    /**
     * Linki do serwisów ustawionych w „Profile zawodników”, w kolejności z ustawień.
     *
     * @return list<array{key: string, label: string, icon: string, url: string}>
     */
    public function socialProfiles(): array
    {
        $links = (array) ($this->social_links ?? []);

        return collect(app(SiteSettings::class)->playerSocialPlatforms())
            ->map(fn (array $platform, string $key) => $platform + ['key' => $key, 'url' => (string) ($links[$key] ?? '')])
            ->filter(fn (array $platform) => preg_match('#^https?://#i', $platform['url']))
            ->values()
            ->all();
    }

    /**
     * Czy jest kapitanem swojej drużyny w bieżącym sezonie.
     */
    public function isCaptain(): bool
    {
        $seasonId = Season::current()->value('id');

        return $seasonId && $this->team_id
            && $this->teams()->wherePivot('season_id', $seasonId)->where('teams.id', $this->team_id)->wherePivot('is_captain', true)->exists();
    }

    /**
     * Wpis w kadrze macierzystej drużyny na bieżący sezon (numer i opaska kapitana), bez ruszania innych drużyn.
     */
    public function syncHomeRoster(?bool $captain = null): void
    {
        $seasonId = Season::current()->value('id');

        if (! $seasonId || ! $this->team_id) {
            return;
        }

        $captain ??= $this->isCaptain();
        $this->teams()->wherePivot('season_id', $seasonId)->detach($this->team_id);
        $this->teams()->attach($this->team_id, ['season_id' => $seasonId, 'jersey_number' => $this->jersey_number, 'is_captain' => $captain]);
    }

    public function age(): ?int
    {
        return $this->birth_date?->age;
    }

    protected function routeSlugSource(): string
    {
        return (string) $this->full_name;
    }

    /**
     * Zawodnicy, których użytkownik może edytować: z prowadzonych drużyn (także grający w nich dodatkowo).
     */
    /**
     * Zawodnicy drużyny: z tej drużyny macierzystej albo dopisani do jej składu w bieżącym sezonie
     * (np. junior grający też w seniorach).
     */
    public function scopeInTeam($query, int|string $teamId, ?int $seasonId = null)
    {
        $seasonId ??= Season::current()->value('id');

        return $query->where(fn ($inner) => $inner->where('players.team_id', $teamId)
            ->when($seasonId, fn ($roster) => $roster->orWhereExists(fn ($pivot) => $pivot->selectRaw('1')->from('team_player_season')
                ->whereColumn('team_player_season.player_id', 'players.id')
                ->where('team_player_season.team_id', $teamId)
                ->where('team_player_season.season_id', $seasonId))));
    }

    public function scopeManagedBy($query, User $user)
    {
        $teamIds = $user->managedTeamIds();

        if ($teamIds === null) {
            return $query;
        }

        return $query->where(fn ($players) => $players
            ->whereIn('team_id', $teamIds)
            ->orWhereHas('teams', fn ($teams) => $teams->whereIn('teams.id', $teamIds)));
    }

    public function photoTags(): MorphMany
    {
        return $this->morphMany(PhotoTag::class, 'taggable');
    }

    /**
     * Zdjęcia z opublikowanych galerii, na których zawodnik jest oznaczony
     * (puste, gdy nie ma zgody na wizerunek).
     *
     * @return Collection<int, GalleryPhoto>
     */
    public function taggedPhotos(int $limit = 24): Collection
    {
        if (! $this->image_consent) {
            return collect();
        }

        return GalleryPhoto::query()
            ->whereHas('tags', fn ($tags) => $tags->approved()->where('taggable_type', $this->getMorphClass())->where('taggable_id', $this->id))
            ->whereHas('gallery', fn ($gallery) => $gallery->published())
            ->with(['gallery', 'tags.taggable'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}

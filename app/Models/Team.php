<?php

namespace App\Models;

use App\Models\Concerns\CountsViews;
use App\Models\Concerns\HasSlugRouteKey;
use App\Models\Concerns\Likeable;
use App\Support\SiteSettings;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Team extends Model
{
    use CountsViews;

    /** @use HasFactory<TeamFactory> */
    use HasFactory, Likeable;

    use HasSlugRouteKey;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'birth_year',
        'logo_path',
        'photo_path',
        'trainer_id',
        'display_order',
    ];

    /**
     * Logo drużyny: własne z panelu, a bez niego herb klubu (z ustawień strony albo domyślny).
     */
    public function logoPath(): string
    {
        return $this->logo_path ?: (app(SiteSettings::class)->get('logo') ?: asset('img/logo-256.webp'));
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Koordynatorzy drużyny: przyjmują rodziców i zatwierdzają zgłoszenia drużyny.
     */
    public function coordinators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_coordinators')->withTimestamps();
    }

    /**
     * Dodatkowi (pomocniczy) trenerzy drużyny, oprócz trenera głównego.
     */
    public function assistantTrainers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_trainers')->withPivot('role')->withTimestamps();
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

    /**
     * Sztab drużyny z funkcjami: trener główny na początku, potem pozostali członkowie sztabu.
     *
     * @return Collection<int, array{user: User, role: string}>
     */
    public function staff(): Collection
    {
        $staff = collect();

        if ($this->trainer) {
            $staff->push(['user' => $this->trainer, 'role' => __('Trener główny')]);
        }

        foreach ($this->assistantTrainers as $member) {
            if ($member->id === $this->trainer_id) {
                continue;
            }

            $staff->push(['user' => $member, 'role' => __($member->pivot->role ?: 'Asystent trenera')]);
        }

        return $staff;
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class)->ordered();
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
        return $this->belongsToMany(Competition::class, 'competition_team')->withPivot(['external_name', 'display_name', 'display_suffix'])->withTimestamps();
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

    /**
     * Drużyny, którymi użytkownik może zarządzać (trener tylko swoimi).
     */
    public function scopeManagedBy($query, User $user)
    {
        $teamIds = $user->managedTeamIds();

        return $teamIds === null ? $query : $query->whereIn('teams.id', $teamIds);
    }
}

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
        'birth_year_to',
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
     * Rocznik albo zakres roczników, np. „2010” lub „2010–2011”; null dla seniorów.
     */
    public function birthYears(): ?string
    {
        if (! $this->birth_year) {
            return null;
        }

        return $this->birth_year_to && $this->birth_year_to !== $this->birth_year
            ? min($this->birth_year, $this->birth_year_to).'–'.max($this->birth_year, $this->birth_year_to)
            : (string) $this->birth_year;
    }

    /**
     * „Rocznik 2010” albo „Roczniki 2010–2011”; null dla seniorów.
     */
    public function birthYearsLabel(): ?string
    {
        $years = $this->birthYears();

        return $years === null ? null : (str_contains($years, '–') ? __('Roczniki :years', ['years' => $years]) : __('Rocznik :year', ['year' => $years]));
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

    /**
     * Liczba aktywnych zawodników jako active_players_count, razem z dopisanymi do składu z innej drużyny.
     * W panelu ($activeOnly = false) liczymy też nieaktywnych, jak na liście zawodników.
     */
    public function scopeWithActivePlayersCount($query, bool $activeOnly = true)
    {
        $seasonId = Season::current()->value('id');

        if ($query->getQuery()->columns === null) {
            $query->select('teams.*');
        }

        return $query->selectSub(fn ($players) => $players->from('players')->selectRaw('count(*)')->when($activeOnly, fn ($active) => $active->where('players.is_active', true))
            ->where(fn ($inner) => $inner->whereColumn('players.team_id', 'teams.id')
                ->when($seasonId, fn ($roster) => $roster->orWhereExists(fn ($pivot) => $pivot->selectRaw('1')->from('team_player_season')
                    ->whereColumn('team_player_season.player_id', 'players.id')
                    ->whereColumn('team_player_season.team_id', 'teams.id')
                    ->where('team_player_season.season_id', $seasonId)))), 'active_players_count');
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

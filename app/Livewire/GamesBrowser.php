<?php

namespace App\Livewire;

use App\BirthdayDisplay;
use App\Models\Game;
use App\Models\MiniTrip;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Support\SearchIndex;
use App\Support\SiteSettings;
use App\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Wszystkie mecze klubu w jednym miejscu: filtry i wyszukiwanie bez przeładowania strony.
 */
class GamesBrowser extends Component
{
    public const PERIODS = ['all' => 'Wszystkie', 'upcoming' => 'Nadchodzące', 'results' => 'Wyniki'];

    /** Wartości filtra rozgrywek obok id lig. */
    public const FILTER_TOURNAMENTS = 'turnieje';

    public const FILTER_FRIENDLIES = 'sparingi';

    public const FILTER_MINI = 'minisiatkowka';

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(as: 'team', except: '')]
    public string $team = '';

    #[Url(as: 'competition', except: '')]
    public string $competition = '';

    #[Url(as: 'show', except: 'all')]
    public string $period = 'all';

    #[Url(as: 'venue', except: '')]
    public string $place = '';

    #[Url(as: 'season', except: '')]
    public string $season = '';

    public function setPeriod(string $period): void
    {
        $this->period = array_key_exists($period, self::PERIODS) ? $period : 'all';
    }

    public function toggleTeam(string $slug): void
    {
        $this->team = $this->team === $slug ? '' : $slug;
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'team', 'competition', 'place');
    }

    #[Computed]
    public function selectedSeason(): ?Season
    {
        return Season::find($this->season) ?? Season::current()->first();
    }

    /**
     * Mecze sezonu (jedno zapytanie); filtrowanie odbywa się w pamięci, więc jest natychmiastowe
     * i ignoruje polskie znaki ("lodz" znajdzie "Łódź").
     *
     * @return Collection<int, Game>
     */
    #[Computed]
    public function seasonGames(): Collection
    {
        return Game::with(['team', 'competition.teams', 'opponentTeam', 'articles', 'tournament'])
            ->when($this->selectedSeason, fn ($query, Season $season) => $query->where('season_id', $season->id))
            // Odwołane mecze (np. rywal wycofał się z rozgrywek) nie trafiają do kalendarza.
            ->where('status', '!=', 'cancelled')
            ->orderBy('game_date')
            ->get();
    }

    /**
     * Turnieje sezonu z naszymi drużynami.
     *
     * @return Collection<int, Tournament>
     */
    #[Computed]
    public function seasonTournaments(): Collection
    {
        return Tournament::with('teams')
            ->when($this->selectedSeason, fn ($query, Season $season) => $query->where('season_id', $season->id))
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Turnieje minisiatkówki w sezonie (wyjazdowe i u nas) (gdy moduł jest włączony), z miejscami naszych zespołów.
     *
     * @return Collection<int, MiniTrip>
     */
    #[Computed]
    public function seasonMiniTrips(): Collection
    {
        $site = app(SiteSettings::class);

        if (! $site['module_mini'] || ! $site['mini_in_calendar']) {
            return collect();
        }

        return MiniTrip::with(['teams.group', 'teams.players'])
            ->when($this->selectedSeason, fn ($query, Season $season) => $query->whereBetween('starts_on', [$season->start_date, $season->end_date]))
            ->orderBy('starts_on')
            ->get();
    }

    /**
     * Turnieje minisiatkówki pasujące do filtrów (filtr drużyny i ligi je ukrywa; u siebie = w mieście klubu).
     *
     * @return Collection<int, MiniTrip>
     */
    private function filteredMiniTrips(): Collection
    {
        if (($this->competition !== '' && $this->competition !== self::FILTER_MINI) || $this->team !== '') {
            return collect();
        }

        $terms = array_filter(explode(' ', SearchIndex::normalize($this->search)));

        return $this->seasonMiniTrips
            ->when($this->place === 'home', fn (Collection $trips) => $trips->filter->isHome())
            ->when($this->place === 'away', fn (Collection $trips) => $trips->reject->isHome())
            ->when($terms, fn (Collection $trips) => $trips->filter(function (MiniTrip $trip) use ($terms) {
                $haystack = SearchIndex::normalize(implode(' ', ['minisiatkowka mini', $trip->title, $trip->city, $trip->venue, $trip->teams->pluck('name')->implode(' '), $trip->starts_on->format('d.m.Y')]));

                return collect($terms)->every(fn (string $term) => str_contains($haystack, $term));
            }));
    }

    /**
     * Turnieje pasujące do filtrów (rozgrywki „Turnieje”, drużyna, dom/wyjazd, wyszukiwanie).
     *
     * @return Collection<int, Tournament>
     */
    private function filteredTournaments(): Collection
    {
        if ($this->competition !== '' && $this->competition !== self::FILTER_TOURNAMENTS) {
            return collect();
        }

        $terms = array_filter(explode(' ', SearchIndex::normalize($this->search)));

        return $this->seasonTournaments
            ->when($this->team !== '', fn (Collection $tournaments) => $tournaments->filter(fn (Tournament $tournament) => $tournament->teams->contains('slug', $this->team)))
            ->when($this->place === 'home', fn (Collection $tournaments) => $tournaments->where('is_organizer', true))
            ->when($this->place === 'away', fn (Collection $tournaments) => $tournaments->where('is_organizer', false))
            ->when($terms, fn (Collection $tournaments) => $tournaments->filter(function (Tournament $tournament) use ($terms) {
                $haystack = SearchIndex::normalize(implode(' ', [
                    'turniej',
                    $tournament->fullName(),
                    $tournament->city,
                    $tournament->venue,
                    $tournament->teams->pluck('name')->implode(' '),
                    $tournament->start_date->format('d.m.Y'),
                ]));

                return collect($terms)->every(fn (string $term) => str_contains($haystack, $term));
            }));
    }

    /**
     * @return Collection<int, Game>
     */
    private function filteredGames(): Collection
    {
        $terms = array_filter(explode(' ', SearchIndex::normalize($this->search)));

        return $this->seasonGames
            ->when($this->team !== '', fn (Collection $games) => $games->filter(fn (Game $game) => $game->team?->slug === $this->team))
            ->when($this->competition !== '', fn (Collection $games) => match ($this->competition) {
                self::FILTER_MINI => collect(),
                self::FILTER_TOURNAMENTS => $games->whereNotNull('tournament_id'),
                self::FILTER_FRIENDLIES => $games->where('kind', Game::KIND_FRIENDLY),
                default => $games->where('competition_id', (int) $this->competition),
            })
            ->when($this->place === 'home', fn (Collection $games) => $games->where('is_home', true))
            ->when($this->place === 'away', fn (Collection $games) => $games->where('is_home', false))
            ->when($terms, fn (Collection $games) => $games->filter(function (Game $game) use ($terms) {
                $haystack = SearchIndex::normalize(implode(' ', [
                    $game->team?->name,
                    $game->opponentDisplayName(),
                    $game->opponentTeam?->short_name,
                    $game->competitionLabel(),
                    $game->league,
                    $game->venue,
                    $game->round_label,
                    $game->is_home ? 'dom u siebie' : 'wyjazd',
                    $game->game_date?->format('d.m.Y'),
                    $game->game_date?->translatedFormat('j F l'),
                    $game->isFinished() ? "wynik {$game->home_score}:{$game->away_score}" : 'nadchodzacy',
                ]));

                return collect($terms)->every(fn (string $term) => str_contains($haystack, $term));
            }));
    }

    public function render(): View
    {
        $filtered = $this->filteredGames();

        $periodCounts = [
            'upcoming' => $filtered->filter(fn (Game $game) => ! $game->isFinished())->count(),
            'results' => $filtered->filter(fn (Game $game) => $game->isFinished())->count(),
            'all' => $filtered->count(),
        ];

        $games = match ($this->period) {
            'results' => $filtered->filter(fn (Game $game) => $game->isFinished())->sortByDesc('game_date'),
            'all' => $filtered,
            default => $filtered->reject(fn (Game $game) => $game->isFinished()),
        };

        $today = now()->startOfDay();
        $tournaments = $this->filteredTournaments()->filter(fn (Tournament $tournament) => match ($this->period) {
            'results' => $tournament->lastDay()->lt($today),
            'all' => true,
            default => $tournament->lastDay()->gte($today),
        });

        $miniTrips = $this->filteredMiniTrips()->filter(fn (MiniTrip $trip) => match ($this->period) {
            'results' => $trip->isPast(),
            'all' => true,
            default => ! $trip->isPast(),
        });

        return view('livewire.games-browser', [
            'months' => $this->calendarMonths($games, $tournaments, $miniTrips, $this->search === '' && $this->competition === '' && $this->place === '', $this->period === 'results'),
            'total' => $games->count(),
            'periodCounts' => $periodCounts,
            'teams' => $this->seasonGames->pluck('team')->filter()->unique('id')->sortBy('display_order')->values(),
            'competitions' => $this->seasonGames->pluck('competition')->filter()->unique('id')->sortBy('name')->values(),
            'hasTournaments' => $this->seasonTournaments->isNotEmpty() || $this->seasonGames->whereNotNull('tournament_id')->isNotEmpty(),
            'hasMini' => $this->seasonMiniTrips->isNotEmpty(),
            'hasFriendlies' => $this->seasonGames->where('kind', Game::KIND_FRIENDLY)->isNotEmpty(),
            'seasons' => Season::orderByDesc('start_date')->get(),
            'hasFilters' => $this->search !== '' || $this->team !== '' || $this->competition !== '' || $this->place !== '',
        ]);
    }

    /**
     * Miesiące jak kartki kalendarza ściennego: tygodnie od poniedziałku, w każdym dniu jego mecze.
     *
     * Urodziny pokazujemy tylko bez wyszukiwania i filtrów meczowych (filtr drużyny działa).
     *
     * @param  Collection<int, Game>  $games
     * @param  Collection<int, Tournament>  $tournaments
     * @return Collection<int, array{key: string, month: Carbon, games: Collection<int, Game>, tournaments: Collection<int, Tournament>, birthdays: Collection<int, array{name: string, age: int, ageLabel: string, role: string, date: Carbon, url: ?string, showAge: bool}>, weeks: array<int, array<int, array{date: Carbon, inMonth: bool, isToday: bool, games: Collection<int, Game>, tournaments: Collection<int, Tournament>, birthdays: Collection<int, array{name: string, age: int, ageLabel: string, role: string, date: Carbon, url: ?string, showAge: bool}>}>>}>
     */
    private function calendarMonths(Collection $games, Collection $tournaments, Collection $miniTrips, bool $withBirthdays = true, bool $newestFirst = false): Collection
    {
        $birthdays = $withBirthdays ? $this->birthdays() : collect();
        $gamesByMonth = $games->filter(fn (Game $game) => $game->game_date)->groupBy(fn (Game $game) => $game->game_date->format('Y-m'));
        $tournamentMonths = $tournaments->flatMap(fn (Tournament $tournament) => collect(Carbon::parse($tournament->start_date)->toPeriod($tournament->lastDay()))->map(fn (Carbon $day) => $day->format('Y-m')))->unique();
        $miniMonths = $miniTrips->map(fn (MiniTrip $trip) => $trip->starts_on->format('Y-m'));
        $keys = $gamesByMonth->keys()->concat($tournamentMonths)->concat($miniMonths)->unique()->sort()->values();

        return ($newestFirst ? $keys->reverse() : $keys)
            ->map(function (string $key) use ($birthdays, $gamesByMonth, $tournaments, $miniTrips) {
                $monthGames = $gamesByMonth->get($key, collect());
                $month = Carbon::createFromFormat('Y-m-d', $key.'-01')->startOfDay();
                $byDay = $monthGames->sortBy('game_date')->groupBy(fn (Game $game) => $game->game_date->toDateString());
                $monthTournaments = $tournaments
                    ->filter(fn (Tournament $tournament) => $tournament->start_date->lte($month->copy()->endOfMonth()) && $tournament->lastDay()->gte($month))
                    ->sortBy('start_date')
                    ->values();
                $monthMini = $miniTrips->filter(fn (MiniTrip $trip) => $trip->starts_on->format('Y-m') === $key)->sortBy('starts_on')->values();
                $monthBirthdays = $birthdays
                    ->filter(fn (array $person) => $person['birth_date']->month === $month->month)
                    ->map(fn (array $person) => [
                        'name' => $person['name'],
                        'role' => $person['role'],
                        'url' => $person['url'],
                        'showAge' => $person['showAge'],
                        'age' => $age = $month->year - $person['birth_date']->year,
                        'ageLabel' => self::yearsLabel($age),
                        'date' => $month->copy()->day(min($person['birth_date']->day, $month->daysInMonth)),
                    ])
                    ->filter(fn (array $person) => $person['age'] > 0)
                    ->sortBy('date')
                    ->values();
                $days = [];

                for ($day = $month->copy()->startOfWeek(Carbon::MONDAY); $day->lte($month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY)); $day->addDay()) {
                    $days[] = [
                        'date' => $day->copy(),
                        'inMonth' => $day->month === $month->month,
                        'isToday' => $day->isToday(),
                        'games' => $byDay->get($day->toDateString(), collect()),
                        'tournaments' => $day->month === $month->month ? $monthTournaments->filter(fn (Tournament $tournament) => $day->between($tournament->start_date, $tournament->lastDay()->endOfDay()))->values() : collect(),
                        'mini' => $day->month === $month->month ? $monthMini->filter(fn (MiniTrip $trip) => $trip->starts_on->isSameDay($day))->values() : collect(),
                        'birthdays' => $day->month === $month->month ? $monthBirthdays->filter(fn (array $person) => $person['date']->isSameDay($day))->values() : collect(),
                    ];
                }

                return ['key' => $key, 'month' => $month, 'games' => $monthGames, 'tournaments' => $monthTournaments, 'mini' => $monthMini, 'birthdays' => $monthBirthdays, 'weeks' => array_chunk($days, 7)];
            })
            ->values();
    }

    /**
     * „1 rok”, „17 lat”, „22 lata” - poprawna odmiana wieku (po angielsku „1 year”, „17 years”).
     */
    public static function yearsLabel(int $years): string
    {
        if (app()->getLocale() === 'en') {
            return $years.' '.($years === 1 ? 'year' : 'years');
        }

        $word = match (true) {
            $years === 1 => 'rok',
            $years % 10 >= 2 && $years % 10 <= 4 && ($years % 100 < 10 || $years % 100 >= 20) => 'lata',
            default => 'lat',
        };

        return $years.' '.$word;
    }

    /**
     * Osoby z widocznymi urodzinami: zawodnicy oraz sztab i działacze. Każdy ma w panelu
     * tryb: ukryte, sam torcik albo torcik z wiekiem (domyślnie z wiekiem).
     *
     * @return Collection<int, array{name: string, role: string, birth_date: Carbon, url: ?string, showAge: bool}>
     */
    #[Computed]
    public function birthdays(): Collection
    {
        $team = $this->team !== '' ? Team::where('slug', $this->team)->first() : null;

        $players = Player::query()
            ->where('is_active', true)
            ->where('birthday_display', '!=', BirthdayDisplay::Hidden->value)
            ->whereNotNull('birth_date')
            ->when($team, fn ($query) => $query->where('team_id', $team->id))
            ->get()
            ->map(fn (Player $player) => [
                'name' => $player->full_name,
                'role' => 'zawodnik',
                'birth_date' => $player->birth_date,
                'url' => route('players.show', $player),
                'showAge' => $player->birthday_display === BirthdayDisplay::Age,
            ]);

        $staffIds = $team ? $team->allTrainers()->pluck('id') : null;

        $staff = User::query()
            ->where('birthday_display', '!=', BirthdayDisplay::Hidden->value)
            ->whereNotNull('birth_date')
            ->when($staffIds, fn ($query) => $query->whereIn('id', $staffIds), fn ($query) => $query->where(fn ($staffQuery) => $staffQuery
                ->withRole(UserRole::Trainer, UserRole::Staff)
                ->orWhereHas('headCoachTeams')
                ->orWhereHas('assistantTeams')))
            ->get()
            ->map(fn (User $user) => [
                'name' => $user->name,
                'role' => $user->isTrainer() ? 'trener' : 'sztab',
                'birth_date' => $user->birth_date,
                'url' => route('trainers.show', $user),
                'showAge' => $user->birthday_display === BirthdayDisplay::Age,
            ]);

        return $players->concat($staff)->values();
    }
}

<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Support\SearchIndex;
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
    public const PERIODS = ['nadchodzace' => 'Nadchodzące', 'wyniki' => 'Wyniki', 'wszystkie' => 'Wszystkie'];

    #[Url(as: 'szukaj', except: '')]
    public string $search = '';

    #[Url(as: 'druzyna', except: '')]
    public string $team = '';

    #[Url(as: 'rozgrywki', except: '')]
    public string $competition = '';

    #[Url(as: 'pokaz', except: 'nadchodzace')]
    public string $period = 'nadchodzace';

    #[Url(as: 'miejsce', except: '')]
    public string $place = '';

    #[Url(as: 'sezon', except: '')]
    public string $season = '';

    public function setPeriod(string $period): void
    {
        $this->period = array_key_exists($period, self::PERIODS) ? $period : 'nadchodzace';
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
        return Game::with(['team', 'competition.teams', 'opponentTeam', 'articles'])
            ->when($this->selectedSeason, fn ($query, Season $season) => $query->where('season_id', $season->id))
            ->orderBy('game_date')
            ->get();
    }

    /**
     * @return Collection<int, Game>
     */
    private function filteredGames(): Collection
    {
        $terms = array_filter(explode(' ', SearchIndex::normalize($this->search)));

        return $this->seasonGames
            ->when($this->team !== '', fn (Collection $games) => $games->filter(fn (Game $game) => $game->team?->slug === $this->team))
            ->when($this->competition !== '', fn (Collection $games) => $games->where('competition_id', (int) $this->competition))
            ->when($this->place === 'dom', fn (Collection $games) => $games->where('is_home', true))
            ->when($this->place === 'wyjazd', fn (Collection $games) => $games->where('is_home', false))
            ->when($terms, fn (Collection $games) => $games->filter(function (Game $game) use ($terms) {
                $haystack = SearchIndex::normalize(implode(' ', [
                    $game->team?->name,
                    $game->opponentDisplayName(),
                    $game->opponentTeam?->short_name,
                    $game->competition?->name,
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
            'nadchodzace' => $filtered->filter(fn (Game $game) => ! $game->isFinished())->count(),
            'wyniki' => $filtered->filter(fn (Game $game) => $game->isFinished())->count(),
            'wszystkie' => $filtered->count(),
        ];

        $games = match ($this->period) {
            'wyniki' => $filtered->filter(fn (Game $game) => $game->isFinished())->sortByDesc('game_date'),
            'wszystkie' => $filtered,
            default => $filtered->reject(fn (Game $game) => $game->isFinished()),
        };

        return view('livewire.games-browser', [
            'months' => $this->calendarMonths($games, $this->search === '' && $this->competition === '' && $this->place === ''),
            'total' => $games->count(),
            'periodCounts' => $periodCounts,
            'teams' => $this->seasonGames->pluck('team')->filter()->unique('id')->sortBy('display_order')->values(),
            'competitions' => $this->seasonGames->pluck('competition')->filter()->unique('id')->sortBy('name')->values(),
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
     * @return Collection<int, array{key: string, month: Carbon, games: Collection<int, Game>, birthdays: Collection<int, array{name: string, age: int, ageLabel: string, role: string, date: Carbon, url: ?string}>, weeks: array<int, array<int, array{date: Carbon, inMonth: bool, isToday: bool, games: Collection<int, Game>, birthdays: Collection<int, array{name: string, age: int, ageLabel: string, role: string, date: Carbon, url: ?string}>}>>}>
     */
    private function calendarMonths(Collection $games, bool $withBirthdays = true): Collection
    {
        $birthdays = $withBirthdays ? $this->birthdays() : collect();

        return $games->filter(fn (Game $game) => $game->game_date)
            ->groupBy(fn (Game $game) => $game->game_date->format('Y-m'))
            ->map(function (Collection $monthGames, string $key) use ($birthdays) {
                $month = Carbon::createFromFormat('Y-m-d', $key.'-01')->startOfDay();
                $byDay = $monthGames->sortBy('game_date')->groupBy(fn (Game $game) => $game->game_date->toDateString());
                $monthBirthdays = $birthdays
                    ->filter(fn (array $person) => $person['birth_date']->month === $month->month)
                    ->map(fn (array $person) => [
                        'name' => $person['name'],
                        'role' => $person['role'],
                        'url' => $person['url'],
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
                        'birthdays' => $day->month === $month->month ? $monthBirthdays->filter(fn (array $person) => $person['date']->isSameDay($day))->values() : collect(),
                    ];
                }

                return ['key' => $key, 'month' => $month, 'games' => $monthGames, 'birthdays' => $monthBirthdays, 'weeks' => array_chunk($days, 7)];
            })
            ->values();
    }

    /**
     * „1 rok”, „17 lat”, „22 lata” - poprawna odmiana wieku.
     */
    public static function yearsLabel(int $years): string
    {
        $word = match (true) {
            $years === 1 => 'rok',
            $years % 10 >= 2 && $years % 10 <= 4 && ($years % 100 < 10 || $years % 100 >= 20) => 'lata',
            default => 'lat',
        };

        return $years.' '.$word;
    }

    /**
     * Osoby z widocznymi urodzinami: zawodnicy (przełącznik domyślnie włączony) oraz sztab
     * i działacze, którym administrator włączył pokazywanie urodzin.
     *
     * @return Collection<int, array{name: string, role: string, birth_date: Carbon, url: ?string}>
     */
    #[Computed]
    public function birthdays(): Collection
    {
        $team = $this->team !== '' ? Team::where('slug', $this->team)->first() : null;

        $players = Player::query()
            ->where('is_active', true)
            ->where('show_birthday', true)
            ->whereNotNull('birth_date')
            ->when($team, fn ($query) => $query->where('team_id', $team->id))
            ->get()
            ->map(fn (Player $player) => [
                'name' => $player->full_name,
                'role' => 'zawodnik',
                'birth_date' => $player->birth_date,
                'url' => route('players.show', $player),
            ]);

        $staffIds = $team ? $team->allTrainers()->pluck('id') : null;

        $staff = User::query()
            ->where('show_birthday', true)
            ->whereNotNull('birth_date')
            ->when($staffIds, fn ($query) => $query->whereIn('id', $staffIds))
            ->get()
            ->map(fn (User $user) => [
                'name' => $user->name,
                'role' => $user->isTrainer() ? 'trener' : 'sztab',
                'birth_date' => $user->birth_date,
                'url' => null,
            ]);

        return $players->concat($staff)->values();
    }
}

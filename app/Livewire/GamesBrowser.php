<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Season;
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
        return Game::with(['team', 'competition', 'opponentTeam'])
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
            'months' => $this->calendarMonths($games),
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
     * @param  Collection<int, Game>  $games
     * @return Collection<int, array{key: string, month: Carbon, games: Collection<int, Game>, weeks: array<int, array<int, array{date: Carbon, inMonth: bool, isToday: bool, games: Collection<int, Game>}>>}>
     */
    private function calendarMonths(Collection $games): Collection
    {
        return $games->filter(fn (Game $game) => $game->game_date)
            ->groupBy(fn (Game $game) => $game->game_date->format('Y-m'))
            ->map(function (Collection $monthGames, string $key) {
                $month = Carbon::createFromFormat('Y-m-d', $key.'-01')->startOfDay();
                $byDay = $monthGames->sortBy('game_date')->groupBy(fn (Game $game) => $game->game_date->toDateString());
                $days = [];

                for ($day = $month->copy()->startOfWeek(Carbon::MONDAY); $day->lte($month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY)); $day->addDay()) {
                    $days[] = [
                        'date' => $day->copy(),
                        'inMonth' => $day->month === $month->month,
                        'isToday' => $day->isToday(),
                        'games' => $byDay->get($day->toDateString(), collect()),
                    ];
                }

                return ['key' => $key, 'month' => $month, 'games' => $monthGames, 'weeks' => array_chunk($days, 7)];
            })
            ->values();
    }
}

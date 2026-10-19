<?php

namespace App\Livewire;

use App\Models\Competition;
use App\Models\LeagueStanding;
use App\Models\Season;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Tabele ligowe: przełączanie rozgrywek bez przeładowania strony.
 */
class StandingsBrowser extends Component
{
    /** Rozgrywki w adresie jako „6-iii-liga-mezczyzn” (czytelne dla ludzi i Google). */
    #[Url(as: 'rozgrywki', except: '')]
    public string $competition = '';

    /**
     * Stare linki „/standings?competition=6” też otwierają właściwą tabelę.
     */
    public function mount(): void
    {
        if ($this->competition === '' && request()->filled('competition')) {
            $this->competition = (string) request()->query('competition');
        }
    }

    public function select(string $routeKey): void
    {
        $this->competition = $routeKey;
    }

    /**
     * @return Collection<int, Competition>
     */
    #[Computed]
    public function competitions(): Collection
    {
        return Competition::with('competitionLevel')
            ->where('season_id', Season::current()->value('id'))
            ->withCount('standings')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function selected(): ?Competition
    {
        $id = (int) $this->competition;

        return $this->competitions->firstWhere('id', $id)
            ?? $this->competitions->firstWhere('standings_count', '>', 0)
            ?? $this->competitions->first();
    }

    public function render(): View
    {
        $standings = $this->selected
            ? LeagueStanding::with('competitor')->where('competition_id', $this->selected->id)->orderBy('group_name')->orderBy('position')->get()
            : collect();

        return view('livewire.standings-browser', [
            'standings' => $standings,
            'groups' => $standings->groupBy(fn (LeagueStanding $standing) => (string) $standing->group_name),
        ]);
    }
}

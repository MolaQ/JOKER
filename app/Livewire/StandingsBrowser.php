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

    /** Etap rozgrywek (np. „Etap 2”) - młodzież gra w kilku etapach. */
    #[Url(as: 'etap', except: '')]
    public string $stage = '';

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
        $this->stage = '';
    }

    public function selectStage(string $stage): void
    {
        $this->stage = $stage;
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
            ? LeagueStanding::with(['competitor', 'competition.teams'])->where('competition_id', $this->selected->id)->orderBy('position')->get()
            : collect();

        // Najnowszy etap pierwszy („Etap 2” przed „Etap 1”).
        $stages = $standings->pluck('stage_name')->unique()->sortByDesc(fn (?string $stage) => (int) preg_replace('/\D/', '', (string) $stage))->values();
        $currentStage = $stages->contains($this->stage) ? $this->stage : $stages->first(fn (?string $stage) => $standings->where('stage_name', $stage)->contains(fn (LeagueStanding $row) => $row->isOwnTeam())) ?? $stages->first();

        // Grupy z naszą drużyną na górze i rozwinięte, pozostałe zwinięte.
        $groups = $standings->where('stage_name', $currentStage)
            ->groupBy(fn (LeagueStanding $standing) => (string) $standing->group_name)
            ->map(fn ($rows, string $name) => ['name' => $name, 'rows' => $rows->values(), 'ours' => $rows->contains(fn (LeagueStanding $row) => $row->isOwnTeam())])
            ->sortBy(fn (array $group) => ($group['ours'] ? '0' : '1').$group['name'])
            ->values();

        return view('livewire.standings-browser', [
            'standings' => $standings,
            'stages' => $stages->filter()->values(),
            'currentStage' => $currentStage,
            'groups' => $groups,
        ]);
    }
}

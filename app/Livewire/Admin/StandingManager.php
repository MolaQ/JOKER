<?php

namespace App\Livewire\Admin;

use App\Models\Competition;
use App\Models\LeagueStanding;
use App\Models\RivalTeam;
use App\Models\Team;
use App\Services\StandingsCalculator;
use Livewire\Component;

class StandingManager extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public ?int $competition_id = null;

    public string $competitor_type = 'team';

    public ?int $competitor_id = null;

    public int $position = 0;

    public int $played = 0;

    public int $won = 0;

    public int $lost = 0;

    public int $sets_won = 0;

    public int $sets_lost = 0;

    public int $points_for = 0;

    public int $points_against = 0;

    public int $points = 0;

    public bool $is_manual_override = true;

    public ?int $competitionFilter = null;

    public ?string $flashType = null;

    public ?string $flashMessage = null;

    protected function rules(): array
    {
        return [
            'competition_id' => 'required|exists:competitions,id',
            'competitor_type' => 'required|in:team,rival_team',
            'competitor_id' => 'required|integer|min:1',
            'position' => 'required|integer|min:0',
            'played' => 'required|integer|min:0',
            'won' => 'required|integer|min:0',
            'lost' => 'required|integer|min:0',
            'sets_won' => 'required|integer|min:0',
            'sets_lost' => 'required|integer|min:0',
            'points_for' => 'required|integer|min:0',
            'points_against' => 'required|integer|min:0',
            'points' => 'required|integer|min:0',
            'is_manual_override' => 'boolean',
        ];
    }

    public function mount(): void
    {
        $this->competitionFilter = Competition::orderBy('season_id', 'desc')->value('id');
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->competition_id = $this->competitionFilter;
        $this->flashType = null;
        $this->flashMessage = null;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $standing = LeagueStanding::findOrFail($id);

        $this->editingId = $standing->id;
        $this->competition_id = $standing->competition_id;
        $this->competitor_type = $standing->competitor_type ?? 'team';
        $this->competitor_id = $standing->competitor_id ?? $standing->team_id;
        $this->position = $standing->position;
        $this->played = $standing->played;
        $this->won = $standing->won;
        $this->lost = $standing->lost;
        $this->sets_won = $standing->sets_won;
        $this->sets_lost = $standing->sets_lost;
        $this->points_for = $standing->points_for;
        $this->points_against = $standing->points_against;
        $this->points = $standing->points;
        $this->is_manual_override = $standing->is_manual_override;
        $this->flashType = null;
        $this->flashMessage = null;

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $competition = Competition::find($validated['competition_id']);
        $validated['season_id'] = $competition?->season_id;
        $validated['team_id'] = $validated['competitor_type'] === 'team' ? $validated['competitor_id'] : null;

        if ($this->editingId) {
            LeagueStanding::findOrFail($this->editingId)->update($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Wpis w tabeli został zaktualizowany.';
        } else {
            LeagueStanding::create($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Drużyna została dodana do tabeli.';
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        LeagueStanding::findOrFail($id)->delete();
        $this->flashType = 'success';
        $this->flashMessage = 'Wpis został usunięty.';
    }

    /**
     * Przelicz tabelę automatycznie na podstawie zakończonych meczów wybranych rozgrywek.
     */
    public function recalculate(): void
    {
        if (! $this->competitionFilter) {
            $this->flashType = 'error';
            $this->flashMessage = 'Wybierz rozgrywki, dla których chcesz przeliczyć tabelę.';

            return;
        }

        $competition = Competition::findOrFail($this->competitionFilter);
        StandingsCalculator::recalculate($competition);

        $this->flashType = 'success';
        $this->flashMessage = 'Tabela została przeliczona na podstawie zakończonych meczów.';
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->competition_id = null;
        $this->competitor_type = 'team';
        $this->competitor_id = null;
        $this->position = 0;
        $this->played = 0;
        $this->won = 0;
        $this->lost = 0;
        $this->sets_won = 0;
        $this->sets_lost = 0;
        $this->points_for = 0;
        $this->points_against = 0;
        $this->points = 0;
        $this->is_manual_override = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $standings = LeagueStanding::with(['competitor', 'competition'])
            ->when($this->competitionFilter, fn ($q) => $q->where('competition_id', $this->competitionFilter))
            ->orderBy('position')
            ->get();

        $competitions = Competition::orderBy('season_id', 'desc')->orderBy('display_order')->get();
        $teams = Team::orderBy('display_order')->get();
        $rivalTeams = RivalTeam::orderBy('full_name')->get();

        return view('livewire.admin.standing-manager', [
            'standings' => $standings,
            'competitions' => $competitions,
            'teams' => $teams,
            'rivalTeams' => $rivalTeams,
        ]);
    }
}

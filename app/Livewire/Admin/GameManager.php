<?php

namespace App\Livewire\Admin;

use App\Models\Competition;
use App\Models\Game;
use App\Models\RivalTeam;
use App\Models\Season;
use App\Models\Team;
use App\Services\StandingsCalculator;
use Livewire\Component;
use Livewire\WithPagination;

class GameManager extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public ?int $team_id = null;

    public ?int $season_id = null;

    public ?int $competition_id = null;

    public string $opponent = '';

    public ?int $opponent_team_id = null;

    public bool $is_home = true;

    public string $game_date = '';

    public string $venue = '';

    public string $league = '';

    public string $status = 'scheduled';

    public ?int $home_score = null;

    public ?int $away_score = null;

    public ?int $home_points = null;

    public ?int $away_points = null;

    public string $match_report = '';

    public string $video_url = '';

    public string $search = '';

    public string $statusFilter = '';

    public ?string $flashType = null;

    public ?string $flashMessage = null;

    protected function rules(): array
    {
        return [
            'team_id' => 'required|exists:teams,id',
            'season_id' => 'required|exists:seasons,id',
            'competition_id' => 'nullable|exists:competitions,id',
            'opponent' => 'required|string|max:255',
            'opponent_team_id' => 'nullable|exists:rival_teams,id',
            'is_home' => 'boolean',
            'game_date' => 'required|date',
            'venue' => 'nullable|string|max:255',
            'league' => 'nullable|string|max:255',
            'status' => 'required|in:scheduled,live,finished,cancelled',
            'home_score' => 'nullable|integer|min:0',
            'away_score' => 'nullable|integer|min:0',
            'home_points' => 'nullable|integer|min:0',
            'away_points' => 'nullable|integer|min:0',
            'match_report' => 'nullable|string',
            'video_url' => 'nullable|url',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedOpponentTeamId(): void
    {
        if ($this->opponent_team_id && $rivalTeam = RivalTeam::find($this->opponent_team_id)) {
            $this->opponent = $rivalTeam->full_name;
        }
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->flashType = null;
        $this->flashMessage = null;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $game = Game::findOrFail($id);

        $this->editingId = $game->id;
        $this->team_id = $game->team_id;
        $this->season_id = $game->season_id;
        $this->competition_id = $game->competition_id;
        $this->opponent = $game->opponent;
        $this->opponent_team_id = $game->opponent_team_id;
        $this->is_home = $game->is_home;
        $this->game_date = $game->game_date?->format('Y-m-d\TH:i');
        $this->venue = (string) $game->venue;
        $this->league = (string) $game->league;
        $this->status = $game->status;
        $this->home_score = $game->home_score;
        $this->away_score = $game->away_score;
        $this->home_points = $game->home_points;
        $this->away_points = $game->away_points;
        $this->match_report = (string) $game->match_report;
        $this->video_url = (string) $game->video_url;
        $this->flashType = null;
        $this->flashMessage = null;

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            $game = Game::findOrFail($this->editingId);
            $game->update($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Mecz został zaktualizowany.';
        } else {
            $game = Game::create($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Mecz został dodany.';
        }

        if ($game->competition_id && $competition = Competition::find($game->competition_id)) {
            StandingsCalculator::recalculate($competition);
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        $game = Game::findOrFail($id);
        $competitionId = $game->competition_id;
        $game->delete();

        if ($competitionId && $competition = Competition::find($competitionId)) {
            StandingsCalculator::recalculate($competition);
        }

        $this->flashType = 'success';
        $this->flashMessage = 'Mecz został usunięty.';
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->team_id = null;
        $this->season_id = null;
        $this->competition_id = null;
        $this->opponent = '';
        $this->opponent_team_id = null;
        $this->is_home = true;
        $this->game_date = '';
        $this->venue = '';
        $this->league = '';
        $this->status = 'scheduled';
        $this->home_score = null;
        $this->away_score = null;
        $this->home_points = null;
        $this->away_points = null;
        $this->match_report = '';
        $this->video_url = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $games = Game::with(['team', 'opponentTeam'])
            ->when($this->search, fn ($q) => $q->where('opponent', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('game_date', 'desc')
            ->paginate(12);

        $teams = Team::orderBy('display_order')->get();
        $seasons = Season::orderBy('start_date', 'desc')->get();
        $competitions = Competition::orderBy('name')->get();
        $rivalTeams = RivalTeam::orderBy('full_name')->get();

        return view('livewire.admin.game-manager', [
            'games' => $games,
            'teams' => $teams,
            'seasons' => $seasons,
            'competitions' => $competitions,
            'rivalTeams' => $rivalTeams,
        ]);
    }
}

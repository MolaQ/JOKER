<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Game;
use App\Models\LeagueStanding;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Support\Collection;

class TeamController extends Controller
{
    public function index()
    {
        $teams = Team::with(['trainer', 'assistantTrainers'])
            ->withCount('activePlayers')
            ->orderBy('display_order')
            ->get();

        return view('teams.index', compact('teams'));
    }

    public function show(Team $team)
    {
        $team->load(['trainer', 'assistantTrainers']);

        $currentSeason = Season::current()->first();

        $roster = $currentSeason
            ? $team->seasonRoster($currentSeason->id)->where('players.is_active', true)->get()
            : $team->activePlayers()->get();

        // Najpierw rozgrywający, przyjmujący... potem wg nazwiska - czytelniej niż sam alfabet.
        $positionOrder = ['setter', 'outside_hitter', 'opposite', 'middle_blocker', 'libero'];
        $rosterByPosition = $roster
            ->sortBy('last_name')
            ->groupBy(fn ($player) => $player->position?->value)
            ->sortBy(fn ($players, $position) => array_search($position, $positionOrder));

        $staff = $team->staff();

        $upcoming_games = Game::where('team_id', $team->id)
            ->with(['team', 'competition', 'opponentTeam'])
            ->upcoming()
            ->limit(5)
            ->get();

        $recent_games = Game::where('team_id', $team->id)
            ->with(['team', 'competition', 'opponentTeam'])
            ->finished()
            ->limit(5)
            ->get();

        $seasonCompetitions = $this->seasonCompetitions($team, $currentSeason);

        return view('teams.show', compact('team', 'roster', 'rosterByPosition', 'staff', 'upcoming_games', 'recent_games', 'seasonCompetitions', 'currentSeason'));
    }

    /**
     * Rozgrywki drużyny w sezonie: pod jaką nazwą gra, droga przez etapy i grupy
     * (np. Etap 1, Grupa B: 2. miejsce → Etap 2, Grupa D: 1. miejsce) oraz tabela bieżącej grupy.
     *
     * @return Collection<int, array{competition: Competition, names: array<int, string>, steps: Collection<int, array{stage: ?string, group: ?string, position: int, teams: int}>, table: Collection<int, LeagueStanding>}>
     */
    private function seasonCompetitions(Team $team, ?Season $season): Collection
    {
        if (! $season) {
            return collect();
        }

        return $team->competitions()
            ->where('season_id', $season->id)
            ->orderBy('display_order')
            ->get()
            ->map(function (Competition $competition) use ($team) {
                $standings = LeagueStanding::with('competitor')->where('competition_id', $competition->id)->orderBy('position')->get();
                $stageNumber = fn (?string $stage) => (int) preg_replace('/\D/', '', (string) $stage);

                $steps = $standings
                    ->filter(fn (LeagueStanding $row) => $row->isOwnTeam() && $row->competitor_id === $team->id)
                    ->sortBy(fn (LeagueStanding $row) => $stageNumber($row->stage_name))
                    ->map(fn (LeagueStanding $row) => [
                        'stage' => $row->stage_name,
                        'group' => $row->group_name,
                        'position' => $row->position,
                        'teams' => $standings->where('stage_name', $row->stage_name)->where('group_name', $row->group_name)->count(),
                    ])
                    ->values();

                $current = $steps->last();
                $names = array_values(array_filter(array_map('trim', preg_split('/[;\n]+/', (string) $competition->pivot->external_name) ?: [])));

                return [
                    'competition' => $competition,
                    'names' => array_values(array_filter($names, fn (string $name) => $name !== $team->name)),
                    'steps' => $steps,
                    'table' => $current
                        ? $standings->where('stage_name', $current['stage'])->where('group_name', $current['group'])->values()
                        : collect(),
                ];
            });
    }
}

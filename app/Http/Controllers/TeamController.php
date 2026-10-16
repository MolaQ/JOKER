<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\LeagueStanding;
use App\Models\Season;
use App\Models\Team;

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

        $competitionIds = LeagueStanding::where('team_id', $team->id)
            ->where('season_id', $currentSeason?->id)
            ->pluck('competition_id');

        $standingsByCompetition = LeagueStanding::with(['competitor', 'competition'])
            ->whereIn('competition_id', $competitionIds)
            ->orderBy('position')
            ->get()
            ->groupBy(fn (LeagueStanding $standing) => $standing->competition?->name ?? 'Rozgrywki');

        return view('teams.show', compact('team', 'roster', 'rosterByPosition', 'staff', 'upcoming_games', 'recent_games', 'standingsByCompetition', 'currentSeason'));
    }
}

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
        $teams = Team::with(['trainer', 'assistantTrainers'])->orderBy('display_order')->get();

        return view('teams.index', compact('teams'));
    }

    public function show(Team $team)
    {
        $team->load(['trainer', 'assistantTrainers']);

        $currentSeason = Season::current()->first();

        $roster = $currentSeason
            ? $team->seasonRoster($currentSeason->id)->where('players.is_active', true)->orderBy('players.last_name')->get()
            : $team->activePlayers()->orderBy('last_name')->get();

        $upcoming_games = Game::where('team_id', $team->id)
            ->with(['competition', 'opponentTeam'])
            ->withCount('likes')
            ->upcoming()
            ->limit(5)
            ->get();

        $recent_games = Game::where('team_id', $team->id)
            ->with(['competition', 'opponentTeam'])
            ->withCount('likes')
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

        return view('teams.show', compact('team', 'roster', 'upcoming_games', 'recent_games', 'standingsByCompetition', 'currentSeason'));
    }
}

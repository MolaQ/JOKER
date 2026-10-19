<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\LeagueStanding;
use App\Models\Season;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        return view('schedule.index', ['currentSeason' => Season::current()->first()]);
    }

    public function standings(Request $request)
    {
        $currentSeason = Season::current()->first();

        $competitions = Competition::where('season_id', $currentSeason?->id)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $selectedCompetitionId = (int) $request->integer('competition');

        if (! $selectedCompetitionId && $competitions->isNotEmpty()) {
            $selectedCompetitionId = (int) $competitions->first()->id;
        }

        $standings = LeagueStanding::with('competitor')
            ->where('season_id', $currentSeason?->id)
            ->when($selectedCompetitionId, fn ($q) => $q->where('competition_id', $selectedCompetitionId))
            ->orderBy('position')
            ->get();

        $selectedCompetition = $competitions->firstWhere('id', $selectedCompetitionId);

        return view('schedule.standings', compact('standings', 'currentSeason', 'competitions', 'selectedCompetitionId', 'selectedCompetition'));
    }
}

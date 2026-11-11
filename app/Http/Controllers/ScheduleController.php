<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Season;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Mecze i tabele na jednej stronie: zakładki „Terminarz” i „Tabele” (?tab=tables).
     */
    public function index(Request $request)
    {
        $season = Season::current()->first();

        return view('schedule.index', [
            'currentSeason' => $season,
            'hasStandings' => Competition::where('season_id', $season?->id)->exists(),
            'startTab' => $request->query('tab') === 'tables' ? 'tables' : 'schedule',
        ]);
    }

    /**
     * Dawna osobna strona tabel: przekierowuje na zakładkę „Tabele” na stronie meczów (z wybraną ligą i etapem).
     */
    public function standings(Request $request): RedirectResponse
    {
        return redirect()->route('schedule.index', array_filter([
            'tab' => 'tables',
            'league' => $request->query('league', $request->query('competition')),
            'stage' => $request->query('stage'),
        ], fn ($value) => is_string($value) && $value !== ''), 301);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Support\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TournamentController extends Controller
{
    /**
     * Zakładka „Memoriał” w menu: najnowsza edycja cyklicznego turnieju klubu (System → Moduły).
     */
    public function memorial(): RedirectResponse|View
    {
        abort_if(blank(app(SiteSettings::class)['featured_tournament_name']), 404);

        $tournament = Tournament::where('series', Tournament::SERIES_MEMORIAL)
            ->where('has_page', true)
            ->orderByDesc('start_date')
            ->first();

        if ($tournament === null) {
            return view('tournaments.memorial-soon');
        }

        return redirect()->route('tournaments.show', ['tournament' => $tournament, ...request()->query()]);
    }

    public function show(Tournament $tournament): View
    {
        abort_unless($tournament->has_page, 404);

        return view('tournaments.show', ['tournament' => $tournament]);
    }
}

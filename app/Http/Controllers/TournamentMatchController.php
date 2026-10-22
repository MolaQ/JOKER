<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentMatch;
use Illuminate\Contracts\View\View;

class TournamentMatchController extends Controller
{
    /**
     * Mecz turnieju: wynik, transmisje i galerie zdjęć.
     */
    public function show(Tournament $tournament, TournamentMatch $match): View
    {
        $match->load(['category.tournament', 'group', 'home.competitor', 'away.competitor', 'publishedGalleries.photos.tags.taggable']);

        abort_unless($tournament->has_page && $match->category?->tournament_id === $tournament->id, 404);

        return view('tournaments.match', ['tournament' => $tournament, 'match' => $match]);
    }
}

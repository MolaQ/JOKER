<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Game;
use App\Models\HeroSlide;
use App\Models\Player;
use App\Models\Season;
use App\Models\Sponsor;
use App\Models\Team;
use App\Models\User;

class HomeController extends Controller
{
    public function index()
    {
        $heroSlides = HeroSlide::activeNow()
            ->ordered()
            ->get();

        $upcoming_games = Game::with(['team', 'competition', 'opponentTeam'])
            ->upcoming()
            ->limit(6)
            ->get();

        $recent_results = Game::with(['team', 'competition', 'opponentTeam'])
            ->finished()
            ->limit(4)
            ->get();

        $articles = Article::published()
            ->limit(5)
            ->get();

        $teams = Team::withCount('activePlayers')
            ->orderBy('display_order')
            ->get();

        $sponsors = Sponsor::active()->ordered()->get();

        $stats = [
            'teams' => $teams->count(),
            'players' => Player::where('is_active', true)->count(),
            'staff' => User::query()->whereHas('headCoachTeams')->orWhereHas('assistantTeams')->count(),
        ];

        $seasonName = Season::current()->value('name');

        return view('home', compact('seasonName', 'heroSlides', 'upcoming_games', 'recent_results', 'articles', 'teams', 'sponsors', 'stats'));
    }
}

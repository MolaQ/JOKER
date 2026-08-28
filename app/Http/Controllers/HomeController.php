<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Game;
use App\Models\Player;
use App\Models\Sponsor;
use App\Models\Team;

class HomeController extends Controller
{
    public function index()
    {
        $featured_articles = Article::published()
            ->featured()
            ->withCount(['approvedComments', 'likes'])
            ->limit(3)
            ->get();

        $latest_articles = Article::published()
            ->withCount(['approvedComments', 'likes'])
            ->limit(6)
            ->get();

        $upcoming_games = Game::with(['team', 'competition'])
            ->withCount('likes')
            ->upcoming()
            ->limit(5)
            ->get();

        $sponsors = Sponsor::active()->ordered()->get();

        $teams = Team::withCount('likes')
            ->with(['trainer', 'assistantTrainers'])
            ->orderBy('display_order')
            ->get();

        $popularPlayer = Player::withCount('likes')
            ->orderByDesc('likes_count')
            ->orderByDesc('id')
            ->first();

        $popularGame = Game::with(['team', 'competition'])
            ->withCount('likes')
            ->orderByDesc('likes_count')
            ->orderByDesc('id')
            ->first();

        return view('home', compact('featured_articles', 'latest_articles', 'upcoming_games', 'sponsors', 'teams', 'popularPlayer', 'popularGame'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'teams' => Team::count(),
            'players' => Player::count(),
            'games' => Game::count(),
            'articles' => Article::count(),
            'users' => User::count(),
            'upcoming_games' => Game::upcoming()->count(),
            'published_articles' => Article::published()->count(),
        ];

        $recent_games = Game::with('team')->latest('game_date')->limit(5)->get();
        $recent_articles = Article::with('author')->published()->limit(5)->get();

        return view('admin.dashboard', compact('stats', 'recent_games', 'recent_articles'));
    }
}

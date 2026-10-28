<?php

namespace App\Http\Controllers;

use App\Models\Alumnus;
use App\Models\Article;
use App\Models\DailyLineup;
use App\Models\Game;
use App\Models\HeroSlide;
use App\Models\Player;
use App\Models\Season;
use App\Models\Sponsor;
use App\Models\Team;
use App\Models\User;
use App\Support\SiteSettings;

class HomeController extends Controller
{
    public function index(SiteSettings $site)
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

        $articles = Article::forListing()
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

        $countdownGame = $this->countdownGame($site['countdown_target']) ?? $upcoming_games->first();

        $lineup = $site['home_lineup_enabled'] ? DailyLineup::today() : null;

        $alumni = $site['home_alumni_enabled'] ? Alumnus::published()->with('player')->limit(4)->get() : collect();

        return view('home', compact('seasonName', 'heroSlides', 'upcoming_games', 'recent_results', 'articles', 'teams', 'sponsors', 'stats', 'countdownGame', 'alumni', 'lineup'));
    }

    /**
     * Mecz, do którego odlicza zegar na stronie głównej (Wygląd i ustawienia → Strona główna).
     */
    private function countdownGame(?string $target): ?Game
    {
        if ($target !== 'seniors') {
            return null;
        }

        return Game::with(['team', 'competition', 'opponentTeam'])
            ->upcoming()
            ->whereHas('team', fn ($query) => $query->where('category', 'senior'))
            ->first();
    }
}

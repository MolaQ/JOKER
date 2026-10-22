<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Document;
use App\Models\Gallery;
use App\Models\GalleryPhoto;
use App\Models\Game;
use App\Models\GameComment;
use App\Models\Like;
use App\Models\PageView;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Support\Approvals;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $season = Season::current()->first();
        $seasonGames = $season ? Game::where('season_id', $season->id)->get() : collect();
        $results = $seasonGames->filter->isFinished()->map->ourSetsResult()->filter();
        $wins = $results->filter(fn (array $sets) => $sets[0] > $sets[1])->count();
        $views = PageView::where('kind', PageView::KIND_PAGE);

        // Kafelki: [ikona, podpis, liczba, dopisek, trasa, uprawnienie]
        $stats = [
            ['👥', 'Drużyny', Team::count(), null, 'admin.teams.index', 'teams.manage'],
            ['🎽', 'Zawodnicy', Player::where('is_active', true)->count(), 'aktywni', 'admin.players.index', 'teams.manage'],
            ['🏐', 'Mecze w sezonie', $seasonGames->count(), $season?->name, 'admin.games.index', 'games.manage'],
            ['🏆', 'Bilans sezonu', $wins.'–'.($results->count() - $wins), 'wygrane–przegrane', 'admin.games.index', 'games.manage'],
            ['📅', 'Nadchodzące mecze', Game::upcoming()->count(), null, 'admin.games.index', 'games.manage'],
            ['🥇', 'Turnieje', Tournament::count(), null, 'admin.tournaments.index', 'tournaments.manage'],
            ['📰', 'Aktualności', Article::published()->count(), 'opublikowane', 'admin.articles.index', 'articles.manage'],
            ['📷', 'Galerie', Gallery::count(), GalleryPhoto::count().' zdjęć', 'admin.galleries.index', 'media.manage'],
            ['💬', 'Komentarze', Comment::count() + GameComment::count(), null, 'admin.comments.index', 'comments.moderate'],
            ['❤️', 'Polubienia', Like::count(), null, null, null],
            ['📄', 'Pobrania dokumentów', (int) Document::sum('download_count'), Document::count().' dokumentów', 'admin.documents.index', 'documents.manage'],
            ['🔑', 'Konta', User::where('is_active', true)->count(), User::where('is_active', false)->count().' nieaktywnych', 'admin.users.index', 'users.manage'],
            ['👀', 'Odwiedziny dziś', (clone $views)->where('created_at', '>=', today())->count(), null, 'admin.statistics.index', 'statistics.view'],
            ['📈', 'Odwiedziny 7 dni', (clone $views)->where('created_at', '>=', now()->subDays(7))->count(), (clone $views)->where('created_at', '>=', now()->subDays(7))->distinct()->count('visitor_hash').' osób', 'admin.statistics.index', 'statistics.view'],
        ];

        $user = $request->user();
        $stats = array_values(array_filter($stats, fn (array $stat) => $stat[5] === null || $user->can($stat[5])));
        $approvals = Approvals::sectionsFor($user)->filter(fn (array $section) => $section['count'] > 0);

        $recent_games = Game::with('team')->upcoming()->limit(5)->get();
        $recent_articles = Article::with('author')->published()->limit(5)->get();

        return view('admin.dashboard', compact('stats', 'approvals', 'recent_games', 'recent_articles'));
    }
}

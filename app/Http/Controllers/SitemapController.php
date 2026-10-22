<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Gallery;
use App\Models\Game;
use App\Models\Page;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * Mapa strony dla wyszukiwarek (sitemap.xml) i robots.txt z adresem mapy.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), fn () => view('sitemap', ['urls' => $this->urls()])->render());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /panel',
            'Disallow: /login',
            'Disallow: /moj-profil',
            'Disallow: /szukaj',
            'Disallow: /jezyk/',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * @return list<array{loc: string, lastmod: ?Carbon, priority: string}>
     */
    private function urls(): array
    {
        $urls = [
            ['loc' => route('home'), 'lastmod' => Article::published()->max('updated_at'), 'priority' => '1.0'],
            ['loc' => route('articles.index'), 'lastmod' => Article::published()->max('updated_at'), 'priority' => '0.8'],
            ['loc' => route('schedule.index'), 'lastmod' => Game::max('updated_at'), 'priority' => '0.8'],
            ['loc' => route('standings'), 'lastmod' => null, 'priority' => '0.6'],
            ['loc' => route('teams.index'), 'lastmod' => Team::max('updated_at'), 'priority' => '0.8'],
            ['loc' => route('documents.index'), 'lastmod' => null, 'priority' => '0.4'],
            ['loc' => route('contact.index'), 'lastmod' => null, 'priority' => '0.5'],
        ];

        foreach (Page::published()->get() as $page) {
            $urls[] = ['loc' => route('pages.show', $page), 'lastmod' => $page->updated_at, 'priority' => '0.7'];
        }

        foreach (Team::all() as $team) {
            $urls[] = ['loc' => route('teams.show', $team), 'lastmod' => $team->updated_at, 'priority' => '0.7'];
        }

        foreach (Player::where('is_active', true)->get() as $player) {
            $urls[] = ['loc' => route('players.show', $player), 'lastmod' => $player->updated_at, 'priority' => '0.5'];
        }

        foreach (User::all()->filter->isStaffMember() as $trainer) {
            $urls[] = ['loc' => route('trainers.show', $trainer), 'lastmod' => $trainer->updated_at, 'priority' => '0.4'];
        }

        foreach (Article::published()->latest('published_at')->get() as $article) {
            $urls[] = ['loc' => route('articles.show', $article), 'lastmod' => $article->updated_at, 'priority' => '0.6'];
        }

        // Mecze bieżącego i poprzedniego sezonu (starsze rzadko ktoś szuka, a lista byłaby bardzo długa).
        $seasonIds = Season::orderByDesc('start_date')->limit(2)->pluck('id');
        foreach (Game::whereIn('season_id', $seasonIds)->get() as $game) {
            $urls[] = ['loc' => route('games.show', $game), 'lastmod' => $game->updated_at, 'priority' => '0.5'];
        }

        foreach (Tournament::where('has_page', true)->get() as $tournament) {
            $urls[] = ['loc' => route('tournaments.show', $tournament), 'lastmod' => $tournament->updated_at, 'priority' => '0.6'];
        }

        if (Route::has('galleries.show')) {
            foreach (Gallery::published()->get() as $gallery) {
                $urls[] = ['loc' => route('galleries.show', $gallery), 'lastmod' => $gallery->updated_at, 'priority' => '0.5'];
            }
        }

        return $urls;
    }
}

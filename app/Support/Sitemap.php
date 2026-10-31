<?php

namespace App\Support;

use App\Models\Alumnus;
use App\Models\Article;
use App\Models\Gallery;
use App\Models\Game;
use App\Models\Page;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * Mapa strony dla wyszukiwarek. Gotowy plik leży w pamięci podręcznej; każda zmiana treści
 * (artykuł, mecz, drużyna...) go unieważnia, a następne wejście robota buduje go od nowa.
 * W panelu „Pozycjonowanie (SEO)” można zbudować ją od razu.
 */
class Sitemap
{
    private const XML = 'sitemap.xml';

    private const META = 'sitemap.meta';

    /**
     * Modele, których zmiana zmienia mapę strony.
     *
     * @var list<class-string>
     */
    public const WATCHED = [Article::class, Game::class, Team::class, Player::class, Page::class, Gallery::class, Tournament::class, Alumnus::class, TrainingSession::class];

    public static function xml(): string
    {
        return Cache::get(self::XML) ?? self::build();
    }

    public static function build(): string
    {
        $urls = (new self)->urls();
        $xml = view('sitemap', ['urls' => $urls])->render();

        Cache::forever(self::XML, $xml);
        Cache::forever(self::META, ['built_at' => now()->toIso8601String(), 'count' => count($urls), 'by' => Auth::user()?->name, 'stale' => false]);

        return $xml;
    }

    public static function forget(): void
    {
        Cache::forget(self::XML);
        Cache::forever(self::META, array_merge(self::meta() ?? [], ['stale' => true]));
    }

    /**
     * Kiedy i z iloma adresami zbudowano mapę; stale = od tego czasu zmieniły się treści.
     *
     * @return array{built_at?: string, count?: int, by?: ?string, stale?: bool}|null
     */
    public static function meta(): ?array
    {
        return Cache::get(self::META);
    }

    /**
     * @return list<array{loc: string, lastmod: ?Carbon, priority: string}>
     */
    public function urls(): array
    {
        $urls = [
            ['loc' => route('home'), 'lastmod' => Article::published()->max('updated_at'), 'priority' => '1.0'],
            ['loc' => route('articles.index'), 'lastmod' => Article::published()->max('updated_at'), 'priority' => '0.8'],
            ['loc' => route('schedule.index'), 'lastmod' => Game::max('updated_at'), 'priority' => '0.8'],
            ['loc' => route('standings'), 'lastmod' => null, 'priority' => '0.6'],
            ['loc' => route('teams.index'), 'lastmod' => Team::max('updated_at'), 'priority' => '0.8'],
            ['loc' => route('galleries.index'), 'lastmod' => Gallery::max('updated_at'), 'priority' => '0.6'],
            ['loc' => route('alumni.index'), 'lastmod' => Alumnus::max('updated_at'), 'priority' => '0.7'],
            ['loc' => route('trainings.index'), 'lastmod' => TrainingSession::max('updated_at'), 'priority' => '0.7'],
            ['loc' => route('support.index'), 'lastmod' => null, 'priority' => '0.5'],
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

        foreach (Tournament::where('has_page', true)->with('categories')->get() as $tournament) {
            $urls[] = ['loc' => route('tournaments.show', $tournament), 'lastmod' => $tournament->updated_at, 'priority' => '0.6'];

            foreach ($tournament->categories->skip(1) as $category) {
                $urls[] = ['loc' => route('tournaments.show', ['tournament' => $tournament, 'category' => $category->slug()]), 'lastmod' => $tournament->updated_at, 'priority' => '0.5'];
            }
        }

        if (Route::has('galleries.show')) {
            foreach (Gallery::published()->get() as $gallery) {
                $urls[] = ['loc' => route('galleries.show', $gallery), 'lastmod' => $gallery->updated_at, 'priority' => '0.5'];
            }
        }

        return $urls;
    }
}

<?php

namespace App\Support;

use App\Models\Article;
use App\Models\GalleryPhoto;
use App\Models\Game;
use App\Models\TournamentMatch;
use App\Services\PushNotifier;
use Illuminate\Support\Facades\Cache;

use function Illuminate\Support\defer;

/**
 * Kiedy wysłać powiadomienie push: opublikowana aktualność, wpisany wynik meczu (ligowego i turniejowego), pierwsze zdjęcia w galerii.
 * Każda treść powiadamia najwyżej raz; wysyłka idzie po odpowiedzi serwera, więc nie spowalnia panelu.
 */
class PushTriggers
{
    public static function register(): void
    {
        Article::saved(function (Article $article) {
            if ($article->status === 'published' && ($article->wasRecentlyCreated || $article->wasChanged(['status', 'published_at']))) {
                self::once('article-'.$article->id, fn (PushNotifier $push) => $push->articlePublished($article));
            }
        });

        Game::saved(function (Game $game) {
            if ($game->isFinished() && $game->ourSetsResult() && ($game->wasRecentlyCreated || $game->wasChanged(['status', 'home_score', 'away_score']))) {
                self::once('game-'.$game->id, fn (PushNotifier $push) => $push->gameFinished($game));
            }
        });

        TournamentMatch::saved(function (TournamentMatch $match) {
            if ($match->isFinished() && ($match->wasRecentlyCreated || $match->wasChanged(['is_live', 'home_sets', 'away_sets']))) {
                self::once('tournament-match-'.$match->id, fn (PushNotifier $push) => $push->tournamentMatchFinished($match));
            }
        });

        GalleryPhoto::created(function (GalleryPhoto $photo) {
            if (($gallery = $photo->gallery) && $gallery->is_published) {
                self::once('gallery-'.$gallery->id, fn (PushNotifier $push) => $push->galleryPublished($gallery));
            }
        });
    }

    private static function once(string $key, callable $callback): void
    {
        if (! app(PushNotifier::class)->enabled() || ! Cache::add('push-sent:'.$key, true, now()->addDays(30))) {
            return;
        }

        defer(fn () => $callback(app(PushNotifier::class)));
    }
}

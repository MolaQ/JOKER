<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\GameController;
use App\Http\Controllers\TournamentMatchController;
use App\Models\Article;
use App\Models\ContentProposal;
use App\Models\Game;
use App\Models\RivalTeam;
use App\Models\TournamentMatch;
use App\Support\ClubLogos;
use App\Support\ContentProposalPublisher;
use App\Support\LiveNow;
use App\Support\SearchIndex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Podgląd propozycji treści na prawdziwej stronie: propozycja jest publikowana w transakcji,
 * strona renderowana, a transakcja wycofywana. Pliki zostają na miejscu, nic nie trafia na stronę.
 */
class ContentProposalPreviewController extends Controller
{
    public function __invoke(ContentProposal $proposal): Response
    {
        abort_unless($proposal->status === ContentProposal::STATUS_PENDING, 404);
        $proposal->load(['match', 'user']);

        DB::beginTransaction();

        try {
            $html = $this->render($proposal, new ContentProposalPublisher(keepFiles: true));
        } finally {
            DB::rollBack();
            // Strona mogła zapamiętać dane z podglądu (np. trwającą transmisję).
            Cache::forget(LiveNow::CACHE_KEY);
            SearchIndex::forget();
        }

        $banner = view('admin.content-proposals.preview-banner', ['proposal' => $proposal])->render();
        $html = preg_replace('/<body[^>]*>/i', '$0'.$banner, $html, 1) ?? $html;

        return response($html)->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * Czy propozycja ma stronę do podglądu (zdjęcia i transmisje z wyjazdów minisiatkówki jej nie mają).
     */
    public static function previewable(ContentProposal $proposal): bool
    {
        return match (true) {
            $proposal->status !== ContentProposal::STATUS_PENDING => false,
            in_array($proposal->type, [ContentProposal::TYPE_ARTICLE, ContentProposal::TYPE_LOGO], true) => true,
            $proposal->match instanceof Game => true,
            $proposal->match instanceof TournamentMatch => (bool) $proposal->match->tournament()?->has_page,
            default => false,
        };
    }

    private function render(ContentProposal $proposal, ContentProposalPublisher $publisher): string
    {
        if ($proposal->type === ContentProposal::TYPE_ARTICLE) {
            $publisher->publishArticle($proposal);
            $article = Article::latest('id')->firstOrFail();

            return app(ArticleController::class)->show($article)->render();
        }

        $publisher->publish($proposal);

        $match = $proposal->match instanceof RivalTeam ? $this->gameAgainst($proposal->match) : $proposal->match;

        if ($match === null && $proposal->match instanceof RivalTeam) {
            return view('logos.index', ['logos' => ClubLogos::ranked()])->render();
        }

        return $this->matchPage($match?->fresh() ?? abort(404, 'Ta propozycja nie ma strony do podglądu.'));
    }

    private function matchPage(Model $match): string
    {
        if ($match instanceof Game) {
            return app(GameController::class)->show($match)->render();
        }

        $tournament = $match instanceof TournamentMatch ? $match->tournament() : null;
        abort_unless($tournament?->has_page, 404, 'Ta propozycja nie ma strony do podglądu.');

        return app(TournamentMatchController::class)->show($tournament, $match)->render();
    }

    /**
     * Najbliższy mecz z tą drużyną, a gdy żadnego nie ma w terminarzu: ostatni rozegrany.
     */
    private function gameAgainst(RivalTeam $rival): ?Game
    {
        $games = fn () => Game::where('opponent_team_id', $rival->id);

        return $games()->where('game_date', '>=', today())->orderBy('game_date')->first()
            ?? $games()->orderByDesc('game_date')->first();
    }
}

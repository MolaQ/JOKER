<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Game;
use App\Models\GameComment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    /**
     * Strona meczu: drużyny, wynik, transmisje, galerie, artykuły i komentarze.
     */
    public function show(Game $game): View
    {
        $game->load([
            'team',
            'competition.teams',
            'opponentTeam.club',
            'tournament',
            'articles' => fn ($query) => $query->published()->orderByDesc('published_at'),
            'publishedGalleries.photos',
            'approvedComments' => fn ($query) => $query->with('user')->latest(),
        ]);

        return view('games.show', [
            'game' => $game,
            'articles' => $game->articles->filter(fn (Article $article) => $article->isPublished())->values(),
        ]);
    }

    public function storeComment(Request $request, Game $game): RedirectResponse
    {
        if (! $request->user()->is_active) {
            return back()->with('error', __('Komentować możesz po aktywacji konta.'));
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $user = $request->user();

        $comment = GameComment::create([
            'game_id' => $game->id,
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
            'is_approved' => ! $user->commentsNeedApproval(),
        ]);

        return redirect()->to(route('games.show', $game).'#komentarze')->with('success', $comment->is_approved ? __('Komentarz został dodany!') : __('Dziękujemy! Komentarz pojawi się po akceptacji moderatora.'));
    }
}

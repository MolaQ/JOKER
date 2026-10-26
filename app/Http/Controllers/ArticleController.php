<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::forListing()
            ->withCount(['likes', 'approvedComments'])
            ->paginate(13);

        return view('articles.index', compact('articles'));
    }

    public function show(Article $article)
    {
        if (! $article->isPublished()) {
            abort(404);
        }

        $article->increment('views_count');
        $article->load([
            'author',
            'approvedComments.user',
            'approvedComments.replies.user',
            'games' => fn ($query) => $query->with(['team', 'competition.teams', 'opponentTeam', 'tournament', 'publishedGalleries.photos.tags.taggable'])->orderBy('game_date'),
        ]);

        $related_articles = Article::published()
            ->where('id', '!=', $article->id)
            ->limit(3)
            ->get();

        return view('articles.show', compact('article', 'related_articles'));
    }

    public function storeComment(Request $request, Article $article)
    {
        if (! Auth::check()) {
            return redirect()->back()->with('error', __('Musisz być zalogowany, aby komentować.'));
        }

        if (! Auth::user()->is_active) {
            return redirect()->back()->with('error', __('Komentować możesz po aktywacji konta.'));
        }

        if (! Auth::user()->canComment()) {
            return redirect()->back()->with('error', __('Klub wyłączył Ci możliwość komentowania.'));
        }

        if (! $article->allow_comments) {
            return redirect()->back()->with('error', __('Komentarze są wyłączone dla tego artykułu.'));
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $user = Auth::user();

        $comment = Comment::create([
            'article_id' => $article->id,
            'user_id' => Auth::id(),
            'content' => $validated['content'],
            'is_approved' => ! $user->commentsNeedApproval(),
        ]);

        return redirect()->back()->with('success', $comment->is_approved ? __('Komentarz został dodany!') : __('Dziękujemy! Komentarz pojawi się po akceptacji moderatora.'));
    }
}

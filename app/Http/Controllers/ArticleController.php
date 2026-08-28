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
        $articles = Article::published()
            ->with('author')
            ->withCount(['approvedComments', 'likes'])
            ->paginate(12);

        $featured = Article::published()
            ->featured()
            ->withCount(['approvedComments', 'likes'])
            ->first();

        return view('articles.index', compact('articles', 'featured'));
    }

    public function show(Article $article)
    {
        if (! $article->isPublished()) {
            abort(404);
        }

        $article->increment('views_count');
        $article->load(['author', 'approvedComments.user', 'likes']);

        $related_articles = Article::published()
            ->where('id', '!=', $article->id)
            ->withCount(['approvedComments', 'likes'])
            ->limit(3)
            ->get();

        return view('articles.show', compact('article', 'related_articles'));
    }

    public function storeComment(Request $request, Article $article)
    {
        if (! Auth::check()) {
            return redirect()->back()->with('error', 'Musisz być zalogowany, aby komentować.');
        }

        if (! $article->allow_comments) {
            return redirect()->back()->with('error', 'Komentarze są wyłączone dla tego artykułu.');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        Comment::create([
            'article_id' => $article->id,
            'user_id' => Auth::id(),
            'content' => $validated['content'],
            'is_approved' => true,
        ]);

        return redirect()->back()->with('success', 'Komentarz został dodany!');
    }
}

<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\GameComment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Komentarze kibiców czekające na akceptację (pod aktualnościami i meczami).
 */
class CommentModeration extends Component
{
    use ShowsToasts;

    public const KINDS = ['article' => Comment::class, 'game' => GameComment::class];

    public bool $showAll = false;

    public static function pendingCount(): int
    {
        return Comment::where('is_approved', false)->count() + GameComment::where('is_approved', false)->count();
    }

    public function approve(string $kind, int $id): void
    {
        $comment = $this->find($kind, $id);
        $comment->update(['is_approved' => true]);

        $this->toast('Komentarz opublikowany.');
    }

    public function hide(string $kind, int $id): void
    {
        $comment = $this->find($kind, $id);
        $comment->update(['is_approved' => false]);

        $this->toast('Komentarz ukryty.');
    }

    public function delete(string $kind, int $id): void
    {
        $comment = $this->find($kind, $id);
        AuditLog::record('comment.deleted', 'Usunięto komentarz '.$comment->user?->name.': „'.str($comment->content)->limit(80).'”');
        $comment->delete();

        $this->toast('Komentarz usunięty.');
    }

    private function find(string $kind, int $id): Model
    {
        $class = self::KINDS[$kind] ?? abort(404);

        return $class::findOrFail($id);
    }

    /**
     * @return Collection<int, array{kind: string, comment: Model, where: string, url: ?string}>
     */
    private function comments(): Collection
    {
        $articles = Comment::with(['user', 'article'])
            ->when(! $this->showAll, fn ($query) => $query->where('is_approved', false))
            ->latest('id')->limit(50)->get()
            ->map(fn (Comment $comment) => ['kind' => 'article', 'comment' => $comment, 'where' => $comment->article?->title, 'url' => $comment->article ? route('articles.show', $comment->article) : null]);

        $games = GameComment::with(['user', 'game.team', 'game.opponentTeam'])
            ->when(! $this->showAll, fn ($query) => $query->where('is_approved', false))
            ->latest('id')->limit(50)->get()
            ->map(fn (GameComment $comment) => ['kind' => 'game', 'comment' => $comment, 'where' => $comment->game?->mediaLabel(), 'url' => $comment->game ? route('games.show', $comment->game) : null]);

        return $articles->concat($games)->sortByDesc(fn (array $row) => $row['comment']->created_at)->values();
    }

    public function render(): View
    {
        return view('livewire.admin.comment-moderation', ['rows' => $this->comments()]);
    }
}

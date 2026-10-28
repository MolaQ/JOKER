<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Comment;
use App\Models\GameComment;
use App\Models\User;
use App\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Komentarze kibiców czekające na akceptację (pod aktualnościami i meczami).
 * Kto ma „Moderacja komentarzy”, widzi wszystkie; koordynator drużyny tylko komentarze pod meczami swoich drużyn.
 */
class CommentModeration extends Component
{
    use ShowsToasts;

    public const KINDS = ['article' => Comment::class, 'game' => GameComment::class];

    public bool $showAll = false;

    /**
     * Drużyny, których komentarze moderuje konto: null = wszystkie, pusta lista = żadne.
     *
     * @return Collection<int, int>|null
     */
    public static function teamScope(User $user): ?Collection
    {
        if ($user->can('comments.moderate')) {
            return null;
        }

        return $user->hasRole(UserRole::Coordinator) ? $user->coordinatedTeams()->pluck('teams.id') : collect();
    }

    public static function canModerate(User $user): bool
    {
        $scope = self::teamScope($user);

        return $scope === null || $scope->isNotEmpty();
    }

    public static function pendingCount(User $user): int
    {
        $scope = self::teamScope($user);

        return ($scope === null ? Comment::where('is_approved', false)->count() : 0)
            + self::gameComments($scope)->where('is_approved', false)->count();
    }

    /**
     * @param  Collection<int, int>|null  $scope
     */
    private static function gameComments(?Collection $scope): Builder
    {
        return GameComment::query()->when($scope !== null, fn (Builder $query) => $query->whereHas('game', fn (Builder $game) => $game->whereIn('team_id', $scope)));
    }

    public function approve(string $kind, int $id): void
    {
        $comment = $this->find($kind, $id);
        $comment->update(['is_approved' => true, 'approved_by' => Auth::id()]);

        $this->toast('Komentarz opublikowany.');
    }

    public function hide(string $kind, int $id): void
    {
        $comment = $this->find($kind, $id);
        $comment->update(['is_approved' => false, 'approved_by' => null]);

        $this->toast('Komentarz ukryty.');
    }

    public function delete(string $kind, int $id): void
    {
        $comment = $this->find($kind, $id);
        $comment->delete();

        $this->toast('Komentarz usunięty.');
    }

    private function find(string $kind, int $id): Model
    {
        $scope = self::teamScope(Auth::user());
        abort_unless(isset(self::KINDS[$kind]) && ($scope === null || $kind === 'game'), 404);

        return $kind === 'game' ? self::gameComments($scope)->findOrFail($id) : Comment::findOrFail($id);
    }

    /**
     * @return Collection<int, array{kind: string, comment: Model, where: string, url: ?string}>
     */
    private function comments(): Collection
    {
        $scope = self::teamScope(Auth::user());

        $articles = $scope !== null ? collect() : Comment::with(['user', 'article', 'approver'])
            ->when(! $this->showAll, fn ($query) => $query->where('is_approved', false))
            ->latest('id')->limit(50)->get()
            ->map(fn (Comment $comment) => ['kind' => 'article', 'comment' => $comment, 'where' => $comment->article?->title, 'url' => $comment->article ? route('articles.show', $comment->article) : null]);

        $games = self::gameComments($scope)->with(['user', 'game.team', 'game.opponentTeam', 'approver'])
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

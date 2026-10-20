<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use App\Models\Game;
use App\Support\SearchIndex;
use App\Support\Slug;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ArticleManager extends Component
{
    use WithFileUploads, WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $excerpt = '';

    public string $content = '';

    public string $status = 'draft';

    public ?string $published_at = null;

    public bool $is_featured = false;

    public bool $allow_comments = true;

    public $featured_image = null;

    public ?string $existingImage = null;

    /**
     * Mecze, których dotyczy artykuł (otwierany z kalendarza meczów).
     *
     * @var array<int, int>
     */
    public array $gameIds = [];

    public string $gameSearch = '';

    public string $search = '';

    public string $statusFilter = '';

    public ?string $flashType = null;

    public ?string $flashMessage = null;

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'is_featured' => 'boolean',
            'allow_comments' => 'boolean',
            'featured_image' => 'nullable|image|max:4096',
            'gameIds' => 'array',
            'gameIds.*' => 'integer|exists:games,id',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->flashType = null;
        $this->flashMessage = null;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $article = Article::findOrFail($id);

        $this->editingId = $article->id;
        $this->title = $article->title;
        $this->excerpt = (string) $article->excerpt;
        $this->content = $article->content;
        $this->status = $article->status;
        $this->published_at = $article->published_at?->format('Y-m-d\TH:i');
        $this->is_featured = $article->is_featured;
        $this->allow_comments = $article->allow_comments;
        $this->existingImage = $article->featured_image;
        $this->featured_image = null;
        $this->gameIds = $article->games()->pluck('games.id')->all();
        $this->gameSearch = '';
        $this->flashType = null;
        $this->flashMessage = null;

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->featured_image) {
            $validated['featured_image'] = $this->featured_image->store('articles', 'public');
        } else {
            unset($validated['featured_image']);
        }

        unset($validated['gameIds'], $validated['gameIds.*']);

        if ($this->editingId) {
            $article = Article::findOrFail($this->editingId);
            $article->update($validated);
            $article->games()->sync($this->gameIds);
            $this->flashType = 'success';
            $this->flashMessage = 'Artykuł został zaktualizowany.';
        } else {
            $validated['slug'] = Slug::unique(Article::class, $this->title);
            $validated['author_id'] = Auth::id();
            Article::create($validated)->games()->sync($this->gameIds);
            $this->flashType = 'success';
            $this->flashMessage = 'Artykuł został dodany.';
        }

        $this->closeModal();
    }

    public function addGame(int $gameId): void
    {
        if (! in_array($gameId, $this->gameIds, true) && Game::whereKey($gameId)->exists()) {
            $this->gameIds[] = $gameId;
        }

        $this->gameSearch = '';
    }

    public function removeGame(int $gameId): void
    {
        $this->gameIds = array_values(array_filter($this->gameIds, fn (int $id) => $id !== $gameId));
    }

    /**
     * Czytelny opis meczu do wyboru, np. „12.10 Seniorzy – Wilki Wronki 3:2”.
     */
    public static function gameOptionLabel(Game $game): string
    {
        $score = $game->isFinished() && ($result = $game->ourSetsResult()) ? ' '.$result[0].':'.$result[1] : '';

        return trim(($game->game_date?->format('d.m.Y') ?? '').' '.($game->team?->name ?? '').' – '.$game->opponentShortName().$score);
    }

    /**
     * Podpowiedzi meczów do podpięcia: po wpisaniu rywala, drużyny lub daty; bez wpisu - ostatnie mecze.
     *
     * @return Collection<int, Game>
     */
    private function gameSuggestions(): Collection
    {
        $terms = array_filter(explode(' ', SearchIndex::normalize($this->gameSearch)));
        $games = Game::with(['team', 'opponentTeam'])
            ->whereNotIn('id', $this->gameIds)
            ->where('game_date', '<=', now()->addDays(14))
            ->orderByDesc('game_date')
            ->limit(400)
            ->get();

        return $games->filter(function (Game $game) use ($terms) {
            $haystack = SearchIndex::normalize(implode(' ', [self::gameOptionLabel($game), $game->opponentDisplayName(), $game->game_date?->format('d.m')]));

            return collect($terms)->every(fn (string $term) => str_contains($haystack, $term));
        })->take(8)->values();
    }

    public function delete(int $id): void
    {
        Article::findOrFail($id)->delete();
        $this->flashType = 'success';
        $this->flashMessage = 'Artykuł został usunięty.';
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->excerpt = '';
        $this->content = '';
        $this->status = 'draft';
        $this->published_at = null;
        $this->is_featured = false;
        $this->allow_comments = true;
        $this->featured_image = null;
        $this->existingImage = null;
        $this->gameIds = [];
        $this->gameSearch = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $articles = Article::with('author')
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.admin.article-manager', [
            'articles' => $articles,
            'linkedGames' => $this->showModal ? Game::with(['team', 'opponentTeam'])->whereIn('id', $this->gameIds)->orderBy('game_date')->get() : collect(),
            'gameSuggestions' => $this->showModal ? $this->gameSuggestions() : collect(),
        ]);
    }
}

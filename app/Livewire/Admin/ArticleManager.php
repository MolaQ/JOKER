<?php

namespace App\Livewire\Admin;

use App\AccountStatus;
use App\Livewire\Concerns\FiltersList;
use App\Livewire\Concerns\ShowsToasts;
use App\Livewire\Concerns\SortsTable;
use App\Models\Article;
use App\Models\Game;
use App\Models\Tournament;
use App\Models\User;
use App\Support\SearchIndex;
use App\Support\Slug;
use App\Support\Trash;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ArticleManager extends Component
{
    use FiltersList, ShowsToasts, SortsTable, WithFileUploads, WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $excerpt = '';

    public string $content = '';

    /** Opcjonalna wersja angielska (pokazywana, gdy odwiedzający wybierze angielski). */
    public string $title_en = '';

    public string $excerpt_en = '';

    public string $content_en = '';

    public string $status = 'draft';

    public ?string $published_at = null;

    public bool $is_featured = false;

    public bool $allow_comments = true;

    /** Autor z listy kont (domyślnie osoba, która dodaje artykuł). */
    public string $authorId = '';

    /** Autor spoza społeczności, wpisany ręcznie (ma pierwszeństwo przed listą). */
    public string $authorName = '';

    public $featured_image = null;

    public ?string $existingImage = null;

    /** Punkt ostrości zdjęcia w procentach (lewo-prawo, góra-dół). */
    public int $focusX = 50;

    public int $focusY = 50;

    /**
     * Mecze, których dotyczy artykuł (otwierany z kalendarza meczów).
     *
     * @var array<int, int>
     */
    public array $gameIds = [];

    public string $gameSearch = '';

    /** Turniej, z którego jest relacja (link na stronie artykułu i lista relacji na stronie turnieju). */
    public string $tournamentId = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    #[Url(as: 'o', except: '')]
    public string $linkFilter = '';

    #[Url(as: 'cechy', except: '')]
    public string $extraFilter = '';

    #[Url(as: 'autor', except: '')]
    public string $authorFilter = '';

    /** O czym jest artykuł. */
    public const LINK_FILTERS = [
        'game' => 'O meczu',
        'tournament' => 'O turnieju',
        'mini' => 'Minisiatkówka',
        'none' => 'Bez powiązania',
    ];

    public const EXTRA_FILTERS = [
        'featured' => 'Przypięte',
        'english' => 'Z wersją angielską',
        'pending' => 'Komentarze do akceptacji',
        'no_image' => 'Bez zdjęcia',
        'scheduled' => 'Zaplanowana publikacja',
    ];

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'title_en' => 'nullable|string|max:255',
            'excerpt_en' => 'nullable|string|max:500',
            'content_en' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'is_featured' => 'boolean',
            'allow_comments' => 'boolean',
            'featured_image' => 'nullable|image|max:4096',
            'focusX' => 'integer|between:0,100',
            'focusY' => 'integer|between:0,100',
            'gameIds' => 'array',
            'gameIds.*' => 'integer|exists:games,id',
            'tournamentId' => 'nullable|integer|exists:tournaments,id',
            'authorId' => 'nullable|integer|exists:users,id',
            'authorName' => 'nullable|string|max:120',
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

    /**
     * Link z panelu turnieju („Relacja z wynikami”) otwiera szkic od razu do edycji.
     */
    public function mount(): void
    {
        $id = (int) request()->query('edytuj');

        if ($id && Article::whereKey($id)->exists()) {
            $this->openEditModal($id);
        }
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $article = Article::findOrFail($id);

        $this->editingId = $article->id;
        $this->title = $article->title;
        $this->excerpt = (string) $article->excerpt;
        $this->content = $article->content;
        $this->title_en = (string) $article->title_en;
        $this->excerpt_en = (string) $article->excerpt_en;
        $this->content_en = (string) $article->content_en;
        $this->status = $article->status;
        $this->published_at = $article->published_at?->format('Y-m-d\TH:i');
        $this->is_featured = $article->is_featured;
        $this->allow_comments = $article->allow_comments;
        $this->authorId = (string) $article->author_id;
        $this->authorName = (string) $article->author_name;
        $this->existingImage = $article->featured_image;
        $this->featured_image = null;
        [$this->focusX, $this->focusY] = array_map('intval', explode(' ', str_replace('%', '', $article->imageFocus())));
        $this->gameIds = $article->games()->pluck('games.id')->all();
        $this->gameSearch = '';
        $this->tournamentId = (string) $article->tournament_id;

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

        unset($validated['gameIds'], $validated['gameIds.*'], $validated['focusX'], $validated['focusY'], $validated['authorId'], $validated['authorName'], $validated['tournamentId']);
        $validated['tournament_id'] = $this->tournamentId !== '' ? (int) $this->tournamentId : null;
        $validated['author_id'] = (int) ($this->authorId ?: Auth::id());
        $validated['author_name'] = trim($this->authorName) ?: null;
        $validated['featured_image_focus'] = $this->focusX.'% '.$this->focusY.'%';

        foreach (['title_en', 'excerpt_en', 'content_en'] as $englishField) {
            $validated[$englishField] = filled(strip_tags((string) $validated[$englishField])) ? $validated[$englishField] : null;
        }

        if ($this->editingId) {
            $article = Article::findOrFail($this->editingId);
            $article->update($validated);
            $article->games()->sync($this->gameIds);
            $this->toast('Artykuł został zaktualizowany.');
        } else {
            $validated['slug'] = Slug::unique(Article::class, $this->title);
            Article::create($validated)->games()->sync($this->gameIds);
            $this->toast('Artykuł został dodany.');
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
        $score = $game->isFinished() && $game->ourSetsResult() ? ' '.$game->home_score.':'.$game->away_score : '';

        return trim(($game->game_date?->format('d.m.Y') ?? '').' '.$game->matchup(withClub: false).$score);
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

    /**
     * Pinezka na liście: przypina artykuł na górze aktualności i strony głównej albo go odpina.
     */
    public function toggleFeatured(int $id): void
    {
        $article = Article::findOrFail($id);
        $article->update(['is_featured' => ! $article->is_featured]);
        $this->toast($article->is_featured ? 'Artykuł jest przypięty na górze aktualności.' : 'Artykuł odpięty: stoi teraz według daty publikacji.');
    }

    public function delete(int $id): void
    {
        Trash::delete(Article::findOrFail($id));
        $this->toast('Artykuł trafił do kosza. Przywrócisz go w: System → Kosz.');
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
        $this->title_en = '';
        $this->excerpt_en = '';
        $this->content_en = '';
        $this->status = 'draft';
        $this->published_at = null;
        $this->is_featured = false;
        $this->allow_comments = true;
        $this->authorId = (string) Auth::id();
        $this->authorName = '';
        $this->featured_image = null;
        $this->existingImage = null;
        $this->focusX = 50;
        $this->focusY = 50;
        $this->gameIds = [];
        $this->gameSearch = '';
        $this->tournamentId = '';
        $this->resetErrorBag();
    }

    protected function filterDefaults(): array
    {
        return ['search' => '', 'statusFilter' => '', 'linkFilter' => '', 'extraFilter' => '', 'authorFilter' => ''];
    }

    private function applyListFilters(Builder $articles): Builder
    {
        $term = trim($this->search);

        return $articles
            ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('title', 'like', "%{$term}%")
                ->orWhere('excerpt', 'like', "%{$term}%")
                ->orWhere('content', 'like', "%{$term}%")
                ->orWhere('title_en', 'like', "%{$term}%")
                ->orWhere('author_name', 'like', "%{$term}%")
                ->orWhereHas('author', fn (Builder $author) => $author->where('name', 'like', "%{$term}%"))))
            ->when($this->statusFilter, fn (Builder $q) => $q->where('status', $this->statusFilter))
            ->when($this->authorFilter, fn (Builder $q) => $q->where('author_id', $this->authorFilter))
            ->when($this->linkFilter === 'game', fn (Builder $q) => $q->whereHas('games'))
            ->when($this->linkFilter === 'tournament', fn (Builder $q) => $q->whereNotNull('tournament_id'))
            ->when($this->linkFilter === 'mini', fn (Builder $q) => $q->whereNotNull('mini_trip_id'))
            ->when($this->linkFilter === 'none', fn (Builder $q) => $q->whereDoesntHave('games')->whereNull('tournament_id')->whereNull('mini_trip_id'))
            ->when($this->extraFilter === 'featured', fn (Builder $q) => $q->where('is_featured', true))
            ->when($this->extraFilter === 'english', fn (Builder $q) => $q->whereNotNull('title_en')->where('title_en', '!=', ''))
            ->when($this->extraFilter === 'pending', fn (Builder $q) => $q->whereHas('comments', fn (Builder $c) => $c->where('is_approved', false)))
            ->when($this->extraFilter === 'no_image', fn (Builder $q) => $q->where(fn (Builder $w) => $w->whereNull('featured_image')->orWhere('featured_image', '')))
            ->when($this->extraFilter === 'scheduled', fn (Builder $q) => $q->where('status', 'published')->where('published_at', '>', now()))
            ->tap(fn (Builder $q) => $this->applyDateFilter($q, 'published_at'));
    }

    protected function sortableColumns(): array
    {
        return [
            'data' => fn ($q, $d) => $q->orderByRaw('published_at IS NULL')->orderBy('published_at', $d),
            'tytul' => 'title',
            'autor' => fn ($q, $d) => $q->orderByRaw('coalesce(articles.author_name, (select name from users where users.id = articles.author_id)) '.($d === 'desc' ? 'desc' : 'asc')),
            'status' => 'status',
            'wyswietlenia' => 'views_count',
            'polubienia' => 'likes_count',
            'komentarze' => 'approved_comments_count',
        ];
    }

    protected function sortDescendingFirst(): array
    {
        return ['data', 'wyswietlenia', 'polubienia', 'komentarze'];
    }

    public function render()
    {
        // Liczby do kolumny „Odbiór” i znaczniki transmisji/galerii z meczów, o których jest artykuł.
        $articles = $this->applySort(Article::with(['author', 'games' => fn ($query) => $query->withCount(['galleries' => fn ($galleries) => $galleries->where('is_published', true)])])
            ->withCount(['likes', 'approvedComments', 'comments as pending_comments_count' => fn ($query) => $query->where('is_approved', false)])
            ->tap(fn (Builder $q) => $this->applyListFilters($q))
            ->orderBy('created_at', 'desc')
        )->paginate(15);

        return view('livewire.admin.article-manager', [
            'articles' => $articles,
            // Autorzy, którzy mają artykuły (przyciski filtra).
            'authors' => User::whereIn('id', Article::query()->whereNotNull('author_id')->select('author_id'))->orderBy('name')->pluck('name', 'id')->all(),
            'linkedGames' => $this->showModal ? Game::with(['team', 'opponentTeam'])->whereIn('id', $this->gameIds)->orderBy('game_date')->get() : collect(),
            'gameSuggestions' => $this->showModal ? $this->gameSuggestions() : collect(),
            'tournamentOptions' => $this->showModal ? Tournament::query()->orderByDesc('start_date')->limit(30)->get(['id', 'name', 'edition', 'start_date']) : collect(),
            'authorOptions' => $this->showModal
                ? User::withAccountStatus(AccountStatus::Active)->with('roleAssignments')->orderBy('name')->get(['id', 'name'])
                    ->map(fn (User $user) => ['value' => (string) $user->id, 'label' => $user->name.($user->rolesLabel() ? ' ('.$user->rolesLabel().')' : '')])->all()
                : [],
        ]);
    }
}

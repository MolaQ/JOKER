<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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
        $this->flashType = null;
        $this->flashMessage = null;

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->featured_image) {
            $validated['featured_image'] = $this->featured_image->store('articles', 'public');
        }

        if ($this->editingId) {
            $article = Article::findOrFail($this->editingId);
            $article->update($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Artykuł został zaktualizowany.';
        } else {
            $validated['slug'] = Str::slug($this->title).'-'.Str::random(6);
            $validated['author_id'] = Auth::id();
            Article::create($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Artykuł został dodany.';
        }

        $this->closeModal();
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
        ]);
    }
}

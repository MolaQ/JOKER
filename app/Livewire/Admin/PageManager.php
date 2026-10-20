<?php

namespace App\Livewire\Admin;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Podstrony z tekstem (Dla rodziców, FAQ, O klubie…).
 */
class PageManager extends Component
{
    use WithFileUploads;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $slug = '';

    public string $lead = '';

    public string $content = '';

    /** Opcjonalna wersja angielska (pokazywana, gdy odwiedzający wybierze angielski). */
    public string $title_en = '';

    public string $lead_en = '';

    public string $content_en = '';

    public int $display_order = 0;

    public bool $is_published = true;

    public $image = null;

    public ?string $existingImage = null;

    public ?string $flashMessage = null;

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9-]*$/', Rule::unique('pages', 'slug')->ignore($this->editingId)],
            'lead' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'lead_en' => ['nullable', 'string', 'max:255'],
            'content_en' => ['nullable', 'string'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'title.required' => 'Wpisz tytuł podstrony.',
            'slug.regex' => 'Adres może zawierać tylko małe litery bez polskich znaków, cyfry i myślniki.',
            'slug.unique' => 'Podstrona z takim adresem już istnieje.',
            'image.image' => 'Plik musi być obrazkiem.',
            'image.max' => 'Obrazek może mieć najwyżej 4 MB.',
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->display_order = (int) Page::max('display_order') + 1;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $page = Page::findOrFail($id);

        $this->resetForm();
        $this->editingId = $page->id;
        $this->title = $page->title;
        $this->slug = $page->slug;
        $this->lead = (string) $page->lead;
        $this->content = (string) $page->content;
        $this->title_en = (string) $page->title_en;
        $this->lead_en = (string) $page->lead_en;
        $this->content_en = (string) $page->content_en;
        $this->display_order = (int) $page->display_order;
        $this->is_published = $page->is_published;
        $this->existingImage = $page->image_path;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->slug = Str::slug($this->slug ?: $this->title);
        $validated = $this->validate();
        unset($validated['image']);

        foreach (['title_en', 'lead_en', 'content_en'] as $englishField) {
            $validated[$englishField] = filled(strip_tags((string) $validated[$englishField])) ? $validated[$englishField] : null;
        }

        if ($this->image) {
            $validated['image_path'] = $this->image->store('pages', 'public');
        }

        if ($this->editingId) {
            Page::findOrFail($this->editingId)->update($validated);
            $this->flashMessage = 'Podstrona została zapisana.';
        } else {
            Page::create($validated);
            $this->flashMessage = 'Podstrona została dodana. Adres: /strona/'.$validated['slug'];
        }

        $this->closeModal();
    }

    public function removeImage(): void
    {
        if ($this->editingId) {
            Page::whereKey($this->editingId)->update(['image_path' => null]);
        }

        $this->existingImage = null;
        $this->image = null;
    }

    public function delete(int $id): void
    {
        Page::findOrFail($id)->delete();
        $this->flashMessage = 'Podstrona została usunięta.';
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
        $this->slug = '';
        $this->lead = '';
        $this->content = '';
        $this->title_en = '';
        $this->lead_en = '';
        $this->content_en = '';
        $this->display_order = 0;
        $this->is_published = true;
        $this->image = null;
        $this->existingImage = null;
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.admin.page-manager', [
            'pages' => Page::orderBy('display_order')->orderBy('title')->get(),
        ]);
    }
}

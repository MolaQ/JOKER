<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Page;
use App\Support\Trash;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Podstrony z tekstem (Dla rodziców, FAQ, O klubie…).
 */
class PageManager extends Component
{
    use ShowsToasts, WithFileUploads;

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

    /** Podstrona w grupie, np. KLUB PRO 2026 w „Programy i projekty”. */
    public ?int $parent_id = null;

    public bool $in_menu = true;

    public bool $footer_link = false;

    public bool $image_is_banner = false;

    public string $theme_primary = '';

    public string $theme_accent = '';

    /** @var array<int, array{value: string, label: string}> */
    public array $highlights = [];

    /** Gotowe zestawy kolorów (np. w barwach programu z banera). */
    public const THEMES = [
        'klub-pro' => ['label' => 'KLUB PRO (granat i pomarańcz)', 'primary' => '#1e2a8a', 'accent' => '#ff6b1a'],
        'klub' => ['label' => 'Barwy klubu', 'primary' => '', 'accent' => ''],
    ];

    public $image = null;

    public ?string $existingImage = null;

    public function boot(): void
    {
        abort_unless(auth()->user()?->can('site.manage'), 403);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9-]*$/', Rule::unique('pages', 'slug')->ignore($this->editingId)],
            'lead' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'lead_en' => ['nullable', 'string', 'max:255'],
            'content_en' => ['nullable', 'string'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'parent_id' => ['nullable', 'integer', Rule::exists('pages', 'id')->whereNull('parent_id'), Rule::notIn(array_filter([$this->editingId]))],
            'in_menu' => ['boolean'],
            'footer_link' => ['boolean'],
            'image_is_banner' => ['boolean'],
            'theme_primary' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'highlights' => ['array', 'max:4'],
            'highlights.*.value' => ['nullable', 'string', 'max:30'],
            'highlights.*.label' => ['nullable', 'string', 'max:80'],
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
            'parent_id.exists' => 'Grupą może być tylko podstrona najwyższego poziomu.',
            'parent_id.not_in' => 'Podstrona nie może być grupą dla samej siebie.',
            'theme_primary.regex' => 'Kolor podaj jako #RRGGBB.',
            'theme_accent.regex' => 'Kolor podaj jako #RRGGBB.',
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
        $this->parent_id = $page->parent_id;
        $this->in_menu = $page->in_menu;
        $this->footer_link = $page->footer_link;
        $this->image_is_banner = $page->image_is_banner;
        $this->theme_primary = (string) $page->theme_primary;
        $this->theme_accent = (string) $page->theme_accent;
        $this->highlights = array_values($page->highlights ?? []);
        $this->existingImage = $page->image_path;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->slug = Str::slug($this->slug ?: $this->title);
        $validated = $this->validate();
        unset($validated['image']);
        $validated['parent_id'] = $this->parent_id ?: null;
        if ($validated['parent_id'] && $this->editingId && Page::where('parent_id', $this->editingId)->exists()) {
            throw ValidationException::withMessages(['parent_id' => 'Ta podstrona sama jest grupą (ma podstrony), więc nie może trafić do innej grupy.']);
        }
        $validated['theme_primary'] = $this->theme_primary ?: null;
        $validated['theme_accent'] = $this->theme_accent ?: null;
        $validated['highlights'] = collect($this->highlights)
            ->map(fn ($item) => ['value' => trim((string) ($item['value'] ?? '')), 'label' => trim((string) ($item['label'] ?? ''))])
            ->filter(fn ($item) => $item['value'] !== '')
            ->values()->all() ?: null;

        foreach (['title_en', 'lead_en', 'content_en'] as $englishField) {
            $validated[$englishField] = filled(strip_tags((string) $validated[$englishField])) ? $validated[$englishField] : null;
        }

        if ($this->image) {
            $validated['image_path'] = $this->image->store('pages', 'public');
        }

        if ($this->editingId) {
            Page::findOrFail($this->editingId)->update($validated);
            $this->toast('Podstrona została zapisana.');
        } else {
            Page::create($validated);
            $this->toast('Podstrona została dodana. Adres: /strona/'.$validated['slug']);
        }

        $this->closeModal();
    }

    public function useTheme(string $key): void
    {
        $theme = self::THEMES[$key] ?? null;

        if ($theme) {
            $this->theme_primary = $theme['primary'];
            $this->theme_accent = $theme['accent'];
        }
    }

    public function addHighlight(): void
    {
        if (count($this->highlights) < 4) {
            $this->highlights[] = ['value' => '', 'label' => ''];
        }
    }

    public function removeHighlight(int $index): void
    {
        unset($this->highlights[$index]);
        $this->highlights = array_values($this->highlights);
    }

    /**
     * Strzałki na liście: zamienia miejscami z sąsiednią podstroną na tym samym poziomie.
     */
    public function move(int $id, int $direction): void
    {
        $page = Page::findOrFail($id);
        $siblings = Page::where('parent_id', $page->parent_id)->orderBy('display_order')->orderBy('title')->get()->values();
        $index = $siblings->search(fn (Page $sibling) => $sibling->id === $page->id);
        $target = $index + ($direction < 0 ? -1 : 1);

        if ($target < 0 || $target >= $siblings->count()) {
            return;
        }

        $ordered = $siblings->all();
        [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];

        foreach ($ordered as $position => $sibling) {
            if ($sibling->display_order !== $position + 1) {
                $sibling->update(['display_order' => $position + 1]);
            }
        }
    }

    public function togglePublished(int $id): void
    {
        $page = Page::findOrFail($id);
        $page->update(['is_published' => ! $page->is_published]);
        $this->toast($page->is_published ? 'Podstrona opublikowana: widzą ją wszyscy.' : 'Podstrona ukryta: widzi ją tylko administrator i szef.');
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
        Trash::delete(Page::findOrFail($id));
        $this->toast('Podstrona trafiła do kosza. Przywrócisz ją w: System → Kosz.');
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
        $this->parent_id = null;
        $this->in_menu = true;
        $this->footer_link = false;
        $this->image_is_banner = false;
        $this->theme_primary = '';
        $this->theme_accent = '';
        $this->highlights = [];
        $this->image = null;
        $this->existingImage = null;
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.admin.page-manager', [
            'pages' => Page::whereNull('parent_id')->with('children')->orderBy('display_order')->orderBy('title')->get(),
            'parents' => Page::whereNull('parent_id')->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))->orderBy('display_order')->get(['id', 'title']),
        ]);
    }
}

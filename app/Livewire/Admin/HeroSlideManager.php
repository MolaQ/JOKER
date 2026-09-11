<?php

namespace App\Livewire\Admin;

use App\Models\HeroSlide;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class HeroSlideManager extends Component
{
    use WithFileUploads, WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $subtitle = '';

    public string $cta_label = '';

    public string $cta_url = '';

    public int $display_order = 0;

    public bool $is_active = true;

    public ?string $publish_from = null;

    public ?string $publish_to = null;

    public $image = null;

    public ?string $existingImage = null;

    public string $search = '';

    public ?string $flashType = null;

    public ?string $flashMessage = null;

    protected function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:1000',
            'cta_label' => 'nullable|string|max:40',
            'cta_url' => 'nullable|url|max:255',
            'display_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'publish_from' => 'nullable|date',
            'publish_to' => 'nullable|date|after_or_equal:publish_from',
        ];

        $rules['image'] = $this->editingId ? 'nullable|image|max:4096' : 'required|image|max:4096';

        return $rules;
    }

    public function updatingSearch(): void
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
        $slide = HeroSlide::findOrFail($id);

        $this->editingId = $slide->id;
        $this->title = $slide->title;
        $this->subtitle = (string) $slide->subtitle;
        $this->cta_label = (string) $slide->cta_label;
        $this->cta_url = (string) $slide->cta_url;
        $this->display_order = $slide->display_order;
        $this->is_active = $slide->is_active;
        $this->publish_from = $slide->publish_from?->format('Y-m-d\TH:i');
        $this->publish_to = $slide->publish_to?->format('Y-m-d\TH:i');
        $this->existingImage = $slide->image_path;
        $this->image = null;
        $this->flashType = null;
        $this->flashMessage = null;

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $validated['publish_from'] = $validated['publish_from'] ?: null;
        $validated['publish_to'] = $validated['publish_to'] ?: null;

        if ($this->image) {
            $validated['image_path'] = $this->image->store('hero-slides', 'public');
        }

        if ($this->editingId) {
            $slide = HeroSlide::findOrFail($this->editingId);
            $slide->update($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Slajd został zaktualizowany.';
        } else {
            HeroSlide::create($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Slajd został dodany.';
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        HeroSlide::findOrFail($id)->delete();
        $this->flashType = 'success';
        $this->flashMessage = 'Slajd został usunięty.';
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function sortSlide(int $id, int $position): void
    {
        $slides = HeroSlide::query()->orderBy('display_order')->orderBy('id')->get();

        $target = $slides->firstWhere('id', $id);

        if (! $target) {
            return;
        }

        $orderedIds = $slides
            ->pluck('id')
            ->reject(fn (int $slideId): bool => $slideId === $id)
            ->values();

        $newIndex = max(0, min($position, $orderedIds->count()));
        $orderedIds->splice($newIndex, 0, [$id]);

        foreach ($orderedIds->values() as $index => $slideId) {
            HeroSlide::whereKey($slideId)->update(['display_order' => $index]);
        }

        $this->flashType = 'success';
        $this->flashMessage = 'Kolejność slajdów została zaktualizowana.';
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->subtitle = '';
        $this->cta_label = '';
        $this->cta_url = '';
        $this->display_order = 0;
        $this->is_active = true;
        $this->publish_from = null;
        $this->publish_to = null;
        $this->image = null;
        $this->existingImage = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $slides = HeroSlide::query()
            ->when($this->search, fn ($query) => $query->where('title', 'like', "%{$this->search}%"))
            ->orderBy('display_order')
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.admin.hero-slide-manager', [
            'slides' => $slides,
        ]);
    }
}

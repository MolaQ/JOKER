<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Award;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Panel „Wyróżnienia i certyfikaty”: wpisy w stopce strony, każdy z opcjonalnym logo i linkiem.
 */
class AwardManager extends Component
{
    use ShowsToasts, WithFileUploads;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $note = '';

    public string $url = '';

    public bool $is_active = true;

    public $logo = null;

    public ?string $existingLogo = null;

    public bool $removeLogo = false;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'note' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:255',
            'is_active' => 'boolean',
            'logo' => 'nullable|image|max:4096',
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $award = Award::findOrFail($id);

        $this->resetForm();
        $this->editingId = $award->id;
        $this->name = $award->name;
        $this->note = (string) $award->note;
        $this->url = (string) $award->url;
        $this->is_active = $award->is_active;
        $this->existingLogo = $award->logo_path;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();
        unset($validated['logo']);
        $validated['note'] = trim($validated['note'] ?? '') ?: null;
        $validated['url'] = trim($validated['url'] ?? '') ?: null;

        if ($this->logo) {
            $validated['logo_path'] = $this->logo->store('awards', 'public');
        } elseif ($this->removeLogo) {
            $validated['logo_path'] = null;
        }

        if ($this->editingId) {
            Award::findOrFail($this->editingId)->update($validated);
            $this->toast('Wyróżnienie zapisane.');
        } else {
            Award::create($validated + ['display_order' => (int) Award::max('display_order') + 1]);
            $this->toast('Wyróżnienie dodane. Widać je w stopce strony.');
        }

        $this->closeModal();
    }

    /**
     * Przesuwa wpis o jedno miejsce w górę (-1) albo w dół (1).
     */
    public function move(int $id, int $direction): void
    {
        $awards = Award::orderBy('display_order')->orderBy('id')->get()->values();
        $index = $awards->search(fn (Award $award) => $award->id === $id);
        $target = $index === false ? null : $index + ($direction < 0 ? -1 : 1);

        if ($target === null || ! isset($awards[$target])) {
            return;
        }

        [$awards[$index], $awards[$target]] = [$awards[$target], $awards[$index]];
        $awards->each(fn (Award $award, int $position) => $award->display_order === $position + 1 ?: $award->update(['display_order' => $position + 1]));
    }

    public function delete(int $id): void
    {
        Award::findOrFail($id)->delete();
        $this->toast('Wyróżnienie usunięte.');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->note = '';
        $this->url = '';
        $this->is_active = true;
        $this->logo = null;
        $this->existingLogo = null;
        $this->removeLogo = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.admin.award-manager', [
            'awards' => Award::orderBy('display_order')->orderBy('id')->get(),
        ]);
    }
}

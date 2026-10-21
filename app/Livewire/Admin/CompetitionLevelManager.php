<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\CompetitionLevel;
use Livewire\Component;

class CompetitionLevelManager extends Component
{
    use ShowsToasts;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public int $display_order = 0;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'display_order' => 'nullable|integer|min:0',
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $level = CompetitionLevel::findOrFail($id);

        $this->editingId = $level->id;
        $this->name = $level->name;
        $this->display_order = $level->display_order;

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            CompetitionLevel::findOrFail($this->editingId)->update($validated);
            $this->toast('Poziom rozgrywek został zaktualizowany.');
        } else {
            CompetitionLevel::create($validated);
            $this->toast('Poziom rozgrywek został dodany.');
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        $level = CompetitionLevel::findOrFail($id);

        if ($level->competitions()->count() > 0) {
            $this->toast('Nie można usunąć poziomu, który jest przypisany do rozgrywek.', 'error');

            return;
        }

        $level->delete();
        $this->toast('Poziom rozgrywek został usunięty.');
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
        $this->display_order = 0;
        $this->resetErrorBag();
    }

    public function render()
    {
        $levels = CompetitionLevel::withCount('competitions')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.competition-level-manager', [
            'levels' => $levels,
        ]);
    }
}

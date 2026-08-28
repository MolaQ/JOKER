<?php

namespace App\Livewire\Admin;

use App\Models\CompetitionLevel;
use Livewire\Component;

class CompetitionLevelManager extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public int $display_order = 0;

    public ?string $flashType = null;

    public ?string $flashMessage = null;

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
        $this->flashType = null;
        $this->flashMessage = null;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $level = CompetitionLevel::findOrFail($id);

        $this->editingId = $level->id;
        $this->name = $level->name;
        $this->display_order = $level->display_order;
        $this->flashType = null;
        $this->flashMessage = null;

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            CompetitionLevel::findOrFail($this->editingId)->update($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Poziom rozgrywek został zaktualizowany.';
        } else {
            CompetitionLevel::create($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Poziom rozgrywek został dodany.';
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        $level = CompetitionLevel::findOrFail($id);

        if ($level->competitions()->count() > 0) {
            $this->flashType = 'error';
            $this->flashMessage = 'Nie można usunąć poziomu, który jest przypisany do rozgrywek.';

            return;
        }

        $level->delete();
        $this->flashType = 'success';
        $this->flashMessage = 'Poziom rozgrywek został usunięty.';
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

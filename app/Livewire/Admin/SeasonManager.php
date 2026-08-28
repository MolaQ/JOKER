<?php

namespace App\Livewire\Admin;

use App\Models\Season;
use Livewire\Component;

class SeasonManager extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $start_date = '';

    public string $end_date = '';

    public bool $is_current = false;

    public ?string $flashType = null;

    public ?string $flashMessage = null;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
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
        $season = Season::findOrFail($id);

        $this->editingId = $season->id;
        $this->name = $season->name;
        $this->start_date = $season->start_date?->format('Y-m-d');
        $this->end_date = $season->end_date?->format('Y-m-d');
        $this->is_current = $season->is_current;
        $this->flashType = null;
        $this->flashMessage = null;

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($validated['is_current']) {
            Season::where('is_current', true)->update(['is_current' => false]);
        }

        if ($this->editingId) {
            Season::findOrFail($this->editingId)->update($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Sezon został zaktualizowany.';
        } else {
            Season::create($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Sezon został dodany.';
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        Season::findOrFail($id)->delete();
        $this->flashType = 'success';
        $this->flashMessage = 'Sezon został usunięty.';
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
        $this->start_date = '';
        $this->end_date = '';
        $this->is_current = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        $seasons = Season::orderBy('start_date', 'desc')->get();

        return view('livewire.admin.season-manager', [
            'seasons' => $seasons,
        ]);
    }
}

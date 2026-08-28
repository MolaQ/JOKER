<?php

namespace App\Livewire\Admin;

use App\Models\RivalTeam;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class RivalTeamManager extends Component
{
    use WithFileUploads, WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $full_name = '';

    public string $short_name = '';

    public string $category = 'senior';

    public $logo = null;

    public ?string $existingLogo = null;

    public string $search = '';

    public ?string $flashType = null;

    public ?string $flashMessage = null;

    protected function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:255',
            'category' => 'nullable|in:senior,junior,junior_mlodszy,mlodzik',
            'logo' => 'nullable|image|max:2048',
        ];
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
        $rivalTeam = RivalTeam::findOrFail($id);

        $this->editingId = $rivalTeam->id;
        $this->full_name = $rivalTeam->full_name;
        $this->short_name = (string) $rivalTeam->short_name;
        $this->category = (string) $rivalTeam->category ?: 'senior';
        $this->existingLogo = $rivalTeam->logo_path;
        $this->logo = null;
        $this->flashType = null;
        $this->flashMessage = null;

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->logo) {
            $validated['logo_path'] = $this->logo->store('rival-teams', 'public');
        }

        if ($this->editingId) {
            RivalTeam::findOrFail($this->editingId)->update($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Drużyna rywali została zaktualizowana.';
        } else {
            RivalTeam::create($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Drużyna rywali została dodana.';
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        RivalTeam::findOrFail($id)->delete();
        $this->flashType = 'success';
        $this->flashMessage = 'Drużyna rywali została usunięta.';
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->full_name = '';
        $this->short_name = '';
        $this->category = 'senior';
        $this->logo = null;
        $this->existingLogo = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $rivalTeams = RivalTeam::when($this->search, fn ($q) => $q->where('full_name', 'like', "%{$this->search}%")
            ->orWhere('short_name', 'like', "%{$this->search}%"))
            ->orderBy('full_name')
            ->paginate(10);

        return view('livewire.admin.rival-team-manager', [
            'rivalTeams' => $rivalTeams,
        ]);
    }
}

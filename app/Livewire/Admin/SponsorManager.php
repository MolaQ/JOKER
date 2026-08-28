<?php

namespace App\Livewire\Admin;

use App\Models\Sponsor;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class SponsorManager extends Component
{
    use WithFileUploads, WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $website_url = '';

    public string $tier = 'bronze';

    public int $display_order = 0;

    public bool $is_active = true;

    public $logo = null;

    public ?string $existingLogo = null;

    public string $search = '';

    public ?string $flashType = null;

    public ?string $flashMessage = null;

    protected function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'website_url' => 'nullable|url',
            'tier' => 'required|in:platinum,gold,silver,bronze',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ];

        $rules['logo'] = $this->editingId ? 'nullable|image|max:2048' : 'required|image|max:2048';

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
        $sponsor = Sponsor::findOrFail($id);

        $this->editingId = $sponsor->id;
        $this->name = $sponsor->name;
        $this->description = (string) $sponsor->description;
        $this->website_url = (string) $sponsor->website_url;
        $this->tier = $sponsor->tier;
        $this->display_order = $sponsor->display_order;
        $this->is_active = $sponsor->is_active;
        $this->existingLogo = $sponsor->logo_path;
        $this->logo = null;
        $this->flashType = null;
        $this->flashMessage = null;

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->logo) {
            $validated['logo_path'] = $this->logo->store('sponsors', 'public');
        }

        if ($this->editingId) {
            $sponsor = Sponsor::findOrFail($this->editingId);
            $sponsor->update($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Sponsor został zaktualizowany.';
        } else {
            Sponsor::create($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Sponsor został dodany.';
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        Sponsor::findOrFail($id)->delete();
        $this->flashType = 'success';
        $this->flashMessage = 'Sponsor został usunięty.';
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
        $this->description = '';
        $this->website_url = '';
        $this->tier = 'bronze';
        $this->display_order = 0;
        $this->is_active = true;
        $this->logo = null;
        $this->existingLogo = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $sponsors = Sponsor::when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('display_order')
            ->paginate(10);

        return view('livewire.admin.sponsor-manager', [
            'sponsors' => $sponsors,
        ]);
    }
}

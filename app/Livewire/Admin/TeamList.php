<?php

namespace App\Livewire\Admin;

use App\Models\Team;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TeamList extends Component
{
    use WithFileUploads, WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $category = 'senior';

    public ?int $birth_year = null;

    public ?int $trainer_id = null;

    /** @var array<int, int> */
    public array $assistant_trainer_ids = [];

    public int $display_order = 0;

    public $logo = null;

    public ?string $existingLogo = null;

    public string $search = '';

    public ?string $flashType = null;

    public ?string $flashMessage = null;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:senior,junior,junior_mlodszy,mlodzik',
            'birth_year' => 'nullable|integer|min:1990|max:2020',
            'trainer_id' => 'nullable|exists:users,id',
            'assistant_trainer_ids' => 'array',
            'assistant_trainer_ids.*' => 'exists:users,id',
            'display_order' => 'nullable|integer|min:0',
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
        $team = Team::findOrFail($id);

        $this->editingId = $team->id;
        $this->name = $team->name;
        $this->description = (string) $team->description;
        $this->category = $team->category;
        $this->birth_year = $team->birth_year;
        $this->trainer_id = $team->trainer_id;
        $this->assistant_trainer_ids = $team->assistantTrainers()->pluck('users.id')->all();
        $this->display_order = $team->display_order;
        $this->existingLogo = $team->logo_path;
        $this->logo = null;
        $this->flashType = null;
        $this->flashMessage = null;

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();
        $assistantTrainerIds = $validated['assistant_trainer_ids'] ?? [];
        unset($validated['assistant_trainer_ids']);

        if ($this->logo) {
            $validated['logo_path'] = $this->logo->store('teams', 'public');
        }

        if ($this->editingId) {
            $team = Team::findOrFail($this->editingId);
            $team->update($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Drużyna została zaktualizowana.';
        } else {
            $validated['slug'] = Str::slug($this->name).'-'.Str::random(4);
            $team = Team::create($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Drużyna została dodana.';
        }

        $team->assistantTrainers()->sync(array_diff($assistantTrainerIds, [$team->trainer_id]));

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        $team = Team::findOrFail($id);

        if ($team->players()->count() > 0) {
            $this->flashType = 'error';
            $this->flashMessage = 'Nie można usunąć drużyny, która ma przypisanych zawodników.';

            return;
        }

        $team->delete();
        $this->flashType = 'success';
        $this->flashMessage = 'Drużyna została usunięta.';
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
        $this->category = 'senior';
        $this->birth_year = null;
        $this->trainer_id = null;
        $this->assistant_trainer_ids = [];
        $this->display_order = 0;
        $this->logo = null;
        $this->existingLogo = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $teams = Team::withCount('players')
            ->with(['trainer', 'assistantTrainers'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('display_order')
            ->paginate(10);

        $trainers = User::where('role', UserRole::Trainer)->orderBy('name')->get();

        return view('livewire.admin.team-list', [
            'teams' => $teams,
            'trainers' => $trainers,
        ]);
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\Team;
use App\Models\User;
use App\Support\SearchIndex;
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

    /**
     * Funkcja w sztabie dla zaznaczonych osób, np. [5 => 'Fizjoterapeuta'].
     *
     * @var array<int, string>
     */
    public array $staff_roles = [];

    public int $display_order = 0;

    public $logo = null;

    public ?string $existingLogo = null;

    public $photo = null;

    public ?string $existingPhoto = null;

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
            'staff_roles' => 'array',
            'staff_roles.*' => 'nullable|string|max:60',
            'logo' => 'nullable|image|max:2048',
            'photo' => 'nullable|image|max:5120',
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
        $assistants = $team->assistantTrainers()->get();
        $this->assistant_trainer_ids = $assistants->pluck('id')->all();
        $this->staff_roles = $assistants->mapWithKeys(fn ($user) => [$user->id => (string) $user->pivot->role])->all();
        $this->display_order = $team->display_order;
        $this->existingLogo = $team->logo_path;
        $this->logo = null;
        $this->existingPhoto = $team->photo_path;
        $this->photo = null;
        $this->flashType = null;
        $this->flashMessage = null;

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();
        $assistantTrainerIds = $validated['assistant_trainer_ids'] ?? [];
        $staffRoles = $validated['staff_roles'] ?? [];
        unset($validated['assistant_trainer_ids'], $validated['staff_roles'], $validated['logo'], $validated['photo']);

        if ($this->logo) {
            $validated['logo_path'] = $this->logo->store('teams', 'public');
        }

        if ($this->photo) {
            $validated['photo_path'] = $this->photo->store('teams/photos', 'public');
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

        $team->assistantTrainers()->sync(
            collect($assistantTrainerIds)
                ->reject(fn ($userId) => (int) $userId === $team->trainer_id)
                ->mapWithKeys(fn ($userId) => [$userId => ['role' => filled($staffRoles[$userId] ?? null) ? trim($staffRoles[$userId]) : null]])
                ->all()
        );
        SearchIndex::forget();

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
        $this->staff_roles = [];
        $this->display_order = 0;
        $this->logo = null;
        $this->existingLogo = null;
        $this->photo = null;
        $this->existingPhoto = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $teams = Team::withCount('players')
            ->with(['trainer', 'assistantTrainers'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('display_order')
            ->paginate(10);

        $trainers = User::whereIn('role', [UserRole::Trainer, UserRole::Staff, UserRole::Admin])->orderBy('name')->get();

        return view('livewire.admin.team-list', [
            'teams' => $teams,
            'trainers' => $trainers,
        ]);
    }
}

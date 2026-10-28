<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Livewire\Concerns\SortsTable;
use App\Models\Team;
use App\Models\User;
use App\Support\SearchIndex;
use App\Support\Slug;
use App\UserRole;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TeamList extends Component
{
    use ShowsToasts, SortsTable, WithFileUploads, WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $category = 'senior';

    public ?int $birth_year = null;

    /** Ostatni rocznik, gdy drużyna łączy kilka (np. 2010–2011). */
    public ?int $birth_year_to = null;

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

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:senior,junior,junior_mlodszy,mlodzik',
            'birth_year' => 'nullable|integer|min:1990|max:2030',
            'birth_year_to' => 'nullable|integer|min:1990|max:2030|gte:birth_year',
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

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $team = Team::managedBy(Auth::user())->findOrFail($id);

        $this->editingId = $team->id;
        $this->name = $team->name;
        $this->description = (string) $team->description;
        $this->category = $team->category;
        $this->birth_year = $team->birth_year;
        $this->birth_year_to = $team->birth_year_to;
        $this->trainer_id = $team->trainer_id;
        $assistants = $team->assistantTrainers()->get();
        $this->assistant_trainer_ids = $assistants->pluck('id')->all();
        $this->staff_roles = $assistants->mapWithKeys(fn ($user) => [$user->id => (string) $user->pivot->role])->all();
        $this->display_order = $team->display_order;
        $this->existingLogo = $team->logo_path;
        $this->logo = null;
        $this->existingPhoto = $team->photo_path;
        $this->photo = null;

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
            $team = Team::managedBy(Auth::user())->findOrFail($this->editingId);
            $team->update($validated);
            $this->toast('Drużyna została zaktualizowana.');
        } else {
            abort_unless(Auth::user()->can('teams.all'), 403);
            $validated['slug'] = Slug::unique(Team::class, $this->name);
            $team = Team::create($validated);
            $this->toast('Drużyna została dodana.');
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
        $team = Team::managedBy(Auth::user())->findOrFail($id);

        if ($team->players()->count() > 0) {
            $this->toast('Nie można usunąć drużyny, która ma przypisanych zawodników.', 'error');

            return;
        }

        $team->delete();
        $this->toast('Drużyna została usunięta.');
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
        $this->birth_year_to = null;
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

    protected function sortableColumns(): array
    {
        return [
            'nazwa' => 'name',
            'rocznik' => 'birth_year',
            'trener' => fn ($q, $d) => $q->orderBy(User::select('name')->whereColumn('users.id', 'teams.trainer_id'), $d),
            'zawodnicy' => 'players_count',
            'wyswietlenia' => 'views_count',
            'polubienia' => 'likes_count',
        ];
    }

    protected function sortDescendingFirst(): array
    {
        return ['zawodnicy', 'wyswietlenia', 'polubienia'];
    }

    public function render()
    {
        $teams = $this->applySort(Team::managedBy(Auth::user())->withCount(['players', 'likes'])
            ->with(['trainer', 'assistantTrainers'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('display_order')
        )->paginate(10);

        $staffIds = User::withRole(UserRole::Trainer, UserRole::Staff, UserRole::Boss, UserRole::Admin)->pluck('id');
        // Osoby przypisane do drużyny, którym zabrano rolę sztabu (np. rodzic, który chwilowo uzupełniał skład),
        // zostają na liście, żeby dało się je odznaczyć.
        $assigned = collect($this->assistant_trainer_ids)->push($this->trainer_id)->filter()->map(fn ($id) => (int) $id);
        $trainers = User::whereIn('id', $staffIds->merge($assigned)->unique())->orderBy('name')->get();

        return view('livewire.admin.team-list', [
            'teams' => $teams,
            'trainers' => $trainers,
            'staffIds' => $staffIds->all(),
        ]);
    }
}

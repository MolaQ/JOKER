<?php

namespace App\Livewire\Admin;

use App\AccountStatus;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class UserManager extends Component
{
    use ShowsToasts, WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    /** @var list<string> */
    public array $selectedRoles = [];

    public bool $is_active = true;

    public ?string $birth_date = null;

    /** Jak pokazać urodziny w kalendarzu meczów: ukryte, sam torcik albo z wiekiem. */
    public string $birthday_display = 'age';

    public string $search = '';

    public string $roleFilter = '';

    public string $statusFilter = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            'password' => $this->editingId ? 'nullable|string|min:8' : 'required|string|min:8',
            'is_active' => 'boolean',
            'selectedRoles' => 'array',
            'selectedRoles.*' => Rule::in(array_map(fn (UserRole $role) => $role->value, $this->assignableRoles())),
            'birth_date' => 'nullable|date|before:today',
            'birthday_display' => 'required|in:hidden,cake,age',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
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
        $user = User::findOrFail($id);

        if (! $this->mayEdit($user)) {
            $this->toast('Konto administratora zmienia tylko administrator.', 'error');

            return;
        }

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->selectedRoles = $user->roles()->map->value->all();
        $this->is_active = $user->is_active;
        $this->birth_date = $user->birth_date?->format('Y-m-d');
        $this->birthday_display = $user->birthday_display?->value ?? 'age';

        $this->showModal = true;
    }

    /**
     * Role, które może nadać zalogowana osoba: rolę administratora nadaje tylko administrator.
     *
     * @return list<UserRole>
     */
    public function assignableRoles(): array
    {
        return array_values(array_filter(UserRole::cases(), fn (UserRole $role) => $role !== UserRole::Admin || Auth::user()->isAdmin()));
    }

    private function mayEdit(User $user): bool
    {
        return Auth::user()->isAdmin() || ! $user->isAdmin();
    }

    public function save(): void
    {
        $validated = $this->validate();
        $roles = $validated['selectedRoles'] ?? [];
        $active = $validated['is_active'];
        unset($validated['selectedRoles'], $validated['is_active']);

        if ($this->editingId === Auth::id() && ! $active) {
            $this->addError('is_active', 'Nie możesz wyłączyć własnego konta.');

            return;
        }

        if ($this->editingId === Auth::id() && Auth::user()->isAdmin() && ! in_array(UserRole::Admin->value, $roles, true)) {
            $this->addError('selectedRoles', 'Nie możesz odebrać sobie roli administratora.');

            return;
        }

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            abort_unless($this->mayEdit($user), 403);
            $user->update($validated);
            $user->syncRoles($roles);
            $active ? $user->activate() : $user->deactivate('wyłączone w panelu');
            $this->toast('Użytkownik został zaktualizowany.');
        } else {
            $validated['email_verified_at'] = now();
            $user = User::create($validated);
            $user->syncRoles($roles);

            if ($active) {
                $user->activate();
            }
            $this->toast('Użytkownik został dodany.');
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        if ($id === Auth::id()) {
            $this->toast('Nie możesz usunąć własnego konta.', 'error');

            return;
        }

        $user = User::findOrFail($id);

        if (! $this->mayEdit($user)) {
            $this->toast('Konto administratora usuwa tylko administrator.', 'error');

            return;
        }

        $user->delete();
        $this->toast('Użytkownik został usunięty.');
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
        $this->email = '';
        $this->password = '';
        $this->selectedRoles = [];
        $this->is_active = true;
        $this->birth_date = null;
        $this->birthday_display = 'age';
        $this->resetErrorBag();
    }

    public function render()
    {
        $users = User::when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
            ->where('name', 'like', "%{$this->search}%")
            ->orWhere('email', 'like', "%{$this->search}%")
        ))
            ->when($this->roleFilter === 'none', fn ($q) => $q->doesntHave('roleAssignments'))
            ->when(UserRole::tryFrom($this->roleFilter), fn ($q, UserRole $role) => $q->withRole($role))
            ->when(AccountStatus::tryFrom($this->statusFilter), fn ($q, AccountStatus $status) => $q->withAccountStatus($status))
            ->with('roleAssignments')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.user-manager', [
            'users' => $users,
            'roleOptions' => UserRole::cases(),
            'statusOptions' => AccountStatus::cases(),
            'assignableRoles' => $this->assignableRoles(),
        ]);
    }
}

<?php

namespace App\Livewire\Admin;

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

    public string $role = 'fan';

    public ?string $birth_date = null;

    /** Jak pokazać urodziny w kalendarzu meczów: ukryte, sam torcik albo z wiekiem. */
    public string $birthday_display = 'age';

    public string $search = '';

    public string $roleFilter = '';

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
            'role' => 'required|in:admin,trainer,staff,parent,player,fan,guest',
            'birth_date' => 'nullable|date|before:today',
            'birthday_display' => 'required|in:hidden,cake,age',
        ];
    }

    public function updatingSearch(): void
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

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->role->value;
        $this->birth_date = $user->birth_date?->format('Y-m-d');
        $this->birthday_display = $user->birthday_display?->value ?? 'age';

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->update($validated);
            $this->toast('Użytkownik został zaktualizowany.');
        } else {
            $validated['email_verified_at'] = now();
            User::create($validated);
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

        User::findOrFail($id)->delete();
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
        $this->role = 'fan';
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
            ->when($this->roleFilter, fn ($q) => $q->where('role', $this->roleFilter))
            ->orderBy('name')
            ->paginate(15);

        $roles = UserRole::cases();

        return view('livewire.admin.user-manager', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }
}

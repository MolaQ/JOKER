<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class UserManager extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'fan';

    public string $search = '';

    public string $roleFilter = '';

    public ?string $flashType = null;

    public ?string $flashMessage = null;

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
            'role' => 'required|in:admin,trainer,parent,player,fan,guest',
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
        $this->flashType = null;
        $this->flashMessage = null;
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
        $this->flashType = null;
        $this->flashMessage = null;

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
            $this->flashType = 'success';
            $this->flashMessage = 'Użytkownik został zaktualizowany.';
        } else {
            $validated['email_verified_at'] = now();
            User::create($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Użytkownik został dodany.';
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        if ($id === Auth::id()) {
            $this->flashType = 'error';
            $this->flashMessage = 'Nie możesz usunąć własnego konta.';

            return;
        }

        User::findOrFail($id)->delete();
        $this->flashType = 'success';
        $this->flashMessage = 'Użytkownik został usunięty.';
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

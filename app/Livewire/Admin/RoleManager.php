<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\EditsPermissionOverrides;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\AuditLog;
use App\Models\RolePermission;
use App\Models\User;
use App\Permission;
use App\UserRole;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Panel "Role i uprawnienia": co może każda rola i wyjątki dla pojedynczych kont.
 */
class RoleManager extends Component
{
    use EditsPermissionOverrides, ShowsToasts;

    public string $userSearch = '';

    public ?int $userId = null;

    /**
     * Rolę administratora i szefa zmienia tylko administrator, żeby nikt nie odciął sobie panelu.
     */
    public function mayEditRole(UserRole $role): bool
    {
        return $role !== UserRole::Admin && (Auth::user()->isAdmin() || $role !== UserRole::Boss);
    }

    public function toggle(string $role, string $permission): void
    {
        $role = UserRole::from($role);
        $permission = Permission::from($permission);
        abort_unless($this->mayEditRole($role), 403);

        $current = collect(RolePermission::map()[$role->value] ?? [])->map(fn (string $value) => Permission::from($value));
        $granted = ! $current->contains($permission);
        RolePermission::syncRole($role, ($granted ? $current->push($permission) : $current->reject(fn (Permission $item) => $item === $permission))->values()->all());

        AuditLog::record('permissions.role', ($granted ? 'Nadano' : 'Odebrano').' roli '.$role->label().': '.$permission->label(), null, ['rola' => $role->value, 'uprawnienie' => $permission->value]);
        $this->toast(($granted ? 'Rola '.$role->label().' ma teraz: ' : 'Rola '.$role->label().' nie ma już: ').$permission->label());
    }

    public function selectUser(int $id): void
    {
        $this->userId = $id;
        $this->userSearch = '';
    }

    public function setOverride(string $permission, string $mode): void
    {
        $this->applyOverride(User::findOrFail($this->userId), $permission, $mode);
    }

    public function setOverrideExpiry(string $permission, ?string $date): void
    {
        $this->applyOverrideExpiry(User::findOrFail($this->userId), $permission, $date);
    }

    public function render()
    {
        $map = RolePermission::map();
        $user = $this->userId ? User::find($this->userId) : null;

        return view('livewire.admin.role-manager', $this->overrideViewData($user) + [
            'roles' => UserRole::cases(),
            'map' => $map,
            'matches' => strlen(trim($this->userSearch)) >= 2
                ? User::where(fn ($query) => $query->where('name', 'like', '%'.trim($this->userSearch).'%')->orWhere('email', 'like', '%'.trim($this->userSearch).'%'))->with('roleAssignments')->orderBy('name')->limit(8)->get()
                : collect(),
            'user' => $user,
            'log' => AuditLog::where(fn ($query) => $query->where('action', 'like', 'roles.%')->orWhere('action', 'like', 'permissions.%'))->with('actor')->latest('id')->limit(15)->get(),
        ]);
    }
}

<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\AuditLog;
use App\Models\PermissionOverride;
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
    use ShowsToasts;

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

    public function mayEditUser(User $user): bool
    {
        return ! $user->isAdmin() && $user->id !== Auth::id();
    }

    /**
     * Ustawia wyjątek dla konta: "grant" nadaje, "deny" odbiera, "role" wraca do uprawnień z ról.
     */
    public function setOverride(string $permission, string $mode): void
    {
        $user = User::findOrFail($this->userId);
        $permission = Permission::from($permission);
        abort_unless($this->mayEditUser($user), 403);

        if ($mode === 'role') {
            PermissionOverride::where('user_id', $user->id)->where('permission', $permission)->delete();
        } else {
            PermissionOverride::updateOrCreate(['user_id' => $user->id, 'permission' => $permission], ['allowed' => $mode === 'grant']);
        }

        $label = ['grant' => 'nadano', 'deny' => 'odebrano', 'role' => 'przywrócono jak w roli'][$mode] ?? $mode;
        AuditLog::record('permissions.user', "Kontu {$user->name} {$label}: ".$permission->label(), $user, ['uprawnienie' => $permission->value, 'tryb' => $mode]);
        $this->toast('Zapisano wyjątek dla '.$user->name.'.');
    }

    public function render()
    {
        $map = RolePermission::map();
        $user = $this->userId ? User::with(['roleAssignments', 'permissionOverrides'])->find($this->userId) : null;

        $rolePermissions = $user
            ? $user->roles()->flatMap(fn (UserRole $role) => $map[$role->value] ?? [])->unique()->all()
            : [];

        return view('livewire.admin.role-manager', [
            'roles' => UserRole::cases(),
            'permissionGroups' => collect(Permission::cases())->groupBy(fn (Permission $permission) => $permission->group()),
            'map' => $map,
            'matches' => strlen(trim($this->userSearch)) >= 2
                ? User::where(fn ($query) => $query->where('name', 'like', '%'.trim($this->userSearch).'%')->orWhere('email', 'like', '%'.trim($this->userSearch).'%'))->with('roleAssignments')->orderBy('name')->limit(8)->get()
                : collect(),
            'user' => $user,
            'rolePermissions' => $rolePermissions,
            'overrides' => $user ? $user->permissionOverrides->mapWithKeys(fn (PermissionOverride $override) => [$override->permission->value => $override->allowed])->all() : [],
            'log' => AuditLog::with('actor')->latest('id')->limit(15)->get(),
        ]);
    }
}

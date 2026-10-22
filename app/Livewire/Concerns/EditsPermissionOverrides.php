<?php

namespace App\Livewire\Concerns;

use App\Models\AuditLog;
use App\Models\PermissionOverride;
use App\Models\User;
use App\Permission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Wyjątki w uprawnieniach jednego konta (nadaj / odbierz / jak w roli),
 * wspólne dla „Role i uprawnienia” i edycji konta w „Użytkownicy”.
 */
trait EditsPermissionOverrides
{
    /**
     * Wyjątki ustawia ktoś z prawem do ról, nie administratorowi i nie sobie.
     */
    public function mayEditUser(User $user): bool
    {
        return Auth::user()->can(Permission::Roles->value) && ! $user->isAdmin() && $user->id !== Auth::id();
    }

    /**
     * "grant" nadaje, "deny" odbiera, "role" wraca do uprawnień z ról.
     */
    protected function applyOverride(User $user, string $permission, string $mode): void
    {
        $permission = Permission::from($permission);
        abort_unless($this->mayEditUser($user) && in_array($mode, ['role', 'grant', 'deny'], true), 403);

        if ($mode === 'role') {
            PermissionOverride::where('user_id', $user->id)->where('permission', $permission)->delete();
        } else {
            PermissionOverride::updateOrCreate(['user_id' => $user->id, 'permission' => $permission], ['allowed' => $mode === 'grant']);
        }

        $label = ['grant' => 'nadano', 'deny' => 'odebrano', 'role' => 'przywrócono jak w roli'][$mode];
        AuditLog::record('permissions.user', "Kontu {$user->name} {$label}: ".$permission->label(), $user, ['uprawnienie' => $permission->value, 'tryb' => $mode]);
        $this->toast('Zapisano uprawnienia konta '.$user->name.'.');
    }

    /**
     * Dane do widoku listy uprawnień konta.
     *
     * @return array{permissionGroups: Collection, rolePermissions: list<string>, overrides: array<string, bool>}
     */
    protected function overrideViewData(?User $user): array
    {
        $user?->loadMissing(['roleAssignments', 'permissionOverrides']);

        return [
            'permissionGroups' => collect(Permission::cases())->sortBy(fn (Permission $permission) => $permission->isPublic() ? 0 : 1)->groupBy(fn (Permission $permission) => $permission->group()),
            'rolePermissions' => $user ? $user->rolePermissionValues()->all() : [],
            'overrides' => $user ? $user->permissionOverrides->mapWithKeys(fn (PermissionOverride $override) => [$override->permission->value => $override->allowed])->all() : [],
        ];
    }
}

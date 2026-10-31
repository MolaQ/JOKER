<?php

namespace App\Livewire\Concerns;

use App\Models\AuditLog;
use App\Models\PermissionOverride;
use App\Models\User;
use App\Permission;
use App\Support\AccessHistory;
use Illuminate\Support\Carbon;
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
        AccessHistory::remember($user, 'Przed zmianą: '.$permission->label());

        if ($mode === 'role') {
            PermissionOverride::where('user_id', $user->id)->where('permission', $permission)->delete();
        } else {
            PermissionOverride::updateOrCreate(['user_id' => $user->id, 'permission' => $permission], ['allowed' => $mode === 'grant', 'expires_at' => null]);
        }

        $label = ['grant' => 'nadano', 'deny' => 'odebrano', 'role' => 'przywrócono jak w roli'][$mode];
        AuditLog::record('permissions.user', "Kontu {$user->name} {$label}: ".$permission->label(), $user, ['uprawnienie' => $permission->value, 'tryb' => $mode]);
        $this->toast('Zapisano uprawnienia konta '.$user->name.'.');
    }

    /**
     * Data „ważne do” wyjątku (pusta = bezterminowo). Wyjątek działa do końca podanego dnia.
     */
    protected function applyOverrideExpiry(User $user, string $permission, ?string $date): void
    {
        $permission = Permission::from($permission);
        abort_unless($this->mayEditUser($user), 403);

        $override = PermissionOverride::where('user_id', $user->id)->where('permission', $permission)->active()->first();
        $until = filled($date) ? Carbon::parse($date)->endOfDay() : null;

        if (! $override || $override->expires_at?->toDateString() === $until?->toDateString()) {
            return;
        }

        if (! $until?->isPast()) {
            AccessHistory::remember($user, 'Przed zmianą daty: '.$permission->label());
        }

        if ($until?->isPast()) {
            $this->toast('Data „ważne do” musi być dzisiejsza albo późniejsza.', 'error');

            return;
        }

        $before = $override->expires_at?->format('d.m.Y') ?? 'bezterminowo';
        $override->update(['expires_at' => $until]);
        $after = $until?->format('d.m.Y') ?? 'bezterminowo';

        AuditLog::record('permissions.expiry', "Kontu {$user->name} ".($override->allowed ? 'nadane' : 'odebrane').' „'.$permission->label().'”: '.($until ? 'do '.$after : 'bezterminowo'), $user, [
            'uprawnienie' => $permission->value,
            'zmiany' => ['ważne do' => [$before, $after]],
        ]);
        $this->toast('Zapisano datę dla konta '.$user->name.'.');
    }

    /**
     * Dane do widoku listy uprawnień konta.
     *
     * @return array{permissionGroups: Collection, rolePermissions: list<string>, overrides: array<string, bool>, overrideExpiries: array<string, string>}
     */
    protected function overrideViewData(?User $user): array
    {
        $user?->loadMissing(['roleAssignments', 'permissionOverrides']);

        return [
            'permissionGroups' => collect(Permission::cases())->sortBy(fn (Permission $permission) => $permission->isPublic() ? 0 : 1)->groupBy(fn (Permission $permission) => $permission->group()),
            'rolePermissions' => $user ? $user->rolePermissionValues()->all() : [],
            'overrides' => $user ? $user->permissionOverrides->mapWithKeys(fn (PermissionOverride $override) => [$override->permission->value => $override->allowed])->all() : [],
            'overrideExpiries' => $user ? $user->permissionOverrides->filter->expires_at->mapWithKeys(fn (PermissionOverride $override) => [$override->permission->value => $override->expires_at->toDateString()])->all() : [],
        ];
    }
}

<?php

namespace App\Support;

use App\Models\AccessSnapshot;
use App\Models\AuditLog;
use App\Models\PermissionOverride;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Permission;
use App\UserRole;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Historia uprawnień konta: przed każdą zmianą ról, wyjątków albo drużyn koordynatora zapisujemy stan,
 * żeby pomyłkę dało się cofnąć jednym kliknięciem („Przywróć ten stan”).
 */
class AccessHistory
{
    /** Ile ostatnich stanów trzymamy dla jednego konta. */
    public const KEEP = 30;

    /**
     * @return array{roles: array<string, ?string>, overrides: array<string, array{allowed: bool, expires_at: ?string}>, teams: list<int>}
     */
    public static function capture(User $user): array
    {
        $roles = RoleAssignment::where('user_id', $user->id)->get()
            ->reject(fn (RoleAssignment $assignment) => $assignment->expires_at?->isPast())
            ->mapWithKeys(fn (RoleAssignment $assignment) => [$assignment->role->value => $assignment->expires_at?->toDateString()])
            ->sortKeys()->all();

        $overrides = PermissionOverride::where('user_id', $user->id)->get()
            ->reject(fn (PermissionOverride $override) => $override->expires_at?->isPast())
            ->mapWithKeys(fn (PermissionOverride $override) => [$override->permission->value => ['allowed' => $override->allowed, 'expires_at' => $override->expires_at?->toDateString()]])
            ->sortKeys()->all();

        $teams = $user->coordinatedTeams()->pluck('teams.id')->map(fn ($id) => (int) $id)->sort()->values()->all();

        return ['roles' => $roles, 'overrides' => $overrides, 'teams' => $teams];
    }

    /**
     * Zapamiętuje obecny stan konta przed zmianą (pomija, gdy jest taki sam jak ostatnio zapisany).
     */
    public static function remember(User $user, string $note): void
    {
        $data = self::capture($user);
        $last = AccessSnapshot::where('user_id', $user->id)->latest('id')->first();

        if ($last && $last->data == $data) {
            return;
        }

        AccessSnapshot::create(['user_id' => $user->id, 'actor_id' => Auth::id(), 'note' => mb_substr($note, 0, 160), 'data' => $data]);

        $old = AccessSnapshot::where('user_id', $user->id)->latest('id')->skip(self::KEEP)->take(PHP_INT_MAX)->pluck('id');
        AccessSnapshot::whereIn('id', $old)->delete();
    }

    /**
     * Przywraca zapisany stan (obecny trafia najpierw do historii, więc i to da się cofnąć).
     */
    public static function restore(AccessSnapshot $snapshot): void
    {
        $user = $snapshot->user;
        $data = $snapshot->data;

        DB::transaction(function () use ($user, $data, $snapshot) {
            self::remember($user, 'Przed przywróceniem stanu z '.$snapshot->created_at->format('d.m.Y H:i'));

            $roles = collect($data['roles'] ?? [])->keys()->map(fn (string $role) => UserRole::tryFrom($role))->filter()->values();
            $user->syncRoles($roles->map->value->all());

            foreach ($roles as $role) {
                $until = $data['roles'][$role->value] ?? null;
                RoleAssignment::where('user_id', $user->id)->where('role', $role)->update(['expires_at' => $until ? Carbon::parse($until)->endOfDay() : null]);
            }

            PermissionOverride::where('user_id', $user->id)->delete();

            foreach ($data['overrides'] ?? [] as $permission => $override) {
                if (Permission::tryFrom($permission)) {
                    PermissionOverride::create([
                        'user_id' => $user->id,
                        'permission' => $permission,
                        'allowed' => (bool) $override['allowed'],
                        'expires_at' => $override['expires_at'] ? Carbon::parse($override['expires_at'])->endOfDay() : null,
                    ]);
                }
            }

            $user->coordinatedTeams()->sync($data['teams'] ?? []);
            $user->refreshPermissions();
        });

        AuditLog::record('permissions.restored', 'Przywrócono uprawnienia konta '.$user->name.' ze stanu z '.$snapshot->created_at->format('d.m.Y H:i'), $user, [
            'role' => array_keys($data['roles'] ?? []),
        ]);
    }

    /**
     * Krótki opis stanu do listy w panelu: role i liczba wyjątków.
     */
    public static function summary(array $data): string
    {
        $roles = collect($data['roles'] ?? [])->keys()->map(fn (string $role) => UserRole::tryFrom($role)?->label())->filter()->implode(', ') ?: 'bez roli';
        $overrides = collect($data['overrides'] ?? []);
        $granted = $overrides->where('allowed', true)->count();
        $denied = $overrides->where('allowed', false)->count();

        return $roles.($granted ? ' · nadane: '.$granted : '').($denied ? ' · odebrane: '.$denied : '').(count($data['teams'] ?? []) ? ' · drużyny koordynatora: '.count($data['teams']) : '');
    }
}

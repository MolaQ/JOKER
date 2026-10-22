<?php

namespace App\Models;

use App\Permission;
use App\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Uprawnienie nadane roli w panelu "Role i uprawnienia".
 */
class RolePermission extends Model
{
    public $timestamps = false;

    protected $fillable = ['role', 'permission'];

    protected function casts(): array
    {
        return ['role' => UserRole::class, 'permission' => Permission::class];
    }

    /**
     * Uprawnienia każdej roli, np. ['trainer' => ['teams.manage', ...]].
     *
     * @return array<string, list<string>>
     */
    public static function map(): array
    {
        return Cache::rememberForever('role_permissions', fn () => static::query()
            ->get()
            ->groupBy(fn (self $row) => $row->role->value)
            ->map(fn ($rows) => $rows->map(fn (self $row) => $row->permission->value)->values()->all())
            ->all());
    }

    /**
     * @param  list<Permission>  $permissions
     */
    public static function syncRole(UserRole $role, array $permissions): void
    {
        static::where('role', $role)->delete();

        foreach ($permissions as $permission) {
            static::create(['role' => $role, 'permission' => $permission]);
        }

        Cache::forget('role_permissions');
    }
}

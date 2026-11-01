<?php

use App\Permission;
use App\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Uprawnienie „Grafiki do transmisji” (/logotypy, /miniatury): dostają je role, które prowadzą transmisje i galerie, oraz szef.
 */
return new class extends Migration
{
    public function up(): void
    {
        $roles = DB::table('role_permissions')->where('permission', Permission::Media->value)->pluck('role')
            ->push(UserRole::Boss->value)
            ->unique();

        foreach ($roles as $role) {
            DB::table('role_permissions')->insertOrIgnore(['role' => $role, 'permission' => Permission::Graphics->value]);
        }

        DB::table('settings')->where('key', 'logos_login')->delete();
        Cache::forget('role_permissions');
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('permission', Permission::Graphics->value)->delete();
        DB::table('permission_user')->where('permission', Permission::Graphics->value)->delete();
        Cache::forget('role_permissions');
    }
};

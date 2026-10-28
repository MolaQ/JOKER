<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Koordynator drużyny: osoba od przepływu informacji w jednej lub kilku drużynach (nie jest w sztabie).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_coordinators', function (Blueprint $table) {
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['team_id', 'user_id']);
        });

        if (! DB::table('role_permissions')->where('role', 'coordinator')->exists()) {
            foreach (['teams.manage', 'users.activate', 'proposals.review'] as $permission) {
                DB::table('role_permissions')->insert(['role' => 'coordinator', 'permission' => $permission]);
            }
        }

        Cache::forget('role_permissions');
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('role', 'coordinator')->delete();
        DB::table('role_user')->where('role', 'coordinator')->delete();
        Cache::forget('role_permissions');
        Schema::dropIfExists('team_coordinators');
    }
};

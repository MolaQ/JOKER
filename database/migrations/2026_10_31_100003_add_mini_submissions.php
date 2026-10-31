<?php

use App\Permission;
use App\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Zgłoszenia minisiatkówki do zatwierdzenia (turnieje, wyniki, relacje) oraz role Trener mini i Koordynator mini.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('mini_trips', 'status')) {
            Schema::table('mini_trips', function (Blueprint $table) {
                $table->string('status', 20)->default('published')->after('tournament_id');
                $table->json('pending_results')->nullable()->after('status');
                $table->foreignId('submitted_by')->nullable()->after('pending_results')->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('articles', 'mini_trip_id')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->foreignId('mini_trip_id')->nullable()->constrained()->nullOnDelete();
            });
        }

        $grants = [UserRole::Boss, UserRole::Admin, UserRole::MiniTrainer, UserRole::MiniCoordinator];
        foreach ($grants as $role) {
            DB::table('role_permissions')->insertOrIgnore(['role' => $role->value, 'permission' => Permission::MiniSubmit->value]);
        }
        foreach ([UserRole::MiniTrainer, UserRole::MiniCoordinator] as $role) {
            foreach ([Permission::WriteComments, Permission::WriteMessages] as $permission) {
                DB::table('role_permissions')->insertOrIgnore(['role' => $role->value, 'permission' => $permission->value]);
            }
        }

        Cache::forget('role_permissions');
    }

    public function down(): void
    {
        DB::table('role_permissions')->whereIn('role', ['mini_trainer', 'mini_coordinator'])->delete();
        DB::table('role_permissions')->where('permission', 'mini.submit')->delete();
        Cache::forget('role_permissions');

        Schema::table('articles', fn (Blueprint $table) => $table->dropConstrainedForeignId('mini_trip_id'));
        Schema::table('mini_trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropColumn(['status', 'pending_results']);
        });
    }
};

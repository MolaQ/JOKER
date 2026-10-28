<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Role Moderator i Fotograf, zapis, kto opublikował komentarz,
 * oraz e-mail o sprawach czekających na zatwierdzenie (do włączenia w „Mój profil”).
 */
return new class extends Migration
{
    private const DEFAULTS = [
        'moderator' => ['comments.write', 'comments.moderate', 'proposals.content', 'proposals.review', 'users.activate', 'teams.all'],
        'photographer' => ['comments.write', 'media.manage', 'photos.tags'],
    ];

    public function up(): void
    {
        foreach (self::DEFAULTS as $role => $permissions) {
            if (! DB::table('role_permissions')->where('role', $role)->exists()) {
                DB::table('role_permissions')->insert(array_map(fn ($permission) => ['role' => $role, 'permission' => $permission], $permissions));
            }
        }
        if (! DB::table('role_permissions')->where('role', 'boss')->where('permission', 'photos.tags')->exists()) {
            DB::table('role_permissions')->insert(['role' => 'boss', 'permission' => 'photos.tags']);
        }
        Cache::forget('role_permissions');

        foreach (['comments', 'game_comments'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('approved_by')->nullable()->after('is_approved')->constrained('users')->nullOnDelete();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_approvals')->default(false);
            $table->unsignedInteger('approvals_notified_count')->default(0);
            $table->timestamp('approvals_notified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notify_approvals', 'approvals_notified_count', 'approvals_notified_at']);
        });

        foreach (['comments', 'game_comments'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('approved_by');
            });
        }

        DB::table('role_permissions')->whereIn('role', array_keys(self::DEFAULTS))->orWhere('permission', 'photos.tags')->delete();
        DB::table('role_user')->whereIn('role', array_keys(self::DEFAULTS))->delete();
        Cache::forget('role_permissions');
    }
};

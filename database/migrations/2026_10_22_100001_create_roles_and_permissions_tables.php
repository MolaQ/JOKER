<?php

use App\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Konto może mieć kilka ról. Role dostają uprawnienia (do zmiany w panelu),
 * a pojedynczemu kontu można uprawnienie dodatkowo nadać albo odebrać.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->timestamps();
            $table->primary(['user_id', 'role']);
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 20);
            $table->string('permission', 50);
            $table->unique(['role', 'permission']);
        });

        Schema::create('permission_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('permission', 50);
            $table->boolean('allowed');
            $table->timestamps();
            $table->unique(['user_id', 'permission']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->nullableMorphs('subject');
            $table->string('description');
            $table->json('details')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        foreach (UserRole::cases() as $role) {
            DB::table('role_permissions')->insert(array_map(
                fn ($permission) => ['role' => $role->value, 'permission' => $permission->value],
                $role->defaultPermissions(),
            ));
        }

        // Dotychczasowa jedna rola konta staje się pierwszą z jego ról.
        $roles = array_column(UserRole::cases(), 'value');

        DB::table('users')->select(['id', 'role'])->orderBy('id')->each(function ($user) use ($roles) {
            if (in_array($user->role, $roles, true)) {
                DB::table('role_user')->insert(['user_id' => $user->id, 'role' => $user->role, 'created_at' => now(), 'updated_at' => now()]);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('fan');
        });

        DB::table('role_user')->orderBy('user_id')->each(
            fn ($row) => DB::table('users')->where('id', $row->user_id)->update(['role' => $row->role])
        );

        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('permission_user');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('role_user');
    }
};

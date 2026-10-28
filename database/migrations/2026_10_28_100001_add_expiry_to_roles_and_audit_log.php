<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Role i wyjątki w uprawnieniach mogą mieć datę „ważne do” (puste = bezterminowo).
 * Dziennik zmian dostaje filtr po rodzaju obiektu, a szef prawo do jego przeglądania.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('role');
        });

        Schema::table('permission_user', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('allowed');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['subject_type', 'created_at']);
            $table->index(['actor_id', 'created_at']);
        });

        if (! DB::table('role_permissions')->where('role', 'boss')->where('permission', 'audit.view')->exists()) {
            DB::table('role_permissions')->insert(['role' => 'boss', 'permission' => 'audit.view']);
        }

        Cache::forget('role_permissions');
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('permission', 'audit.view')->delete();
        Cache::forget('role_permissions');

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['subject_type', 'created_at']);
            $table->dropIndex(['actor_id', 'created_at']);
        });

        Schema::table('permission_user', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });

        Schema::table('role_user', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};

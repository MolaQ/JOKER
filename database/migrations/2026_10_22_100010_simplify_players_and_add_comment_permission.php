<?php

use App\Permission;
use App\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Zawodnik może istnieć z samym imieniem i nazwiskiem (drużynę, numer i pozycję uzupełnia się później),
 * dostaje zainteresowania i linki do mediów społecznościowych, a komentowanie staje się uprawnieniem,
 * które można odebrać pojedynczemu kontu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->nullable()->change();
            $table->integer('jersey_number')->nullable()->change();
            $table->string('position')->nullable()->change();
            $table->string('interests', 500)->nullable()->after('bio');
            $table->json('social_links')->nullable()->after('interests');
        });

        DB::table('players')->whereNotNull('instagram')->where('instagram', '!=', '')->get(['id', 'instagram'])
            ->each(fn ($player) => DB::table('players')->where('id', $player->id)->update(['social_links' => json_encode(['instagram' => $player->instagram])]));

        foreach (UserRole::cases() as $role) {
            DB::table('role_permissions')->insertOrIgnore(['role' => $role->value, 'permission' => Permission::WriteComments->value]);
        }

        Cache::forget('role_permissions');
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('permission', Permission::WriteComments->value)->delete();
        DB::table('permission_user')->where('permission', Permission::WriteComments->value)->delete();
        Cache::forget('role_permissions');

        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['interests', 'social_links']);
        });
    }
};

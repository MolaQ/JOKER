<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trening minisiatkówki: bez drużyny, z listą grup, które ćwiczą razem (pusta lista = wszystkie grupy).
     */
    public function up(): void
    {
        if (Schema::hasColumn('training_sessions', 'mini_group_ids')) {
            return;
        }

        Schema::table('training_sessions', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->change();
            $table->json('mini_group_ids')->nullable()->after('team_id');
        });
    }

    public function down(): void
    {
        Schema::table('training_sessions', function (Blueprint $table) {
            $table->dropColumn('mini_group_ids');
        });
    }
};

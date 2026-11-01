<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Podpis partnera turnieju, np. „Patronat honorowy”, „Partner medialny”.
     */
    public function up(): void
    {
        if (Schema::hasColumn('tournament_sponsor', 'role')) {
            return;
        }

        Schema::table('tournament_sponsor', function (Blueprint $table) {
            $table->string('role', 60)->nullable()->after('is_main');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_sponsor', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};

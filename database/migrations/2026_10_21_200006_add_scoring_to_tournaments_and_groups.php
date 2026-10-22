<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Punktacja meczów grupowych: domyślna dla turnieju i opcjonalnie własna dla grupy.
     */
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->json('scoring')->nullable()->after('guest_info');
        });

        Schema::table('tournament_groups', function (Blueprint $table) {
            $table->json('scoring')->nullable()->after('final_places');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_groups', function (Blueprint $table) {
            $table->dropColumn('scoring');
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn('scoring');
        });
    }
};

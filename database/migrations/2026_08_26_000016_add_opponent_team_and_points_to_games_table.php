<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pozwala powiązać mecz z konkretną drużyną rywala z bazy (zamiast
     * wyłącznie z wolnym tekstem) oraz opcjonalnie zapisać małe punkty
     * (do wyliczania statystyk w tabeli ligowej).
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->foreignId('opponent_team_id')->nullable()->after('opponent')->constrained('rival_teams')->nullOnDelete();
            $table->unsignedSmallInteger('home_points')->nullable()->after('away_score');
            $table->unsignedSmallInteger('away_points')->nullable()->after('home_points');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropConstrainedForeignId('opponent_team_id');
            $table->dropColumn(['home_points', 'away_points']);
        });
    }
};

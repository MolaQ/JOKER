<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stary unikalny indeks (team_id, season_id) uniemożliwiał drużynie grę
     * w więcej niż jednych rozgrywkach w tym samym sezonie (np. liga + puchar).
     * Wpis w tabeli ligowej jest teraz unikalny per (competitor_type,
     * competitor_id, competition_id).
     */
    public function up(): void
    {
        Schema::table('league_standings', function (Blueprint $table) {
            // team_id ma klucz obcy, który wymaga jakiegoś indeksu - dodajemy
            // zwykły indeks zanim usuniemy unikalny (team_id, season_id).
            $table->index('team_id', 'league_standings_team_id_index');
            $table->dropUnique(['team_id', 'season_id']);
            $table->unique(['competitor_type', 'competitor_id', 'competition_id'], 'league_standings_competitor_competition_unique');
        });
    }

    public function down(): void
    {
        Schema::table('league_standings', function (Blueprint $table) {
            $table->dropUnique('league_standings_competitor_competition_unique');
            $table->unique(['team_id', 'season_id']);
            $table->dropIndex('league_standings_team_id_index');
        });
    }
};

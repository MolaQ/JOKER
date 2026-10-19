<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wyniki z dwóch źródeł (WZPS i wielkopolskasiatkowka.com.pl):
     * - przy meczu: skąd jest wynik (wpisany ręcznie nie jest nadpisywany), etap/grupa/kolejka i uwaga (np. walkower),
     * - w tabelach: wszystkie etapy i grupy (stąd brak unikalności drużyny w rozgrywkach) oraz nazwa ze źródła.
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('result_source', 16)->nullable()->after('sets_score');
            $table->string('round_label', 120)->nullable()->after('league');
            $table->string('result_note', 120)->nullable()->after('result_source');
        });

        Schema::table('league_standings', function (Blueprint $table) {
            $table->string('stage_name', 50)->nullable()->after('competition_id');
            $table->string('source_name')->nullable()->after('competitor_id');
            // competition_id ma klucz obcy - MySQL potrzebuje dla niego indeksu przed usunięciem unikalnego.
            $table->index('competition_id', 'league_standings_competition_id_index');
            $table->dropUnique('league_standings_competitor_competition_unique');
            $table->index(['competitor_type', 'competitor_id'], 'league_standings_competitor_index');
        });
    }

    public function down(): void
    {
        Schema::table('league_standings', function (Blueprint $table) {
            $table->dropIndex('league_standings_competitor_index');
            $table->unique(['competitor_type', 'competitor_id', 'competition_id'], 'league_standings_competitor_competition_unique');
            $table->dropIndex('league_standings_competition_id_index');
            $table->dropColumn(['stage_name', 'source_name']);
        });

        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['result_source', 'round_label', 'result_note']);
        });
    }
};

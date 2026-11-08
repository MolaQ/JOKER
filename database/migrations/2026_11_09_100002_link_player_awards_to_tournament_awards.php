<?php

use App\Models\TournamentAward;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wszystkie nagrody indywidualne zawodnika w jednej tabeli (do edycji i ustawiania kolejności w panelu zawodnika):
 * nagroda z turnieju na stronie dostaje kopię w player_awards z odnośnikiem do wyróżnienia turnieju.
 * Dane: tylko dopisanie kopii brakujących nagród (nic nie jest zmieniane ani usuwane).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('player_awards', 'tournament_award_id')) {
            Schema::table('player_awards', function (Blueprint $table) {
                $table->foreignId('tournament_award_id')->nullable()->after('player_id')->constrained()->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('player_awards', 'note')) {
            Schema::table('player_awards', function (Blueprint $table) {
                $table->string('note', 200)->nullable()->after('place');
            });
        }

        // Kopie brakujących nagród z turniejów (syncPlayerAward dopisuje tylko brakujące).
        TournamentAward::whereNotNull('player_id')
            ->whereNotExists(fn ($query) => $query->from('player_awards')->whereColumn('player_awards.tournament_award_id', 'tournament_awards.id'))
            ->get()
            ->each(fn (TournamentAward $award) => $award->syncPlayerAward());
    }

    public function down(): void
    {
        DB::table('player_awards')->whereNotNull('tournament_award_id')->delete();
        Schema::table('player_awards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tournament_award_id');
            $table->dropColumn('note');
        });
    }
};

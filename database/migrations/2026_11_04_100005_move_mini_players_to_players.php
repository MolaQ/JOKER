<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dzieci z minisiatkówki dostają zwykłe profile zawodników (players z grupą mini zamiast drużyny).
     * Same nowe kolumny i tabela, a dane są tylko kopiowane: stare tabele mini_players zostają nietknięte.
     * Kopiowanie jest powtarzalne: dziecko już przeniesione (players.mini_player_id) nie jest dodawane drugi raz.
     */
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            if (! Schema::hasColumn('players', 'mini_group_id')) {
                $table->foreignId('mini_group_id')->nullable()->after('team_id')->constrained('mini_groups')->nullOnDelete();
            }
            if (! Schema::hasColumn('players', 'birth_year')) {
                $table->unsignedSmallInteger('birth_year')->nullable()->after('birth_date');
            }
            if (! Schema::hasColumn('players', 'mini_player_id')) {
                // skąd przeniesiono dziecko (dawna tabela mini_players), bez klucza obcego
                $table->unsignedBigInteger('mini_player_id')->nullable()->unique();
            }
        });

        Schema::hasTable('mini_trip_team_player') || Schema::create('mini_trip_team_player', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mini_trip_team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();

            $table->unique(['mini_trip_team_id', 'player_id'], 'mini_trip_team_player_unique');
        });

        if (! Schema::hasTable('mini_players')) {
            return;
        }

        foreach (DB::table('mini_players')->orderBy('id')->get() as $child) {
            if (DB::table('players')->where('mini_player_id', $child->id)->exists()) {
                continue;
            }

            DB::table('players')->insert([
                'first_name' => $child->first_name,
                'last_name' => $child->last_name,
                'birth_year' => $child->birth_year,
                'jersey_number' => $child->number ?? null,
                'photo_path' => $child->photo_path,
                'image_consent' => (bool) $child->image_consent,
                'is_active' => (bool) $child->is_active,
                'mini_group_id' => $child->mini_group_id,
                'mini_player_id' => $child->id,
                'created_at' => $child->created_at ?? now(),
                'updated_at' => now(),
            ]);
        }

        $players = DB::table('players')->whereNotNull('mini_player_id')->pluck('id', 'mini_player_id');

        if (Schema::hasTable('mini_player_mini_trip_team')) {
            foreach (DB::table('mini_player_mini_trip_team')->get() as $row) {
                if (isset($players[$row->mini_player_id])) {
                    DB::table('mini_trip_team_player')->insertOrIgnore(['mini_trip_team_id' => $row->mini_trip_team_id, 'player_id' => $players[$row->mini_player_id]]);
                }
            }
        }

        // Wyniki zgłoszone przez trenera i czekające na zatwierdzenie trzymają składy jako numery dawnych dzieci mini.
        if (Schema::hasColumn('mini_trips', 'pending_results')) {
            foreach (DB::table('mini_trips')->whereNotNull('pending_results')->get(['id', 'pending_results']) as $trip) {
                $pending = json_decode((string) $trip->pending_results, true);
                if (! is_array($pending) || ! empty($pending['players_moved'])) {
                    continue;
                }
                foreach ($pending['teams'] ?? [] as $index => $team) {
                    $pending['teams'][$index]['players'] = array_values(array_filter(array_map(fn ($id) => $players[$id] ?? null, (array) ($team['players'] ?? []))));
                }
                $pending['players_moved'] = true;
                DB::table('mini_trips')->where('id', $trip->id)->update(['pending_results' => json_encode($pending)]);
            }
        }

        if (Schema::hasTable('mini_player_guardian') && Schema::hasTable('player_guardian')) {
            foreach (DB::table('mini_player_guardian')->get() as $row) {
                if (isset($players[$row->mini_player_id]) && ! DB::table('player_guardian')->where(['player_id' => $players[$row->mini_player_id], 'user_id' => $row->user_id])->exists()) {
                    DB::table('player_guardian')->insert(['player_id' => $players[$row->mini_player_id], 'user_id' => $row->user_id, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('players')->whereNotNull('mini_player_id')->delete();
        Schema::dropIfExists('mini_trip_team_player');
        Schema::table('players', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mini_group_id');
            $table->dropUnique(['mini_player_id']);
            $table->dropColumn(['birth_year', 'mini_player_id']);
        });
    }
};

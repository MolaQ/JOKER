<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Numer meczu w kategorii (#1, #2…) i reguła, skąd bierze się drużyna na każdej stronie:
     * „group:{id grupy}:{miejsce}”, „winner:{id meczu}” albo „loser:{id meczu}”.
     */
    public function up(): void
    {
        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->unsignedSmallInteger('number')->nullable()->after('tournament_group_id');
            $table->string('home_source', 40)->nullable()->after('away_placeholder');
            $table->string('away_source', 40)->nullable()->after('home_source');
        });

        // Istniejące mecze dostają numery w kolejności rozgrywania.
        $numbers = [];

        DB::table('tournament_matches')
            ->orderBy('id')
            ->get(['id', 'tournament_category_id', 'starts_at'])
            ->sortBy(fn (object $match) => [$match->starts_at === null, (string) $match->starts_at, $match->id])
            ->each(function (object $match) use (&$numbers) {
                $numbers[$match->tournament_category_id] = ($numbers[$match->tournament_category_id] ?? 0) + 1;
                DB::table('tournament_matches')->where('id', $match->id)->update(['number' => $numbers[$match->tournament_category_id]]);
            });
    }

    public function down(): void
    {
        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->dropColumn(['number', 'home_source', 'away_source']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Linki do transmisji meczu (YouTube, Facebook, inne): lista {url, label}.
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->json('streams')->nullable();
        });

        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->json('streams')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('streams');
        });

        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->dropColumn('streams');
        });
    }
};

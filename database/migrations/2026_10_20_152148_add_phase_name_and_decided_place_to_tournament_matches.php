<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nazwa fazy (kolumny drabinki) wpisywana przez organizatora i miejsce, o które toczy się mecz
     * (finał = 1, mecz o 3. miejsce = 3), niezależnie od tego, w której kolumnie stoi.
     */
    public function up(): void
    {
        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->string('phase_name', 60)->nullable()->after('label');
            $table->unsignedSmallInteger('decides_place')->nullable()->after('placement');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->dropColumn(['phase_name', 'decides_place']);
        });
    }
};

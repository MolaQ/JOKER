<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dni turnieju w kategorii do układania godzin: data, godzina pierwszego meczu i numer meczu, od którego zaczyna się dzień.
     */
    public function up(): void
    {
        Schema::table('tournament_categories', function (Blueprint $table) {
            $table->json('schedule_days')->nullable()->after('match_interval');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_categories', fn (Blueprint $table) => $table->dropColumn('schedule_days'));
    }
};

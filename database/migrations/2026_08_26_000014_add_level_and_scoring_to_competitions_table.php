<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dodaje powiązanie z poziomem rozgrywek oraz konfigurowalną punktację
     * i kryteria sortowania tabeli dla każdych rozgrywek osobno.
     */
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->foreignId('level_id')->nullable()->after('level')->constrained('competition_levels')->nullOnDelete();

            // Punktacja siatkarska (domyślnie system PZPS: 3/3/2/1/0/0).
            $table->unsignedTinyInteger('points_win_3_0')->default(3)->after('level_id');
            $table->unsignedTinyInteger('points_win_3_1')->default(3)->after('points_win_3_0');
            $table->unsignedTinyInteger('points_win_3_2')->default(2)->after('points_win_3_1');
            $table->unsignedTinyInteger('points_loss_2_3')->default(1)->after('points_win_3_2');
            $table->unsignedTinyInteger('points_loss_1_3')->default(0)->after('points_loss_2_3');
            $table->unsignedTinyInteger('points_loss_0_3')->default(0)->after('points_loss_1_3');

            // Kolejność kryteriów sortowania tabeli, np. ["points","sets_ratio","points_ratio"].
            $table->json('standings_criteria')->nullable()->after('points_loss_0_3');
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('level_id');
            $table->dropColumn([
                'points_win_3_0',
                'points_win_3_1',
                'points_win_3_2',
                'points_loss_2_3',
                'points_loss_1_3',
                'points_loss_0_3',
                'standings_criteria',
            ]);
        });
    }
};

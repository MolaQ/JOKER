<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mecz dwóch naszych drużyn (np. kadeci jako gość w III lidze): druga drużyna widzi mecz w swoim kalendarzu.
     */
    public function up(): void
    {
        if (Schema::hasTable('games') && ! Schema::hasColumn('games', 'guest_team_id')) {
            Schema::table('games', function (Blueprint $table) {
                $table->foreignId('guest_team_id')->nullable()->after('team_id')->constrained('teams')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('games', 'guest_team_id')) {
            Schema::table('games', function (Blueprint $table) {
                $table->dropConstrainedForeignId('guest_team_id');
            });
        }
    }
};

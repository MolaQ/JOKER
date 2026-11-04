<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rodzice dzieci z minisiatkówki: grupa wybrana przy rejestracji i powiązanie rodzica z dzieckiem. Same nowe pola i tabela.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'requested_mini_group_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('requested_mini_group_id')->nullable()->after('requested_team_id')->constrained('mini_groups')->nullOnDelete();
            });
        }

        Schema::hasTable('mini_player_guardian') || Schema::create('mini_player_guardian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mini_player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['mini_player_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mini_player_guardian');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_mini_group_id');
        });
    }
};

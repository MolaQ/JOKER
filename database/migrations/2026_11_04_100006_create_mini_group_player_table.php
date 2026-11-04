<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dodatkowe grupy minisiatkówki dziecka (np. gra w dwójkach i w singielkach). Sama nowa tabela.
     */
    public function up(): void
    {
        Schema::hasTable('mini_group_player') || Schema::create('mini_group_player', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mini_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['mini_group_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mini_group_player');
    }
};

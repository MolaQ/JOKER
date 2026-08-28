<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drużyna może mieć kilku trenerów: teams.trainer_id pozostaje trenerem
     * głównym, ta tabela przechowuje dodatkowych (pomocniczych) trenerów.
     */
    public function up(): void
    {
        Schema::create('team_trainers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_trainers');
    }
};

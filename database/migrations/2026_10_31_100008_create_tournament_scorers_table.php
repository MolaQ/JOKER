<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Osoby wpisujące wyniki jednego turnieju (sędzia stolikowy, sekretarz), tylko w dniach turnieju.
        Schema::hasTable('tournament_scorers') || Schema::create('tournament_scorers', function (Blueprint $table) {
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['tournament_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_scorers');
    }
};

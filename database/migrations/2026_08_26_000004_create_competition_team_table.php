<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drużyny biorące udział w danych rozgrywkach (many-to-many).
     */
    public function up(): void
    {
        Schema::create('competition_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['competition_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_team');
    }
};

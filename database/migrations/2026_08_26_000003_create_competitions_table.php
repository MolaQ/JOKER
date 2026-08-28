<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rozgrywki (ligi/puchary), w których biorą udział drużyny w danym sezonie.
     * Jedna drużyna może brać udział w wielu rozgrywkach jednocześnie (np. liga wojewódzka + 3 liga).
     */
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // np. "3 Liga Mężczyzn", "Liga Wojewódzka Juniorów"
            $table->string('level')->nullable(); // np. "wojewódzka", "3 liga", "okręgowa"
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};

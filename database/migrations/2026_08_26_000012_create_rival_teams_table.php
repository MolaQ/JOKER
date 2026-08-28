<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Baza drużyn rywali (spoza klubu), używana w terminarzu i tabeli ligowej.
     */
    public function up(): void
    {
        Schema::create('rival_teams', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('short_name')->nullable();
            $table->string('category')->nullable(); // np. "Seniorzy", "Junior 2010"
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rival_teams');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Szablony kategorii turnieju: grupy, mecze, drabinka i dni zapisane do ponownego użycia.
     */
    public function up(): void
    {
        if (Schema::hasTable('tournament_templates')) {
            return;
        }

        Schema::create('tournament_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->json('structure');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_templates');
    }
};

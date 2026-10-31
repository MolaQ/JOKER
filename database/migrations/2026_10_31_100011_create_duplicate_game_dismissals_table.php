<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pary meczów oznaczone „to nie dubel”, żeby ostrzeżenie o możliwym dublu nie wracało.
        Schema::hasTable('duplicate_game_dismissals') || Schema::create('duplicate_game_dismissals', function (Blueprint $table) {
            $table->id();
            $table->string('game_ids', 190)->unique();
            $table->foreignId('dismissed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duplicate_game_dismissals');
    }
};

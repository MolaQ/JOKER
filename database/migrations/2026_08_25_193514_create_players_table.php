<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->integer('jersey_number');
            $table->string('position'); // enum: setter, outside_hitter, opposite, middle_blocker, libero
            $table->date('birth_date')->nullable();
            $table->integer('height')->nullable(); // cm
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // powiązanie z kontem użytkownika
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['team_id', 'jersey_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};

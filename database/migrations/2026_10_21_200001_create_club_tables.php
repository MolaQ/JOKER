<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kadra klubu: sezony, drużyny, zawodnicy (z przypisaniem do drużyn w sezonach,
     * opiekunami, osiągnięciami i zgłoszeniami zmian), sztab szkoleniowy
     * oraz zgłoszenia zmian profilu użytkowników.
     */
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // np. "2025/2026"
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // np. "Seniorzy", "Juniorzy"
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category'); // senior, junior, junior_mlodszy, mlodzik
            // Rocznik przypisany do drużyny (np. Junior 2008). Puste dla Seniorów - gra tam może każdy zawodnik klubu.
            $table->integer('birth_year')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('display_order')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
        });

        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->integer('jersey_number');
            $table->string('position'); // enum: setter, outside_hitter, opposite, middle_blocker, libero
            $table->json('extra_positions')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birthday_display', 8)->default('age'); // hidden | cake | age
            $table->integer('height')->nullable(); // cm
            $table->integer('weight')->nullable(); // kg
            $table->integer('reach')->nullable(); // zasięg w staniu/bloku, cm
            $table->integer('spike_reach')->nullable(); // zasięg w ataku, cm
            $table->unsignedSmallInteger('block_reach')->nullable(); // zasięg w bloku, cm
            $table->string('instagram')->nullable();
            $table->text('bio')->nullable();
            $table->text('additional_info')->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // powiązanie z kontem użytkownika
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();

            $table->unique(['team_id', 'jersey_number']);
        });

        Schema::create('team_player_season', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->integer('jersey_number')->nullable(); // numer w tej konkretnej drużynie
            $table->boolean('is_captain')->default(false);
            $table->timestamps();

            $table->unique(['team_id', 'player_id', 'season_id']);
        });

        Schema::create('team_trainers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
        });

        Schema::create('player_guardian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['player_id', 'user_id']);
        });

        Schema::create('player_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->string('type', 16)->default('data'); // data | photo
            $table->json('proposed_data')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('status', 16)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 500)->nullable();
            $table->timestamps();

            $table->index(['status', 'player_id']);
        });

        Schema::create('player_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('season', 20)->nullable();
            $table->string('title');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('profile_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('proposed_data');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_change_requests');
        Schema::dropIfExists('player_achievements');
        Schema::dropIfExists('player_change_requests');
        Schema::dropIfExists('player_guardian');
        Schema::dropIfExists('team_trainers');
        Schema::dropIfExists('team_player_season');
        Schema::dropIfExists('players');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('seasons');
    }
};

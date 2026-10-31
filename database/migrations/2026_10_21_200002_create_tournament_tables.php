<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Turnieje (z miejscami naszych drużyn i własną stroną, np. memoriału)
     * oraz ich kategorie, grupy, uczestnicy i mecze.
     */
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->string('series', 60)->nullable();
            $table->string('edition', 60)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('venue')->nullable();
            $table->string('city', 100)->nullable();
            $table->boolean('is_organizer')->default(false);
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->longText('rules')->nullable();
            $table->longText('guest_info')->nullable();
            $table->boolean('has_page')->default(false);
            $table->timestamps();

            $table->index('start_date');
            $table->index('series');
        });

        Schema::create('team_tournament', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('placement', 60)->nullable();

            $table->unique(['tournament_id', 'team_id']);
        });

        Schema::create('tournament_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tournament_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_category_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tournament_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tournament_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('competitor_type', 20);
            $table->unsignedBigInteger('competitor_id');
            $table->string('display_name')->nullable();
            $table->text('roster')->nullable();
            $table->unsignedSmallInteger('final_position')->nullable();
            $table->timestamps();

            $table->index(['competitor_type', 'competitor_id']);
        });

        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tournament_group_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('number')->nullable(); // numer meczu w kategorii
            $table->string('stage', 12)->default('group');
            $table->string('label', 80)->nullable();
            $table->string('phase_name', 60)->nullable();
            $table->unsignedSmallInteger('placement')->nullable();
            $table->unsignedSmallInteger('decides_place')->nullable();
            $table->unsignedTinyInteger('bracket_round')->nullable();
            $table->unsignedTinyInteger('bracket_slot')->nullable();
            $table->foreignId('home_participant_id')->nullable()->constrained('tournament_participants')->nullOnDelete();
            $table->foreignId('away_participant_id')->nullable()->constrained('tournament_participants')->nullOnDelete();
            $table->string('home_placeholder', 60)->nullable();
            $table->string('away_placeholder', 60)->nullable();
            $table->string('home_source', 40)->nullable();
            $table->string('away_source', 40)->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->string('court', 60)->nullable();
            $table->unsignedTinyInteger('home_sets')->nullable();
            $table->unsignedTinyInteger('away_sets')->nullable();
            $table->json('sets_score')->nullable();
            $table->json('streams')->nullable();
            $table->timestamps();

            $table->index(['tournament_category_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
        Schema::dropIfExists('tournament_participants');
        Schema::dropIfExists('tournament_groups');
        Schema::dropIfExists('tournament_categories');
        Schema::dropIfExists('team_tournament');
        Schema::dropIfExists('tournaments');
    }
};

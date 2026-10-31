<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rozgrywki: poziomy i rozgrywki ligowe (z punktacją i importem terminarza),
     * udział naszych drużyn, kluby i drużyny rywali, tabele oraz mecze
     * wraz z komentarzami i zdjęciami.
     */
    public function up(): void
    {
        Schema::create('competition_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // np. "3 Liga Mężczyzn", "Liga Wojewódzka Juniorów"
            $table->string('level')->nullable(); // np. "wojewódzka", "3 liga", "okręgowa"
            $table->foreignId('level_id')->nullable()->constrained('competition_levels')->nullOnDelete();
            // Punktacja siatkarska (domyślnie system PZPS: 3/3/2/1/0/0).
            $table->unsignedTinyInteger('points_win_3_0')->default(3);
            $table->unsignedTinyInteger('points_win_3_1')->default(3);
            $table->unsignedTinyInteger('points_win_3_2')->default(2);
            $table->unsignedTinyInteger('points_loss_2_3')->default(1);
            $table->unsignedTinyInteger('points_loss_1_3')->default(0);
            $table->unsignedTinyInteger('points_loss_0_3')->default(0);
            // Kolejność kryteriów sortowania tabeli, np. ["points","sets_ratio","points_ratio"].
            $table->json('standings_criteria')->nullable();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->text('source_url')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->string('sync_message', 500)->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('competition_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('external_name')->nullable();
            $table->string('display_name')->nullable();
            $table->string('display_suffix', 60)->nullable();
            $table->timestamps();

            $table->unique(['competition_id', 'team_id']);
        });

        Schema::create('rival_clubs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('rival_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rival_club_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('short_name')->nullable();
            $table->json('aliases')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('needs_review')->default(false);
            $table->timestamps();
        });

        Schema::create('league_standings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            // Wpis może dotyczyć naszej drużyny albo drużyny rywala.
            $table->string('competitor_type')->nullable();
            $table->unsignedBigInteger('competitor_id')->nullable();
            $table->string('source_name')->nullable();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competition_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stage_name', 50)->nullable();
            $table->string('group_name', 100)->nullable();
            $table->integer('position')->default(0);
            $table->integer('played')->default(0);
            $table->integer('won')->default(0);
            $table->integer('lost')->default(0);
            $table->integer('sets_won')->default(0);
            $table->integer('sets_lost')->default(0);
            $table->integer('points_for')->default(0);
            $table->integer('points_against')->default(0);
            $table->integer('points')->default(0); // punkty w tabeli
            $table->boolean('is_manual_override')->default(false);
            $table->timestamps();

            $table->index('team_id', 'league_standings_team_id_index');
            $table->index('competition_id', 'league_standings_competition_id_index');
            $table->index(['competitor_type', 'competitor_id']);
            $table->index(['competitor_type', 'competitor_id'], 'league_standings_competitor_index');
        });

        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competition_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 16)->default('league');
            $table->foreignId('tournament_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_id', 64)->nullable();
            $table->string('opponent'); // przeciwnik
            $table->foreignId('opponent_team_id')->nullable()->constrained('rival_teams')->nullOnDelete();
            $table->boolean('is_home')->default(true); // czy mecz u siebie
            $table->dateTime('game_date');
            $table->string('venue')->nullable(); // miejsce meczu
            $table->string('league')->nullable(); // np. "Tauron 1 Liga"
            $table->string('round_label', 120)->nullable();
            $table->enum('status', ['scheduled', 'live', 'finished', 'cancelled'])->default('scheduled');
            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();
            $table->unsignedSmallInteger('home_points')->nullable();
            $table->unsignedSmallInteger('away_points')->nullable();
            $table->json('sets_score')->nullable(); // [25:23, 20:25, 25:22, ...]
            $table->string('result_source', 16)->nullable();
            $table->string('result_note', 120)->nullable();
            $table->text('match_report')->nullable(); // relacja z meczu
            $table->string('video_url')->nullable(); // link do transmisji/nagrania
            $table->json('streams')->nullable();
            $table->timestamps();

            $table->index('competition_id', 'games_competition_id_index');
            $table->unique(['team_id', 'external_id']);
        });

        Schema::create('game_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });

        Schema::create('game_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('photo_path');
            $table->string('caption')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_photos');
        Schema::dropIfExists('game_comments');
        Schema::dropIfExists('games');
        Schema::dropIfExists('league_standings');
        Schema::dropIfExists('rival_teams');
        Schema::dropIfExists('rival_clubs');
        Schema::dropIfExists('competition_team');
        Schema::dropIfExists('competitions');
        Schema::dropIfExists('competition_levels');
    }
};

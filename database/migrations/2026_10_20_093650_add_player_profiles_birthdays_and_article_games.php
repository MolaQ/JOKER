<?php

use App\Models\Competition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - artykuły podpięte do meczów (relacja z meczu otwiera się z kalendarza),
     * - nazwa i dopisek naszej drużyny w tabelach i kalendarzu (per rozgrywki),
     * - urodziny z przełącznikiem widoczności (zawodnicy i sztab),
     * - rodzice powiązani z zawodnikami, propozycje zmian profilu i zdjęć do akceptacji,
     * - osiągnięcia i zasięg w bloku do profilu zawodnika.
     */
    public function up(): void
    {
        Schema::create('article_game', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->unique(['article_id', 'game_id']);
        });

        Schema::table('competition_team', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('external_name');
            $table->string('display_suffix', 60)->nullable()->after('display_name');
        });

        DB::table('competition_team')
            ->join('teams', 'teams.id', '=', 'competition_team.team_id')
            ->select('competition_team.id', 'competition_team.external_name', 'teams.name', 'teams.slug')
            ->get()
            ->each(fn (object $row) => DB::table('competition_team')->where('id', $row->id)->update(
                Competition::defaultTeamLabel($row->name, $row->slug, $row->external_name)
            ));

        Schema::table('players', function (Blueprint $table) {
            $table->boolean('show_birthday')->default(true)->after('birth_date');
            $table->unsignedSmallInteger('block_reach')->nullable()->after('spike_reach');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('email');
            $table->boolean('show_birthday')->default(false)->after('birth_date');
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
    }

    public function down(): void
    {
        Schema::dropIfExists('player_achievements');
        Schema::dropIfExists('player_change_requests');
        Schema::dropIfExists('player_guardian');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['birth_date', 'show_birthday']);
        });

        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['show_birthday', 'block_reach']);
        });

        Schema::table('competition_team', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'display_suffix']);
        });

        Schema::dropIfExists('article_game');
    }
};

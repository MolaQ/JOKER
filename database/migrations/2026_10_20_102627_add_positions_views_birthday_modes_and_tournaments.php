<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - dodatkowe pozycje zawodnika (np. libero i przyjmujący),
     * - liczniki wyświetleń profili zawodników, sztabu i drużyn,
     * - urodziny w trzech trybach: ukryte / sam torcik / torcik z wiekiem (domyślnie z wiekiem),
     * - turnieje (z miejscami naszych drużyn) i sparingi obok meczów ligowych.
     */
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->json('extra_positions')->nullable()->after('position');
            $table->string('birthday_display', 8)->default('age')->after('birth_date');
            $table->unsignedInteger('views_count')->default(0);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('birthday_display', 8)->default('age')->after('birth_date');
            $table->unsignedInteger('views_count')->default(0);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->unsignedInteger('views_count')->default(0);
        });

        DB::table('players')->where('show_birthday', false)->update(['birthday_display' => 'hidden']);

        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('show_birthday');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('show_birthday');
        });

        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('edition', 60)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('venue')->nullable();
            $table->string('city', 100)->nullable();
            $table->boolean('is_organizer')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index('start_date');
        });

        Schema::create('team_tournament', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('placement', 60)->nullable();
            $table->unique(['tournament_id', 'team_id']);
        });

        Schema::table('games', function (Blueprint $table) {
            $table->string('kind', 16)->default('league')->after('competition_id');
            $table->foreignId('tournament_id')->nullable()->after('kind')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tournament_id');
            $table->dropColumn('kind');
        });

        Schema::dropIfExists('team_tournament');
        Schema::dropIfExists('tournaments');

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('show_birthday')->default(false);
        });

        Schema::table('players', function (Blueprint $table) {
            $table->boolean('show_birthday')->default(true);
        });

        DB::table('players')->where('birthday_display', 'hidden')->update(['show_birthday' => false]);
        DB::table('users')->where('birthday_display', '!=', 'hidden')->update(['show_birthday' => true]);

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['birthday_display', 'views_count']);
        });

        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['extra_positions', 'birthday_display', 'views_count']);
        });
    }
};

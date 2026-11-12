<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kibicowskie MVP: głosy zalogowanych kibiców na zawodnika meczu i ustawienia głosowania pojedynczego meczu.
     * Mecz to rekord games albo tournament_matches (match_type: game / tournament_match).
     */
    public function up(): void
    {
        if (! Schema::hasTable('fan_mvp_polls')) {
            Schema::create('fan_mvp_polls', function (Blueprint $table) {
                $table->id();
                $table->string('match_type', 32);
                $table->unsignedBigInteger('match_id');
                $table->boolean('disabled')->default(false);
                $table->timestamp('closed_at')->nullable();
                $table->json('excluded')->nullable();
                $table->timestamps();
                $table->unique(['match_type', 'match_id']);
            });
        }

        if (! Schema::hasTable('fan_mvp_votes')) {
            Schema::create('fan_mvp_votes', function (Blueprint $table) {
                $table->id();
                $table->string('match_type', 32);
                $table->unsignedBigInteger('match_id');
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('player_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['match_type', 'match_id', 'user_id']);
                $table->index('player_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fan_mvp_votes');
        Schema::dropIfExists('fan_mvp_polls');
    }
};

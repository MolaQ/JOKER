<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::hasTable('tournament_awards') || Schema::create('tournament_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_category_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->string('person_name', 120)->nullable();
            $table->foreignId('tournament_participant_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        if (! Schema::hasColumn('player_achievements', 'tournament_award_id')) {
            Schema::table('player_achievements', function (Blueprint $table) {
                $table->foreignId('tournament_award_id')->nullable()->after('player_id')->constrained()->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('player_achievements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tournament_award_id');
        });
        Schema::dropIfExists('tournament_awards');
    }
};

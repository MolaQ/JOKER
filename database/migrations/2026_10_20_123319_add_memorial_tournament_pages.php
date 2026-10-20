<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Pełne strony turniejów (np. Memoriał im. Włodzimierza Winklera): kategorie wiekowe,
     * drużyny z opcjonalnym składem, grupy z tabelami, mecze grupowe, drabinka i mecze o miejsca,
     * zasady i informator dla drużyn przyjezdnych i kibiców. Edycje łączy wspólna „seria”.
     */
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('series', 60)->nullable()->after('slug');
            $table->string('image_path')->nullable()->after('description');
            $table->longText('rules')->nullable()->after('image_path');
            $table->longText('guest_info')->nullable()->after('rules');
            $table->boolean('has_page')->default(false)->after('guest_info');
            $table->index('series');
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
            $table->string('stage', 12)->default('group');
            $table->string('label', 80)->nullable();
            $table->unsignedSmallInteger('placement')->nullable();
            $table->unsignedTinyInteger('bracket_round')->nullable();
            $table->unsignedTinyInteger('bracket_slot')->nullable();
            $table->foreignId('home_participant_id')->nullable()->constrained('tournament_participants')->nullOnDelete();
            $table->foreignId('away_participant_id')->nullable()->constrained('tournament_participants')->nullOnDelete();
            $table->string('home_placeholder', 60)->nullable();
            $table->string('away_placeholder', 60)->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->string('court', 60)->nullable();
            $table->unsignedTinyInteger('home_sets')->nullable();
            $table->unsignedTinyInteger('away_sets')->nullable();
            $table->json('sets_score')->nullable();
            $table->timestamps();
            $table->index(['tournament_category_id', 'stage']);
        });

        // Istniejące turnieje dostają adres strony.
        DB::table('tournaments')->orderBy('id')->get(['id', 'name', 'edition'])->each(function (object $tournament) {
            DB::table('tournaments')->where('id', $tournament->id)->update([
                'slug' => $tournament->id.'-'.Str::slug(trim(($tournament->edition ?? '').' '.$tournament->name)),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
        Schema::dropIfExists('tournament_participants');
        Schema::dropIfExists('tournament_groups');
        Schema::dropIfExists('tournament_categories');

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropIndex(['series']);
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'series', 'image_path', 'rules', 'guest_info', 'has_page']);
        });
    }
};

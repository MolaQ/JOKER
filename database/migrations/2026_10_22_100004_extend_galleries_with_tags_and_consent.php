<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Galerie mają własne strony: z meczu albo z wydarzenia (bez meczu). Zdjęcia mają
 * oznaczenia drużyn i osób. Zgoda na wizerunek zawodnika jest domyślnie udzielona;
 * bez niej jego oznaczenia są ukryte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->string('match_type')->nullable()->change();
            $table->unsignedBigInteger('match_id')->nullable()->change();
            $table->date('event_date')->nullable()->after('description');
            $table->foreignId('team_id')->nullable()->after('event_date')->constrained()->nullOnDelete();
        });

        // Dotychczasowe galerie dostają datę dodania jako datę wydarzenia.
        DB::table('galleries')->orderBy('id')->each(fn ($gallery) => DB::table('galleries')
            ->where('id', $gallery->id)
            ->update(['event_date' => substr((string) $gallery->created_at, 0, 10) ?: null]));

        Schema::create('photo_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_photo_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable');
            $table->string('status', 20)->default('approved');
            $table->foreignId('proposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['gallery_photo_id', 'taggable_type', 'taggable_id']);
            $table->index('status');
        });

        Schema::table('players', function (Blueprint $table) {
            $table->boolean('image_consent')->default(true)->after('is_active');
            $table->timestamp('image_consent_changed_at')->nullable()->after('image_consent');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['image_consent', 'image_consent_changed_at']);
        });

        Schema::dropIfExists('photo_tags');

        Schema::table('galleries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
            $table->dropColumn('event_date');
        });
    }
};

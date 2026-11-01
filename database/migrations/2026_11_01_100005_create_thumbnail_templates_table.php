<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Miniatury transmisji (/miniatury): zapisane szablony (kolory, czcionka, obrazki, tło)
 * oraz kolor drużyny rywala, który barwi jej połowę grafiki.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thumbnail_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->foreignId('competition_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tournament_id')->nullable()->constrained()->nullOnDelete();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        if (! Schema::hasColumn('tournaments', 'logo_path')) {
            Schema::table('tournaments', fn (Blueprint $table) => $table->string('logo_path')->nullable());
        }

        foreach (['rival_clubs', 'rival_teams'] as $table) {
            if (! Schema::hasColumn($table, 'brand_color')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->string('brand_color', 7)->nullable());
            }
        }

        // Szablony startowe dla klubu, który ma już swoje treści (ligi wojewódzkie i Memoriał).
        if (! DB::table('pages')->where('slug', 'o-klubie')->exists()) {
            return;
        }

        $corners = [
            ['path' => '/img/miniatury/herb-wielkopolski.png', 'position' => 'tl', 'size' => 200],
            ['path' => '/img/miniatury/klaun.png', 'position' => 'tr', 'size' => 210],
        ];
        $templates = [
            ['Wielkopolska Liga Juniorów Młodszych', ['label' => 'Wielkopolska Liga Juniorów Młodszych', 'accent' => '#60a5fa', 'bar_from' => '#1b1f3a', 'bar_to' => '#16106e', 'images' => $corners]],
            ['Wielkopolska Liga Juniorów', ['label' => 'Wielkopolska Liga Juniorów', 'accent' => '#a78bfa', 'bar_from' => '#4a0fb4', 'bar_to' => '#3a0c8c', 'images' => $corners]],
            ['Memoriał im. Włodzimierza Winklera', ['label' => 'Memoriał im. Włodzimierza Winklera', 'accent' => '#facc15', 'bar_from' => '#0b1f4d', 'bar_to' => '#1d7fd6', 'images' => [['path' => '/img/miniatury/klaun.png', 'position' => 'tr', 'size' => 210]], 'tournament_logo' => true]],
        ];

        foreach ($templates as [$name, $settings]) {
            $competitionId = DB::table('competitions')->where('name', $name)->value('id');
            DB::table('thumbnail_templates')->insert([
                'name' => $name,
                'competition_id' => $competitionId,
                'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('thumbnail_templates');

        if (Schema::hasColumn('tournaments', 'logo_path')) {
            Schema::table('tournaments', fn (Blueprint $table) => $table->dropColumn('logo_path'));
        }

        foreach (['rival_clubs', 'rival_teams'] as $table) {
            if (Schema::hasColumn($table, 'brand_color')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('brand_color'));
            }
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolory grup minisiatkówki są teraz wspólne z kategoriami wiekowymi (trening, turniej).
     * Przenosi kolor ustawiony wcześniej w grupie, tylko gdy wspólnego koloru jeszcze nie ma.
     */
    public function up(): void
    {
        if (! Schema::hasTable('mini_groups') || ! Schema::hasTable('settings') || ! Schema::hasColumn('mini_groups', 'color')) {
            return;
        }

        $groups = DB::table('mini_groups')->whereNotNull('color')->where('color', '!=', '')->orderBy('id')->get(['players_on_court', 'color']);

        foreach ($groups as $group) {
            $key = 'color_age_mini'.max(1, min(4, (int) $group->players_on_court));

            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert(['key' => $key, 'value' => json_encode($group->color), 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};

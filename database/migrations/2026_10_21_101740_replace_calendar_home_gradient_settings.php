<?php

use App\Support\SiteSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mecz domowy w kalendarzu ma jednolite tło z ramką zamiast gradientu.
     */
    public function up(): void
    {
        DB::table('settings')->whereIn('key', ['cal_home_from', 'cal_home_to'])->delete();

        foreach (['cal_home_bg' => '#0f2d6b', 'cal_home_text' => '#2f7fbf', 'cal_home_border' => '#2f7fbf'] as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value), 'updated_at' => now()]);
        }

        app(SiteSettings::class)->flush();
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['cal_home_bg', 'cal_home_border'])->delete();
        app(SiteSettings::class)->flush();
    }
};

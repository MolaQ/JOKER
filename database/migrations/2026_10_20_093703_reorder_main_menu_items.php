<?php

use App\Support\SiteSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Menu zapisane w panelu: Aktualności, Mecze, Tabela na początek, reszta w dotychczasowej kolejności.
     */
    public function up(): void
    {
        $row = DB::table('settings')->where('key', 'nav_items')->first();
        $items = $row ? json_decode((string) $row->value, true) : null;

        if (! is_array($items)) {
            return;
        }

        $first = [];
        foreach (['/aktualnosci', '/news', '/mecze', '/schedule', '/tabele', '/standings'] as $url) {
            foreach ($items as $index => $item) {
                if (rtrim((string) ($item['url'] ?? ''), '/') === $url) {
                    $first[] = $item;
                    unset($items[$index]);
                }
            }
        }

        DB::table('settings')->where('key', 'nav_items')->update(['value' => json_encode(array_merge($first, array_values($items)))]);
        Cache::forget(SiteSettings::CACHE_KEY);
    }

    public function down(): void
    {
        //
    }
};

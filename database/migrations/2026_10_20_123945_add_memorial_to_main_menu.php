<?php

use App\Support\SiteSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Zakładka „Memoriał” w zapisanym menu, zaraz po „Tabela” (albo na końcu).
     */
    public function up(): void
    {
        $row = DB::table('settings')->where('key', 'nav_items')->first();
        $items = $row ? json_decode((string) $row->value, true) : null;

        if (! is_array($items) || collect($items)->contains(fn (array $item) => rtrim((string) ($item['url'] ?? ''), '/') === '/memorial')) {
            return;
        }

        $position = collect($items)->search(fn (array $item) => in_array(rtrim((string) ($item['url'] ?? ''), '/'), ['/tabele', '/standings'], true));
        $position = $position === false ? count($items) : $position + 1;
        array_splice($items, $position, 0, [['label' => 'Memoriał', 'url' => '/memorial']]);

        DB::table('settings')->where('key', 'nav_items')->update(['value' => json_encode(array_values($items))]);
        Cache::forget(SiteSettings::CACHE_KEY);
    }

    public function down(): void
    {
        //
    }
};

<?php

use App\Models\Setting;
use App\Support\SiteSettings;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const AWARD = 'Brązowy Ośrodek PZPS 2024/25, 2025/26 i 2026 (Certyfikacja Młodzieżowych Ośrodków Siatkarskich PZPS)';

    /**
     * Brązowe odznaki PZPS z trzech edycji certyfikacji dopisujemy do wyróżnień zapisanych już w panelu.
     * Bez zapisanej wartości strona i tak bierze domyślną listę z config/site.php, która je zawiera.
     */
    public function up(): void
    {
        $setting = Setting::find('seo_awards');

        if (! $setting || str_contains((string) $setting->value, 'Brązowy Ośrodek PZPS')) {
            return;
        }

        $setting->update(['value' => trim(trim((string) $setting->value)."\n".self::AWARD)]);
        app(SiteSettings::class)->flush();
    }

    public function down(): void
    {
        $setting = Setting::find('seo_awards');

        if ($setting) {
            $setting->update(['value' => trim(str_replace(self::AWARD, '', (string) $setting->value))]);
        }
    }
};

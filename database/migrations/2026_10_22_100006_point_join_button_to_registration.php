<?php

use App\Models\Setting;
use App\Support\SiteSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Przycisk „Dołącz” w menu prowadzi do rejestracji konta zamiast do formularza kontaktowego.
     * Zmienia tylko zapisany stary adres, inny wpisany ręcznie zostaje.
     */
    public function up(): void
    {
        Setting::where('key', 'nav_button_url')->get()
            ->filter(fn (Setting $setting) => $setting->value === '/kontakt')
            ->each(fn (Setting $setting) => $setting->update(['value' => '/rejestracja']));

        Cache::forget(SiteSettings::CACHE_KEY);
    }

    public function down(): void
    {
        Setting::where('key', 'nav_button_url')->get()
            ->filter(fn (Setting $setting) => $setting->value === '/rejestracja')
            ->each(fn (Setting $setting) => $setting->update(['value' => '/kontakt']));

        Cache::forget(SiteSettings::CACHE_KEY);
    }
};

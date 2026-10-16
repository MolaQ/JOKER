<?php

namespace App\Providers;

use App\Models\RivalTeam;
use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Polskie nazwy dni i miesięcy w datach (np. "sob, 18 paź").
        Carbon::setLocale(config('app.locale'));

        // Alias tylko dla nowego polimorficznego pola "competitor" w tabeli ligowej.
        // Używamy morphMap (nie enforceMorphMap), aby nie wymuszać aliasów dla
        // pozostałych relacji polimorficznych w aplikacji (np. Likeable).
        Relation::morphMap([
            'team' => Team::class,
            'rival_team' => RivalTeam::class,
        ]);
    }
}

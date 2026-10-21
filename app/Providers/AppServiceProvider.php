<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Document;
use App\Models\Game;
use App\Models\Page;
use App\Models\Player;
use App\Models\RivalTeam;
use App\Models\Team;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Support\SearchIndex;
use App\Support\SiteSettings;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SiteSettings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ustawienia z panelu dostępne w każdym widoku jako $site['klucz'].
        View::share('site', $this->app->make(SiteSettings::class));

        // Polskie nazwy dni i miesięcy w datach (np. "sob, 18 paź").
        Carbon::setLocale(config('app.locale'));

        // Alias tylko dla nowego polimorficznego pola "competitor" w tabeli ligowej.
        // Używamy morphMap (nie enforceMorphMap), aby nie wymuszać aliasów dla
        // pozostałych relacji polimorficznych w aplikacji (np. Likeable).
        Relation::morphMap([
            'team' => Team::class,
            'rival_team' => RivalTeam::class,
            'tournament_match' => TournamentMatch::class,
        ]);

        // Każda zmiana treści odświeża indeks wyszukiwarki.
        foreach ([Article::class, Document::class, Game::class, Page::class, Player::class, Team::class, User::class] as $model) {
            $model::saved(fn () => SearchIndex::forget());
            $model::deleted(fn () => SearchIndex::forget());
        }
    }
}

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
use App\Permission;
use App\Support\PushTriggers;
use App\Support\SearchIndex;
use App\Support\Sitemap;
use App\Support\SiteSettings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
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
        // Zmiana treści unieważnia mapę strony; następne wejście robota zbuduje ją od nowa.
        foreach (Sitemap::WATCHED as $model) {
            $model::saved(fn () => Sitemap::forget());
            $model::deleted(fn () => Sitemap::forget());
        }

        // Ustawienia z panelu dostępne w każdym widoku jako $site['klucz'].
        View::share('site', $this->app->make(SiteSettings::class));

        // Uprawnienia z App\Permission (np. can:teams.manage) liczone z ról konta i jego wyjątków.
        Gate::before(fn (User $user, string $ability) => ($permission = Permission::tryFrom($ability)) ? $user->hasPermission($permission) : null);
        Gate::define('panel', fn (User $user) => $user->canAccessPanel());

        // Powiadomienia push o nowych aktualnościach, wynikach i galeriach.
        PushTriggers::register();

        // Wiadomości o koncie po polsku.
        VerifyEmail::toMailUsing(fn (User $user, string $url) => (new MailMessage)
            ->subject('Potwierdź adres e-mail')
            ->greeting('Cześć, '.$user->name.'!')
            ->line('Kliknij przycisk, aby potwierdzić adres e-mail konta w serwisie '.config('app.name').'.')
            ->action('Potwierdź adres', $url)
            ->line('Jeśli to nie Ty zakładałeś konto, zignoruj tę wiadomość.')
            ->salutation('Pozdrawiamy, '.config('app.name')));
        ResetPassword::toMailUsing(fn (User $user, string $token) => (new MailMessage)
            ->subject('Ustaw nowe hasło')
            ->greeting('Cześć, '.$user->name.'!')
            ->line('Ktoś (pewnie Ty) poprosił o zmianę hasła do konta.')
            ->action('Ustaw nowe hasło', route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->line('Link jest ważny przez '.config('auth.passwords.users.expire').' minut. Jeśli to nie Ty, zignoruj tę wiadomość.')
            ->salutation('Pozdrawiamy, '.config('app.name')));

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

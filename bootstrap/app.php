<?php

use App\Http\Middleware\EnsurePrivacyConsent;
use App\Http\Middleware\RedirectToCanonicalUrl;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackPageView;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            RedirectToCanonicalUrl::class,
            SetLocale::class,
            TrackPageView::class,
            EnsurePrivacyConsent::class,
        ]);

        // Skrypt statystyk wysyła dane przez sendBeacon (bez tokenu CSRF).
        $middleware->validateCsrfTokens(except: ['statystyki/*', 'powiadomienia/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Serwer poczty nie odpowiada albo zły login w .env: zamiast strony 500 wracamy do formularza z komunikatem.
        // Błąd i tak trafia do logu (storage/logs).
        $exceptions->render(function (TransportExceptionInterface $e, Request $request) {
            if ($request->expectsJson() || $request->hasHeader('X-Livewire') || $request->isMethod('GET')) {
                return null;
            }

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', __('Nie udało się wysłać wiadomości e-mail. Spróbuj za chwilę, a jeśli to się powtarza, napisz do klubu.'));
        });
    })->create();

<?php

use App\Http\Middleware\RedirectToCanonicalUrl;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackPageView;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

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
        ]);

        // Skrypt statystyk wysyła dane przez sendBeacon (bez tokenu CSRF).
        $middleware->validateCsrfTokens(except: ['statystyki/*', 'powiadomienia/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

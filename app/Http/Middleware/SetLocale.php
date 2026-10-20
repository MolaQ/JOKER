<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;

/**
 * Język strony publicznej wybrany flagą w nagłówku (zapamiętany w sesji, domyślnie polski).
 * Panel administracyjny (także jego komponenty Livewire) zawsze działa po polsku.
 */
class SetLocale
{
    /** @var array<int, string> */
    public const LOCALES = ['pl', 'en'];

    public const SESSION_KEY = 'locale';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;

        if (! in_array($locale, self::LOCALES, true) || $this->isAdminPanel()) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    /**
     * Czy żądanie dotyczy panelu (bezpośrednio lub przez aktualizację komponentu Livewire z panelu).
     */
    private function isAdminPanel(): bool
    {
        $path = trim((string) Livewire::originalPath(), '/');

        return $path === 'panel' || str_starts_with($path, 'panel/');
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zgoda RODO jest warunkiem konta: zalogowana osoba bez zapisanej zgody (np. konto sprzed
 * wprowadzenia zgód) najpierw ją wyraża albo usuwa konto; do tego czasu widzi tylko stronę zgody.
 */
class EnsurePrivacyConsent
{
    /** Trasy dostępne bez zgody: sama zgoda, wylogowanie, polityka prywatności i usunięcie konta. */
    private const ALLOWED = ['account.consent', 'account.consent.store', 'account.destroy', 'logout', 'pages.show', 'verification.*'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->hasPrivacyConsent() || $request->routeIs(...self::ALLOWED)) {
            return $next($request);
        }

        if ($request->isMethod('GET') && ! $request->expectsJson()) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('account.consent');
        }

        abort(403, __('Najpierw wyraź zgodę na przetwarzanie danych w serwisie klubu.'));
    }
}

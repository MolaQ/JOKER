<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jeden adres na stronę (SEO): „/zawodnicy/12” i „/zawodnicy/12-stara-nazwa”
 * przekierowują na aktualny „/zawodnicy/12-jan-kowalski”.
 */
class RedirectToCanonicalUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if (! $request->isMethod('GET') || ! $route?->getName()) {
            return $next($request);
        }

        $parameters = $route->parameters();
        $changed = false;

        foreach ($parameters as $name => $value) {
            if ($value instanceof Model && (string) $route->originalParameter($name) !== (string) $value->getRouteKey()) {
                $changed = true;
            }
        }

        if ($changed) {
            // Zapytanie doklejamy osobno: ?category= nie może trafić w parametr trasy o tej samej nazwie.
            $query = $request->getQueryString();

            return redirect(route($route->getName(), $parameters).($query ? '?'.$query : ''), 301);
        }

        return $next($request);
    }
}

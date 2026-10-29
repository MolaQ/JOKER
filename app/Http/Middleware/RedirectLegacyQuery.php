<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stare polskie parametry w adresach (?pokaz=wyniki, ?zakladka=drabinka...) przekierowują na angielskie,
 * żeby zapisane linki i wyniki w Google dalej działały.
 */
class RedirectLegacyQuery
{
    /** Stara nazwa parametru => nowa. */
    public const NAMES = [
        'szukaj' => 'search', 'druzyna' => 'team', 'rozgrywki' => 'competition', 'pokaz' => 'show',
        'miejsce' => 'venue', 'sezon' => 'season', 'rozmowa' => 'conversation', 'do' => 'to', 'etap' => 'stage',
        'kategoria' => 'category', 'zakladka' => 'tab', 'sortuj' => 'sort', 'kierunek' => 'direction',
        'co' => 'action', 'kto' => 'user', 'od' => 'from', 'zmiana' => 'change', 'mecz' => 'match',
        'sekcja' => 'section', 'okres' => 'period', 'temat' => 'subject',
    ];

    /** Stare wartości wybranych parametrów (po nowej nazwie). */
    private const VALUES = [
        'show' => ['wszystkie' => 'all', 'nadchodzace' => 'upcoming', 'wyniki' => 'results'],
        'venue' => ['dom' => 'home', 'wyjazd' => 'away'],
        'tab' => ['druzyny' => 'teams', 'grupy' => 'groups', 'mecze' => 'matches', 'drabinka' => 'bracket', 'klasyfikacja' => 'ranking', 'zasady' => 'rules', 'informator' => 'info'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') || $request->query->count() === 0) {
            return $next($request);
        }

        $query = $request->query->all();
        $translated = [];
        $changed = false;

        foreach ($query as $name => $value) {
            $newName = self::NAMES[$name] ?? $name;
            $newValue = is_string($value) ? (self::VALUES[$newName][$value] ?? $value) : $value;
            $changed = $changed || $newName !== $name || $newValue !== $value;
            $translated[$newName] = $newValue;
        }

        if (! $changed) {
            return $next($request);
        }

        return redirect($request->url().'?'.http_build_query($translated), 301);
    }
}

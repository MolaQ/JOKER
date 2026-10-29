<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Support\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    /**
     * Zakładka „Memoriał” w menu: najnowsza edycja cyklicznego turnieju klubu (System → Moduły).
     */
    public function memorial(): RedirectResponse|View
    {
        abort_if(blank(app(SiteSettings::class)['featured_tournament_name']), 404);

        $tournament = Tournament::where('series', Tournament::SERIES_MEMORIAL)
            ->where('has_page', true)
            ->orderByDesc('start_date')
            ->first();

        if ($tournament === null) {
            return view('tournaments.memorial-soon');
        }

        $query = request()->getQueryString();

        return redirect(route('tournaments.show', $tournament).($query ? '?'.$query : ''));
    }

    /**
     * Kategoria wiekowa w adresie (/tournaments/1/memorial-2026/kadeci); pierwsza kategoria bez dopisku.
     * Stary adres z ?category=ID przekierowuje na nowy.
     */
    public function show(Request $request, Tournament $tournament, ?string $category = null): View|RedirectResponse
    {
        abort_unless($tournament->has_page, 404);

        $categories = $tournament->categories()->get(['id', 'tournament_id', 'name']);
        $chosen = $request->filled('category')
            ? $categories->firstWhere('id', (int) $request->query('category'))
            : ($category !== null ? $categories->first(fn ($option) => $option->slug() === $category) : null);
        $canonical = $chosen && $chosen->isNot($categories->first()) ? $chosen->slug() : null;

        if ($request->filled('category') || $category !== $canonical) {
            return redirect()->route('tournaments.show', ['tournament' => $tournament, 'category' => $canonical, ...$request->except('category')], 301);
        }

        return view('tournaments.show', ['tournament' => $tournament, 'category' => $chosen ?? $categories->first()]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Livewire\TournamentPage;
use App\Models\Sponsor;
use App\Models\Tournament;
use App\Models\TournamentCategory;
use App\Models\TournamentMatch;
use App\Support\SiteSettings;
use App\Support\TournamentBracket;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Ekran turnieju na telewizor: plansze kolejnych kategorii (grupy, mecze teraz i następne, wyniki, drabinka, sponsorzy).
 * Każda plansza to osobne wczytanie strony (?s=numer), więc wyniki są zawsze świeże, a telewizor sam wraca po błędzie sieci.
 */
class TournamentTvController extends Controller
{
    public function show(Request $request, Tournament $tournament): View
    {
        abort_unless($tournament->has_page, 404);

        $settings = app(SiteSettings::class);
        $categories = $tournament->categories()
            ->with(['groups.participants.competitor', 'participants.competitor', 'matches.home.competitor', 'matches.away.competitor', 'matches.group', 'matches.category.tournament'])
            ->get();

        $slides = $categories->flatMap(fn (TournamentCategory $category) => $this->categorySlides($category))->values();

        if ($settings['tv_sponsors']) {
            $sponsors = Sponsor::active()->ordered()->whereNotNull('logo_path')->get();
            if ($sponsors->isNotEmpty()) {
                $slides->push(['type' => 'sponsors', 'category' => null, 'title' => __('Dziękujemy sponsorom'), 'sponsors' => $sponsors]);
            }
        }

        $index = $slides->isEmpty() ? 0 : ((int) $request->query('s', 0)) % $slides->count();
        $live = $categories->flatMap(fn (TournamentCategory $category) => $category->matches->where('is_live', true))->sortBy('court')->values();

        return view('tournaments.tv', [
            'tournament' => $tournament,
            'slides' => $slides,
            'slide' => $slides->get($index),
            'index' => $index,
            'next' => $slides->isEmpty() ? 0 : ($index + 1) % $slides->count(),
            'seconds' => max(5, (int) ($settings['tv_interval'] ?: 25)),
            'live' => $settings['tv_live_bar'] ? $live : collect(),
            'brackets' => $categories->mapWithKeys(fn (TournamentCategory $category) => [$category->id => TournamentBracket::for($category)]),
            'footer' => $settings['tv_footer'] ?: __('Wyniki na żywo: :site', ['site' => parse_url(config('app.url'), PHP_URL_HOST) ?: config('app.url')]),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function categorySlides(TournamentCategory $category): Collection
    {
        $slides = collect();
        $matches = $category->matches;

        // Tabele grup: po dwie na planszy.
        foreach ($category->groups->chunk(2) as $groups) {
            $slides->push(['type' => 'groups', 'category' => $category, 'title' => __('Tabele grup'), 'groups' => $groups->values()]);
        }

        $upcoming = $matches->filter(fn (TournamentMatch $match) => $match->is_live || ($match->home_sets === null && $match->starts_at !== null))
            ->sortBy(fn (TournamentMatch $match) => [$match->is_live ? 0 : 1, $match->starts_at?->timestamp ?? PHP_INT_MAX, $match->number ?? PHP_INT_MAX])
            ->take(8)->values();
        if ($upcoming->isNotEmpty()) {
            $slides->push(['type' => 'matches', 'category' => $category, 'title' => __('Teraz i za chwilę'), 'matches' => $upcoming]);
        }

        $results = $matches->filter(fn (TournamentMatch $match) => $match->isFinished())
            ->sortByDesc(fn (TournamentMatch $match) => [$match->starts_at?->timestamp ?? 0, $match->number ?? 0])
            ->take(8)->values();
        if ($results->isNotEmpty()) {
            $slides->push(['type' => 'matches', 'category' => $category, 'title' => __('Ostatnie wyniki'), 'matches' => $results]);
        }

        $knockout = $matches->where('stage', '!=', TournamentMatch::STAGE_GROUP);
        if ($knockout->isNotEmpty()) {
            $slides->push(['type' => 'bracket', 'category' => $category, 'title' => __('Drabinka i mecze o miejsca'), 'columns' => TournamentPage::boardColumns($knockout)]);
        }

        return $slides;
    }
}

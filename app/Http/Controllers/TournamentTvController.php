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
        $brackets = $categories->mapWithKeys(fn (TournamentCategory $category) => [$category->id => TournamentBracket::for($category)]);
        $perSlide = in_array((int) $settings['tv_matches_per_slide'], [6, 8, 10], true) ? (int) $settings['tv_matches_per_slide'] : 8;

        $slides = $categories->flatMap(fn (TournamentCategory $category) => $this->categorySlides($category, $brackets[$category->id], $settings, $perSlide))->values();

        if ($settings['tv_sponsors']) {
            $sponsors = Sponsor::active()->ordered()->whereNotNull('logo_path')->get();
            if ($sponsors->isNotEmpty()) {
                $slides->push(['type' => 'sponsors', 'category' => null, 'title' => __('Dziękujemy sponsorom'), 'sponsors' => $sponsors]);
            }
        }

        $index = $slides->isEmpty() ? 0 : ((int) $request->query('s', 0)) % $slides->count();
        $slide = $slides->get($index);
        $live = $categories->flatMap(fn (TournamentCategory $category) => $category->matches->where('is_live', true))->sortBy('court')->values();
        $seconds = (int) (($slide['type'] ?? '') === 'groups' ? $settings['tv_time_tables'] : $settings['tv_time_other']);

        return view('tournaments.tv', [
            'tournament' => $tournament,
            'slides' => $slides,
            'slide' => $slide,
            'index' => $index,
            'next' => $slides->isEmpty() ? 0 : ($index + 1) % $slides->count(),
            'seconds' => max(5, $seconds ?: 20),
            'perSlide' => $perSlide,
            'live' => $settings['tv_live_bar'] ? $live : collect(),
            'brackets' => $brackets,
            'colors' => [
                'top' => $settings->color('tv_bg_top'),
                'bottom' => $settings->color('tv_bg_bottom'),
                'accent' => $settings['tv_accent_mode'] === 'custom' ? $settings->color('tv_accent') : null,
                'live' => $settings->color('tv_live_color'),
            ],
            'footer' => $settings['tv_footer'] ?: __('Wyniki na żywo: :site', ['site' => parse_url(config('app.url'), PHP_URL_HOST) ?: config('app.url')]),
        ]);
    }

    /**
     * Plansze kategorii: tabele (duża grupa osobno, dwie małe razem), dla każdej grupy najbliższe mecze i wyniki,
     * drabinka, klasyfikacja. Każdą planszę można wyłączyć w ustawieniach.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function categorySlides(TournamentCategory $category, TournamentBracket $bracket, SiteSettings $settings, int $perSlide): Collection
    {
        $slides = collect();
        $groups = $category->groups->sortBy([['display_order', 'asc'], ['id', 'asc']])->values();

        if ($settings['tv_show_tables']) {
            $pending = collect();
            foreach ($groups as $group) {
                if ($bracket->members($group)->count() >= 6) {
                    $slides->push(['type' => 'groups', 'category' => $category, 'title' => __('Tabela'), 'groups' => collect([$group])]);

                    continue;
                }
                $pending->push($group);
                if ($pending->count() === 2) {
                    $slides->push(['type' => 'groups', 'category' => $category, 'title' => __('Tabele grup'), 'groups' => $pending]);
                    $pending = collect();
                }
            }
            if ($pending->isNotEmpty()) {
                $slides->push(['type' => 'groups', 'category' => $category, 'title' => __('Tabela'), 'groups' => $pending]);
            }
        }

        // Mecze grup osobno dla każdej grupy; kategoria bez grup ma jedną listę.
        $sets = $groups->isEmpty()
            ? collect([[null, $category->matches]])
            : $groups->map(fn ($group) => [$group, $category->matches->where('tournament_group_id', $group->id)]);

        foreach ($sets as [$group, $matches]) {
            $suffix = $group ? ' · '.$group->name : '';

            $upcoming = $matches->filter(fn (TournamentMatch $match) => $match->is_live || ($match->home_sets === null && $match->starts_at !== null))
                ->sortBy(fn (TournamentMatch $match) => [$match->is_live ? 0 : 1, $match->starts_at?->timestamp ?? PHP_INT_MAX, $match->number ?? PHP_INT_MAX])
                ->take($perSlide)->values();
            if ($settings['tv_show_upcoming'] && $upcoming->isNotEmpty()) {
                $slides->push(['type' => 'matches', 'category' => $category, 'title' => __('Najbliższe mecze').$suffix, 'matches' => $upcoming]);
            }

            $results = $matches->filter(fn (TournamentMatch $match) => $match->isFinished())
                ->sortByDesc(fn (TournamentMatch $match) => [$match->starts_at?->timestamp ?? 0, $match->number ?? 0])
                ->take($perSlide)->values();
            if ($settings['tv_show_results'] && $results->isNotEmpty()) {
                $slides->push(['type' => 'matches', 'category' => $category, 'title' => __('Wyniki').$suffix, 'matches' => $results]);
            }
        }

        $knockout = $category->matches->where('stage', '!=', TournamentMatch::STAGE_GROUP);
        if ($settings['tv_show_bracket'] && $knockout->isNotEmpty()) {
            $slides->push(['type' => 'bracket', 'category' => $category, 'title' => __('Drabinka i mecze o miejsca'), 'columns' => TournamentPage::boardColumns($knockout)]);
        }

        $standings = $bracket->standings();
        if ($settings['tv_show_standings'] && $standings->contains(fn (array $row) => $row['participant'] !== null)) {
            $slides->push(['type' => 'standings', 'category' => $category, 'title' => __('Klasyfikacja końcowa'), 'standings' => $standings]);
        }

        return $slides;
    }
}

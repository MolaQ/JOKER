<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\TournamentCategory;
use App\Models\TournamentMatch;
use App\Models\TrainingSession;
use App\Support\SiteSettings;
use App\Support\TournamentBracket;
use App\Support\TournamentHalls;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Zbiorcza lista meczów jednego dnia turnieju ze wszystkich kategorii, po kolei według godziny
 * (przy tej samej godzinie według boiska). Na kartce A4 do druku albo PDF, np. dla sędziów i stolików.
 */
class TournamentDaySheetController extends Controller
{
    public function __invoke(Request $request, Tournament $tournament, SiteSettings $site): View
    {
        $categories = $tournament->categories()
            ->with(['groups', 'matches.home.competitor', 'matches.away.competitor', 'matches.group'])
            ->get();

        $matches = $categories->flatMap(fn (TournamentCategory $category) => $category->matches->each->setRelation('category', $category))
            ->filter(fn (TournamentMatch $match) => $match->scheduledAt() !== null);

        $days = $matches->map(fn (TournamentMatch $match) => $match->scheduledAt()->toDateString())->unique()->sort()->values();
        $day = $days->contains($request->query('day')) ? (string) $request->query('day')
            : ($days->contains(today()->toDateString()) ? today()->toDateString() : (string) $days->first());

        $order = array_flip(TournamentHalls::MAIN_VENUES);
        $courtOrder = fn (?string $court) => ($venue = TrainingSession::venueForCourt($court)) ? '0-'.$order[$venue] : '1-'.mb_strtolower((string) $court);
        $brackets = [];

        $rows = $matches->filter(fn (TournamentMatch $match) => $match->scheduledAt()->toDateString() === $day)
            ->sortBy(fn (TournamentMatch $match) => [$match->scheduledAt()->format('H:i'), $courtOrder($match->court), $match->category->display_order, $match->number ?? PHP_INT_MAX])
            ->map(function (TournamentMatch $match) use (&$brackets) {
                $category = $match->category;
                $bracket = $brackets[$category->id] ??= TournamentBracket::for($category);

                return [
                    'time' => $match->scheduledAt()->format('G:i'),
                    'court' => $match->court ? TournamentHalls::label($match->court, $category->id) : null,
                    'icon' => TournamentHalls::icon($match->court, $category->id),
                    'category' => $category,
                    'number' => $match->number,
                    'phase' => $match->group ? ($category->groups->count() > 1 ? $match->group->name : null) : $match->title(),
                    'home' => $this->side($bracket, $match, 'home'),
                    'away' => $this->side($bracket, $match, 'away'),
                    'result' => $match->home_sets !== null ? $match->home_sets.':'.$match->away_sets : null,
                    'sets' => $match->home_sets !== null ? $match->setsLabel() : null,
                    'live' => $match->is_live,
                ];
            })
            ->values();

        return view('admin.tournaments.day-sheet', [
            'tournament' => $tournament,
            'days' => $days,
            'day' => $day,
            'dayLabel' => $day !== '' ? Carbon::parse($day)->translatedFormat('l, j F Y') : '',
            'rows' => $rows,
            'categories' => $rows->pluck('category')->unique('id')->sortBy('display_order')->values(),
            'logo' => $site->image('logo', asset('img/logo-256.webp')),
            'clubName' => (string) $site->get('club_name'),
        ]);
    }

    /**
     * Pewna drużyna albo opis miejsca (np. „Zwycięzca meczu 9”), bez prognoz.
     */
    private function side(TournamentBracket $bracket, TournamentMatch $match, string $side): string
    {
        $resolved = $bracket->side($match, $side);

        return $resolved['state'] === TournamentBracket::CONFIRMED ? $resolved['text'] : ($resolved['description'] ?? $resolved['text']);
    }
}

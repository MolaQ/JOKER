<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\TournamentCategory;
use App\Models\TournamentMatch;
use App\Models\TrainingSession;
use App\Support\Media;
use App\Support\SiteSettings;
use App\Support\TournamentBracket;
use App\Support\TournamentHalls;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Rozpiska dla gości: harmonogram kategorii (albo całego turnieju) na kartce A4 z logo klubu,
 * grupami, meczami i adresami hal. Drukuje się z przeglądarki albo zapisuje jako PDF.
 */
class TournamentScheduleSheetController extends Controller
{
    public function __invoke(Request $request, Tournament $tournament, SiteSettings $site): View
    {
        $categories = $tournament->categories()
            ->with(['groups.participants', 'participants', 'matches.home', 'matches.away', 'matches.group'])
            ->orderBy('display_order')->orderBy('id')
            ->get();

        $chosen = $request->integer('category');
        $shown = $chosen ? $categories->where('id', $chosen)->values() : $categories;
        abort_if($shown->isEmpty(), 404);

        return view('admin.tournaments.schedule-sheet', [
            'tournament' => $tournament,
            'categories' => $categories,
            'chosen' => $chosen,
            'sheets' => $shown->map(fn (TournamentCategory $category) => $this->sheet($tournament, $category)),
            'logo' => $site->image('logo', asset('img/logo-256.webp')),
            'tournamentLogo' => $tournament->logo_path ? Media::url($tournament->logo_path) : null,
            'clubName' => (string) $site->get('club_name'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sheet(Tournament $tournament, TournamentCategory $category): array
    {
        $bracket = TournamentBracket::for($category);
        $order = array_flip(TournamentHalls::MAIN_VENUES);
        $courtOrder = fn (?string $court) => ($venue = TrainingSession::venueForCourt($court)) ? '0-'.$order[$venue] : '1-'.mb_strtolower((string) $court);

        $matches = $category->matches
            ->sortBy(fn (TournamentMatch $match) => [$match->starts_at?->toDateString() ?? '9999', $courtOrder($match->court), $match->starts_at?->format('H:i') ?? '99', $match->number ?? PHP_INT_MAX])
            ->values();

        $rows = $matches->map(fn (TournamentMatch $match) => [
            'day' => $match->starts_at?->toDateString(),
            'time' => $match->starts_at?->format('G:i'),
            'court' => $match->court ? TournamentHalls::label($match->court, $category->id) : null,
            'icon' => TournamentHalls::icon($match->court, $category->id),
            'title' => $match->group ? null : $match->title(),
            'group' => $match->group?->name,
            'home' => $this->side($bracket, $match, 'home'),
            'away' => $this->side($bracket, $match, 'away'),
            'number' => $match->number,
        ]);

        $teams = $category->participants->count();
        $groupMatches = $matches->where('stage', 'group');
        $roundRobin = $category->groups->count() <= 1 && $groupMatches->count() === $matches->count() && $teams > 1;
        // Pasek z najważniejszymi liczbami pod nagłówkiem: [liczba, podpis z poprawną odmianą].
        $plural = fn (int $n, string $one, string $few, string $many) => $n === 1 ? $one : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? $few : $many);
        $facts = collect([
            $teams ? [(string) $teams, $plural($teams, 'zespół', 'zespoły', 'zespołów')] : null,
            $matches->count() ? [(string) $matches->count(), $plural($matches->count(), 'spotkanie', 'spotkania', 'spotkań')] : null,
            $roundRobin ? [(string) ($teams - 1), $plural($teams - 1, 'mecz na zespół', 'mecze na zespół', 'meczów na zespół')] : null,
            $category->groups->count() > 1 ? [(string) $category->groups->count(), $plural($category->groups->count(), 'grupa', 'grupy', 'grup')] : null,
        ])->filter()->values();
        $system = $roundRobin ? 'każdy z każdym' : ($category->groups->count() > 1 ? 'faza grupowa i mecze o miejsca' : null);

        $groups = $category->groups->count() > 1
            ? $category->groups->sortBy('name', SORT_NATURAL)->map(fn ($group) => [
                'name' => $group->name,
                'court' => $group->court ? TournamentHalls::label($group->court, $category->id) : null,
                'teams' => $bracket->members($group)->pluck('text')->all(),
            ])->values()
            : collect();

        return [
            'category' => $category,
            'facts' => $facts,
            'system' => $system,
            'groups' => $groups,
            'days' => $rows->groupBy(fn ($row) => $row['day'] ?? ''),
            'facilities' => $this->facilities($tournament, $matches->pluck('court')->filter()->unique()),
        ];
    }

    /**
     * Strona meczu dla gości: pewna drużyna albo opis (np. „Zwycięzca meczu 9”), bez prognoz.
     */
    private function side(TournamentBracket $bracket, TournamentMatch $match, string $side): string
    {
        $resolved = $bracket->side($match, $side);

        return $resolved['state'] === TournamentBracket::CONFIRMED ? $resolved['text'] : ($resolved['description'] ?? $resolved['text']);
    }

    /**
     * Obiekty, w których kategoria gra: nazwa, adres i boiska z jej meczami.
     *
     * @param  Collection<int, string>  $courts
     * @return list<array<string, mixed>>
     */
    private function facilities(Tournament $tournament, Collection $courts): array
    {
        $result = [];

        foreach (TournamentHalls::legend($tournament) as $index => $facility) {
            $used = collect($facility['rooms'])->flatMap(fn ($room) => collect($room['courts'])
                ->filter(fn ($court) => $courts->contains($court['value']) || ($index === 0 && $courts->contains(fn ($value) => TrainingSession::courtLabel($value) === $court['value'])))
                ->map(fn ($court) => $court + ['room' => $room['name']]))->values();

            if ($used->isNotEmpty()) {
                $result[] = $facility + ['used' => $used->all()];
            }
        }

        return $result;
    }
}

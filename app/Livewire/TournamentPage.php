<?php

namespace App\Livewire;

use App\Models\Tournament;
use App\Models\TournamentCategory;
use App\Models\TournamentMatch;
use App\Support\TournamentTable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Strona turnieju z filtrem kategorii wiekowej i czytelnymi zakładkami.
 */
class TournamentPage extends Component
{
    public Tournament $tournament;

    #[Url(as: 'kategoria', except: '')]
    public string $category = '';

    #[Url(as: 'zakladka', except: '')]
    public string $tab = '';

    public function selectCategory(int $categoryId): void
    {
        $this->category = (string) $categoryId;
    }

    public function selectTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function render(): View
    {
        $categories = $this->tournament->categories()
            ->with(['groups.participants.competitor', 'groups.matches.home.competitor', 'groups.matches.away.competitor', 'participants.competitor', 'participants.group', 'matches.home.competitor', 'matches.away.competitor', 'matches.group'])
            ->get();

        /** @var TournamentCategory|null $current */
        $current = $categories->firstWhere('id', (int) $this->category) ?? $categories->first();

        $matches = $current?->matches ?? collect();
        $playoff = $matches->where('stage', TournamentMatch::STAGE_PLAYOFF)->sortBy([['bracket_round', 'asc'], ['bracket_slot', 'asc']])->groupBy('bracket_round');
        $placement = $matches->where('stage', TournamentMatch::STAGE_PLACEMENT)->sortBy('placement')->values();

        $tabs = collect([
            'druzyny' => [__('Drużyny'), $current?->participants->isNotEmpty()],
            'grupy' => [__('Grupy'), $current?->groups->isNotEmpty()],
            'mecze' => [__('Mecze'), $matches->isNotEmpty()],
            'drabinka' => [__('Drabinka i miejsca'), $playoff->isNotEmpty() || $placement->isNotEmpty()],
            'klasyfikacja' => [__('Klasyfikacja'), $current && TournamentTable::finalStandings($current)->isNotEmpty()],
            'zasady' => [__('Zasady'), filled($this->tournament->rules)],
            'informator' => [__('Informator'), filled($this->tournament->guest_info)],
        ])->filter(fn (array $entry) => $entry[1])->map(fn (array $entry) => $entry[0]);

        $currentTab = $tabs->has($this->tab) ? $this->tab : ($tabs->keys()->first() ?? 'druzyny');

        return view('livewire.tournament-page', [
            'categories' => $categories,
            'current' => $current,
            'tabs' => $tabs,
            'currentTab' => $currentTab,
            'matchesByDay' => $matches->groupBy(fn (TournamentMatch $match) => $match->starts_at?->toDateString() ?? ''),
            'playoff' => $playoff,
            'placement' => $placement,
            'finalStandings' => $current ? TournamentTable::finalStandings($current) : collect(),
        ]);
    }

    /**
     * @param  Collection<int, TournamentMatch>  $matches
     */
    public static function hasResults(Collection $matches): bool
    {
        return $matches->contains(fn (TournamentMatch $match) => $match->isFinished());
    }
}

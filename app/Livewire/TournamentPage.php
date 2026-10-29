<?php

namespace App\Livewire;

use App\Models\Tournament;
use App\Models\TournamentCategory;
use App\Models\TournamentMatch;
use App\Support\TournamentBracket;
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

    #[Url(as: 'category', except: '')]
    public string $category = '';

    #[Url(as: 'tab', except: '')]
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
            ->with(['groups.participants.competitor', 'groups.matches.home.competitor', 'groups.matches.away.competitor', 'participants.competitor', 'participants.group', 'matches.home.competitor', 'matches.away.competitor', 'matches.group', 'matches.category.tournament'])
            ->get();

        /** @var TournamentCategory|null $current */
        $current = $categories->firstWhere('id', (int) $this->category) ?? $categories->first();

        $matches = $current?->matches ?? collect();
        $bracket = $current ? TournamentBracket::for($current) : null;
        $board = self::boardColumns($matches->where('stage', '!=', TournamentMatch::STAGE_GROUP));
        $standings = $bracket?->standings() ?? collect();

        $tabs = collect([
            'teams' => [__('Drużyny'), $current?->participants->isNotEmpty()],
            'groups' => [__('Grupy'), $current?->groups->isNotEmpty()],
            'matches' => [__('Mecze'), $matches->isNotEmpty()],
            'bracket' => [__('Drabinka i miejsca'), $board->isNotEmpty()],
            'ranking' => [__('Klasyfikacja'), $standings->isNotEmpty()],
            'rules' => [__('Zasady'), filled($this->tournament->rules)],
            'info' => [__('Informator'), filled($this->tournament->guest_info)],
        ])->filter(fn (array $entry) => $entry[1])->map(fn (array $entry) => $entry[0]);

        $currentTab = $tabs->has($this->tab) ? $this->tab : ($tabs->keys()->first() ?? 'teams');

        return view('livewire.tournament-page', [
            'categories' => $categories,
            'current' => $current,
            'tabs' => $tabs,
            'currentTab' => $currentTab,
            'bracket' => $bracket,
            'board' => $board,
            'standings' => $standings,
        ]);
    }

    /**
     * Kolumny drabinki (faza = kolumna): mecze bez kolumny lądują w ostatniej, „Mecze o miejsca”.
     * Na telefonie kolumny idą jedna pod drugą, na komputerze obok siebie.
     *
     * @param  Collection<int, TournamentMatch>  $matches
     * @return Collection<int, array{name: string, matches: Collection<int, TournamentMatch>}>
     */
    public static function boardColumns(Collection $matches): Collection
    {
        $columns = $matches->groupBy(fn (TournamentMatch $match) => $match->bracket_round ?? 99)->sortKeys();
        $lastKey = $columns->keys()->last();

        return $columns->map(function (Collection $column, int $round) use ($lastKey) {
            $named = $column->pluck('phase_name')->filter()->first();

            return [
                'name' => match (true) {
                    filled($named) => $named,
                    $round === 99 => __('Mecze o miejsca'),
                    $column->contains(fn (TournamentMatch $match) => $match->placeAtStake() !== null) && $round === $lastKey => __('Finały'),
                    $column->count() === 2 => __('Półfinały'),
                    $column->count() === 4 => __('Ćwierćfinały'),
                    default => __('Runda :round', ['round' => $round]),
                },
                'matches' => $column->sortBy(fn (TournamentMatch $match) => [$match->bracket_slot ?? 999, $match->bracketFrom(), $match->number])->values(),
            ];
        })->values();
    }

    /**
     * @param  Collection<int, TournamentMatch>  $matches
     */
    public static function hasResults(Collection $matches): bool
    {
        return $matches->contains(fn (TournamentMatch $match) => $match->isFinished());
    }
}

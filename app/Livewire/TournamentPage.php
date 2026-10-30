<?php

namespace App\Livewire;

use App\Models\Tournament;
use App\Models\TournamentCategory;
use App\Models\TournamentMatch;
use App\Support\TournamentBracket;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Strona turnieju z filtrem kategorii wiekowej i czytelnymi zakładkami.
 */
class TournamentPage extends Component
{
    public Tournament $tournament;

    /** Kategoria wiekowa z adresu strony (/tournaments/1/memorial-2026/kadeci). */
    #[Locked]
    public ?int $categoryId = null;

    #[Url(as: 'tab', except: '')]
    public string $tab = '';

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
        $current = $categories->firstWhere('id', $this->categoryId) ?? $categories->first();

        $matches = $current?->matches ?? collect();
        $bracket = $current ? TournamentBracket::for($current) : null;
        $knockout = $matches->where('stage', '!=', TournamentMatch::STAGE_GROUP);
        // Mecze o dalsze miejsca, które nie wynikają z drabinki (np. piąte drużyny grup grają o 9. miejsce), stoją osobno nad drabinką.
        $standalone = $knockout->filter(fn (TournamentMatch $match) => $match->stage === TournamentMatch::STAGE_PLACEMENT && $match->bracket_round === null
            && ! collect([$match->home_source, $match->away_source])->contains(fn ($source) => str_starts_with((string) $source, 'winner:') || str_starts_with((string) $source, 'loser:')))
            ->sortBy(fn (TournamentMatch $match) => [$match->number ?? PHP_INT_MAX, $match->id])->values();
        $board = self::boardColumns($knockout->whereNotIn('id', $standalone->pluck('id')));
        $standings = $bracket?->standings() ?? collect();

        $tabs = collect([
            'teams' => [__('Drużyny'), $current?->participants->isNotEmpty()],
            'groups' => [__('Grupy'), $current?->groups->isNotEmpty()],
            'matches' => [__('Mecze'), $matches->isNotEmpty()],
            'bracket' => [__('Drabinka i miejsca'), $knockout->isNotEmpty()],
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
            'standalone' => $standalone,
            'knockout' => $knockout->sortBy(fn (TournamentMatch $match) => [$match->starts_at?->timestamp ?? PHP_INT_MAX, $match->number ?? PHP_INT_MAX, $match->id])->values(),
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

<?php

namespace App\Livewire;

use App\Models\Tournament;
use App\Models\TournamentCategory;
use App\Models\TournamentMatch;
use App\Support\TournamentBracket;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
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
            ->with(['groups.participants.competitor', 'groups.matches.home.competitor', 'groups.matches.away.competitor', 'participants.competitor', 'participants.group', 'matches.home.competitor', 'matches.away.competitor', 'matches.group', 'matches.category.tournament', 'awards.player', 'awards.participant.competitor'])
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
            'about' => [__('O turnieju'), filled(strip_tags((string) $this->tournament->about))],
            'teams' => [__('Drużyny'), $current?->participants->isNotEmpty()],
            'groups' => [__('Grupy'), $current?->groups->isNotEmpty()],
            'matches' => [__('Mecze'), $matches->isNotEmpty()],
            'bracket' => [__('Drabinka i miejsca'), $knockout->isNotEmpty()],
            'ranking' => [__('Klasyfikacja'), $standings->isNotEmpty() || $current?->awards->isNotEmpty()],
            'rules' => [__('Zasady'), filled($this->tournament->rules)],
            'info' => [__('Informator'), filled($this->tournament->guest_info)],
        ])->filter(fn (array $entry) => $entry[1])->map(fn (array $entry) => $entry[0]);

        $summary = self::summary($categories);

        // „O turnieju” otwiera się domyślnie przed turniejem; od pierwszego dnia pierwsza jest zakładka z grą,
        // a po ostatnim meczu turnieju klasyfikacja z podsumowaniem.
        $defaultTab = match (true) {
            $summary !== null && $tabs->has('ranking') => 'ranking',
            $this->tournament->start_date?->isFuture() === false && $tabs->count() > 1 => $tabs->keys()->reject(fn ($key) => $key === 'about')->first(),
            default => $tabs->keys()->first(),
        };
        $currentTab = $tabs->has($this->tab) ? $this->tab : ($defaultTab ?? 'teams');

        return view('livewire.tournament-page', [
            'categories' => $categories,
            'current' => $current,
            'tabs' => $tabs,
            'currentTab' => $currentTab,
            // W dniach turnieju wyniki u kibica odświeżają się same (tylko zakładki z wynikami, tylko widoczna karta):
            // co 30 sekund, gdy w turnieju trwa mecz, a poza tym co 2 minuty.
            'pollSeconds' => $categories->contains(fn (TournamentCategory $category) => $category->matches->contains('is_live', true)) ? 30 : 120,
            'autoRefresh' => in_array($currentTab, ['groups', 'matches', 'bracket', 'ranking'], true)
                && today()->between($this->tournament->start_date->copy()->startOfDay(), $this->tournament->lastDay()->endOfDay()),
            'bracket' => $bracket,
            'board' => $board,
            'standalone' => $standalone,
            'knockout' => $knockout->sortBy(fn (TournamentMatch $match) => [$match->starts_at?->timestamp ?? PHP_INT_MAX, $match->number ?? PHP_INT_MAX, $match->id])->values(),
            'standings' => $standings,
            'summary' => $summary,
            'schedule' => $currentTab === 'matches' && $current ? fn () => $this->schedule($categories, $current, $bracket) : null,
        ]);
    }

    /**
     * Terminarz kategorii (najcięższa część strony w dniu turnieju) z krótkiej pamięci podręcznej: kibice odświeżający
     * wyniki dostają gotowy HTML, a każda zmiana meczu, drużyny czy hali daje nowy klucz, więc wynik widać od razu.
     * Kilkadziesiąt sekund dotyczy tylko rzeczy liczonych od zegara (np. „możliwe opóźnienie”, liczba zdjęć).
     *
     * @param  Collection<int, TournamentCategory>  $categories
     */
    public function schedule(Collection $categories, TournamentCategory $current, TournamentBracket $bracket): string
    {
        $state = [
            $current->getAttributes(),
            $this->tournament->getAttributes(),
            $categories->flatMap->matches->map->getAttributes()->all(),
            $current->participants->map(fn ($participant) => [$participant->getAttributes(), $participant->competitor?->getAttributes()])->all(),
            $current->groups->map->getAttributes()->all(),
        ];
        $key = 'tournament-schedule:'.$current->id.':'.app()->getLocale().':'.now()->toDateString().':'.md5(serialize($state));

        return Cache::remember($key, 30, fn () => Blade::render('<x-tournament-schedule :matches="$matches" :bracket="$bracket" />', [
            'matches' => $current->matches,
            'bracket' => $bracket,
        ]));
    }

    /**
     * Podsumowanie po ostatnim meczu całego turnieju: w każdej kategorii podium i miejsca naszych drużyn.
     * Przed końcem (albo bez meczów) null.
     *
     * @param  Collection<int, TournamentCategory>  $categories
     * @return Collection<int, array{category: TournamentCategory, podium: Collection<int, array<string, mixed>>, ours: Collection<int, array<string, mixed>>}>|null
     */
    public static function summary(Collection $categories): ?Collection
    {
        $played = $categories->filter(fn (TournamentCategory $category) => $category->matches->isNotEmpty());

        if ($played->isEmpty() || $played->contains(fn (TournamentCategory $category) => $category->matches->contains(fn (TournamentMatch $match) => ! $match->isFinished()))) {
            return null;
        }

        return $played->map(function (TournamentCategory $category) {
            $standings = TournamentBracket::for($category)->standings()->filter(fn (array $row) => $row['participant'] !== null);

            return [
                'category' => $category,
                'podium' => $standings->where('position', '<=', 3)->values(),
                'ours' => $standings->filter(fn (array $row) => $row['participant']->isOwnTeam())->values(),
            ];
        })->values();
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
                // Kolejność jak w panelu: według numerów meczów (przestawianych przeciąganiem).
                'matches' => $column->sortBy(fn (TournamentMatch $match) => [$match->number ?? PHP_INT_MAX, $match->bracket_slot ?? 999, $match->id])->values(),
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

<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Player;
use App\Models\TournamentAward;
use App\Models\TournamentCategory;
use App\Support\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Zakładka „Wyróżnienia” w panelu turnieju: nazwa z listy w ustawieniach (albo własna), zawodnik klubu
 * z menu albo nazwisko zawodniczki rywali, opcjonalnie drużyna. Zawodnik klubu dostaje wpis w osiągnięciach.
 */
class TournamentAwards extends Component
{
    use ShowsToasts;

    public int $categoryId;

    /** @var array{name: string, custom: string, player: string, person: string, team: string} */
    public array $award = ['name' => '', 'custom' => '', 'player' => '', 'person' => '', 'team' => ''];

    public function mount(int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) app(SiteSettings::class)['tournament_award_names']))));
    }

    public function save(): void
    {
        $data = $this->validate([
            'award.name' => 'required|string|max:100',
            'award.custom' => 'required_if:award.name,__custom|nullable|string|max:100',
            'award.player' => 'nullable|exists:players,id',
            'award.person' => 'required_without:award.player|nullable|string|max:120',
            'award.team' => 'nullable|integer',
        ], [
            'award.custom.required_if' => 'Wpisz nazwę wyróżnienia.',
            'award.person.required_without' => 'Wybierz zawodnika klubu albo wpisz imię i nazwisko.',
        ])['award'];

        $category = $this->category();
        $team = $data['team'] ? $category->participants()->find((int) $data['team']) : null;

        DB::transaction(function () use ($category, $data, $team) {
            $award = $category->awards()->create([
                'name' => $data['name'] === '__custom' ? trim($data['custom']) : $data['name'],
                'player_id' => $data['player'] ?: null,
                'person_name' => $data['player'] ? null : trim((string) $data['person']),
                'tournament_participant_id' => $team?->id,
                'display_order' => (int) $category->awards()->max('display_order') + 1,
            ]);
            $award->syncAchievement();
        });

        $this->award = ['name' => '', 'custom' => '', 'player' => '', 'person' => '', 'team' => ''];
        $this->toast($data['player'] ? 'Wyróżnienie dodane, jest też w profilu zawodnika.' : 'Wyróżnienie dodane.');
    }

    public function delete(int $id): void
    {
        $this->category()->awards()->findOrFail($id)->delete();
        $this->toast('Wyróżnienie usunięte (także z profilu zawodnika).');
    }

    public function move(int $id, int $direction): void
    {
        $awards = $this->category()->awards()->get()->values();
        $index = $awards->search(fn (TournamentAward $award) => $award->id === $id);
        $target = $index + $direction;

        if ($index === false || $target < 0 || $target >= $awards->count()) {
            return;
        }

        $order = $awards->pluck('id')->all();
        [$order[$index], $order[$target]] = [$order[$target], $order[$index]];

        foreach ($order as $position => $awardId) {
            TournamentAward::whereKey($awardId)->update(['display_order' => $position]);
        }
    }

    private function category(): TournamentCategory
    {
        return TournamentCategory::findOrFail($this->categoryId);
    }

    public function render(): View
    {
        $category = $this->category()->load(['awards.player', 'awards.participant.competitor', 'participants.competitor']);
        $teamIds = $category->participants->where('competitor_type', 'team')->pluck('competitor_id');

        return view('livewire.admin.tournament-awards', [
            'category' => $category,
            'names' => self::names(),
            // Najpierw zawodnicy drużyn grających w tej kategorii, potem reszta klubu.
            'players' => Player::where('is_active', true)->with('team')->orderBy('last_name')->orderBy('first_name')->get()
                ->sortBy(fn (Player $player) => $teamIds->contains($player->team_id) ? 0 : 1)->values(),
            'teamIds' => $teamIds,
        ]);
    }
}

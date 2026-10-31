<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Competition;
use App\Models\Game;
use App\Permission;
use App\Services\StandingsCalculator;
use App\Support\DuplicateGames;
use App\Support\Trash;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * „Do zatwierdzenia → Możliwe duble”: mecze tej samej drużyny z tym samym rywalem tego samego dnia.
 * Zbędny mecz trafia do kosza, a dwumecz w jeden dzień oznacza się „to nie dubel”.
 */
class DuplicateGameReview extends Component
{
    use ShowsToasts;

    public function boot(): void
    {
        abort_unless(Auth::user()?->hasPermission(Permission::Games), 403);
    }

    public function delete(int $gameId): void
    {
        $groups = DuplicateGames::groups(Auth::user());
        abort_unless($groups->flatten()->contains('id', $gameId), 404);

        $game = Game::findOrFail($gameId);
        $competitionId = $game->competition_id;
        Trash::delete($game);

        if ($competitionId && $competition = Competition::find($competitionId)) {
            StandingsCalculator::recalculate($competition);
        }

        $this->toast('Mecz trafił do kosza. Przywrócisz go w: System → Kosz.');
    }

    public function dismiss(string $key): void
    {
        $group = DuplicateGames::groups(Auth::user())->get($key);
        abort_if($group === null, 404);

        DuplicateGames::dismiss($group->pluck('id')->all(), Auth::user());
        $this->toast('Zapisane: to nie dubel. Ostrzeżenie już nie wróci dla tych meczów.');
    }

    public function render(): View
    {
        return view('livewire.admin.duplicate-game-review', [
            'groups' => DuplicateGames::groups(Auth::user()),
        ]);
    }
}

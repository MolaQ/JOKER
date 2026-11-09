<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Player;
use App\Support\PlayerMerge;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * „Do zatwierdzenia → Możliwe duble zawodników”: ta sama osoba wpisana kilka razy.
 * „Zostaw ten” scala pozostałe wpisy z wybranym (PlayerMerge), „To różne osoby” ukrywa grupę.
 */
class DuplicatePlayerReview extends Component
{
    use ShowsToasts;

    public function boot(): void
    {
        abort_unless(Auth::user()?->can('teams.manage'), 403);
    }

    public function keep(string $key, int $playerId): void
    {
        $group = PlayerMerge::groups()->get($key);
        abort_if($group === null || ! $group->contains('id', $playerId), 404);

        $keep = $group->firstWhere('id', $playerId);
        $group->reject(fn (Player $player) => $player->is($keep))->each(fn (Player $duplicate) => PlayerMerge::merge($keep->fresh(), $duplicate->fresh()));

        $this->toast('Scalono: '.$keep->full_name.' ma teraz jeden profil (drugi wpis jest w koszu).');
    }

    public function dismiss(string $key): void
    {
        abort_unless(PlayerMerge::groups()->has($key), 404);
        PlayerMerge::dismiss($key);
        $this->toast('Zapisane: to różne osoby. Ta para nie będzie już podpowiadana.');
    }

    public function render(): View
    {
        return view('livewire.admin.duplicate-player-review', [
            'groups' => PlayerMerge::groups()->each(fn ($group) => $group->load(['awards', 'user:id,email', 'teams' => fn ($query) => $query->orderByDesc('team_player_season.season_id')])),
        ]);
    }
}

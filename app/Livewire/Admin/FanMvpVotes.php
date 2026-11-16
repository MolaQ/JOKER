<?php

namespace App\Livewire\Admin;

use App\Models\FanMvpVote;
use App\Support\FanMvp;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Panel → Kibicowskie MVP: kto na kogo głosował, mecz po meczu. Tylko dla administratora.
 */
class FanMvpVotes extends Component
{
    use WithPagination;

    /** Rozwinięty mecz, np. „game-12” albo „tournament_match-5”. */
    #[Url(as: 'mecz')]
    public string $open = '';

    public function boot(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
    }

    public function toggle(string $key): void
    {
        $this->open = $this->open === $key ? '' : $key;
    }

    public function render(): View
    {
        $page = FanMvpVote::query()
            ->select('match_type', 'match_id', DB::raw('count(*) as total'), DB::raw('max(created_at) as last_vote'))
            ->groupBy('match_type', 'match_id')
            ->orderByDesc('last_vote')
            ->paginate(20);

        $matches = $page->getCollection()->map(function (FanMvpVote $row) {
            $match = FanMvp::find($row->match_type, (int) $row->match_id);
            $key = $row->match_type.'-'.$row->match_id;

            return [
                'key' => $key,
                'label' => $match ? FanMvp::label($match) : 'Mecz usunięty',
                'url' => $match ? FanMvp::url($match) : null,
                'total' => (int) $row->total,
                'leader' => $match ? FanMvp::results($match)->first() : null,
                'ballots' => $match && $this->open === $key ? FanMvp::ballots($match) : collect(),
            ];
        });

        return view('livewire.admin.fan-mvp-votes', ['page' => $page, 'matches' => $matches]);
    }
}

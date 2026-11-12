<?php

namespace App\Livewire;

use App\Models\Player;
use App\Support\FanMvp;
use App\Support\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Kibicowskie MVP na stronie meczu: karty zawodników do głosowania, po zakończeniu karta zwycięzcy.
 * Gość nie dostaje niczego (ani głosowania, ani wyniku); prowadzący mecz widzi dodatkowo panel zarządzania.
 */
class FanMvpPanel extends Component
{
    #[Locked]
    public string $type;

    #[Locked]
    public int $id;

    /** Zawodnik, na którego właśnie oddano głos (do animacji potwierdzenia). */
    public ?int $justVoted = null;

    /** Zawodnik spoza składu wybrany do dopisania (panel prowadzącego). */
    public string $extraPlayerId = '';

    public function mount(string $type, int $id): void
    {
        $this->type = $type;
        $this->id = $id;
    }

    public function vote(int $playerId): void
    {
        $user = Auth::user();
        $match = $this->match();

        if (! $user || ! $match) {
            return;
        }

        if ($error = FanMvp::vote($user, $match, $playerId)) {
            $this->dispatch('toast', message: $error, type: 'error');

            return;
        }

        $this->justVoted = $playerId;
    }

    public function exclude(int $playerId): void
    {
        if (($match = $this->managed()) && ($player = Player::find($playerId))) {
            FanMvp::exclude($match, $player);
        }
    }

    public function restore(int $playerId): void
    {
        if (($match = $this->managed()) && ($player = Player::find($playerId))) {
            FanMvp::restore($match, $player);
        }
    }

    public function addPlayer(): void
    {
        if (($match = $this->managed()) && ($player = Player::where('is_active', true)->find((int) $this->extraPlayerId))) {
            FanMvp::addPlayer($match, $player);
        }

        $this->extraPlayerId = '';
    }

    public function toggleDisabled(): void
    {
        if ($match = $this->managed()) {
            FanMvp::setDisabled($match, ! FanMvp::poll($match)?->disabled);
        }
    }

    public function closeNow(): void
    {
        if (($match = $this->managed()) && FanMvp::state($match) === 'open') {
            FanMvp::closeNow($match);
        }
    }

    private function match(): ?Model
    {
        return FanMvp::find($this->type, $this->id);
    }

    private function managed(): ?Model
    {
        $match = $this->match();

        return $match && FanMvp::canManage(Auth::user(), $match) ? $match : null;
    }

    public function render(): View
    {
        $user = Auth::user();
        $match = $user && FanMvp::enabled() ? $this->match() : null;

        if (! $match) {
            return view('livewire.fan-mvp-panel', ['state' => 'hidden']);
        }

        $canManage = FanMvp::canManage($user, $match);
        $state = FanMvp::state($match);
        $poll = FanMvp::poll($match);

        if (! $canManage && in_array($state, ['off', 'before'], true)) {
            return view('livewire.fan-mvp-panel', ['state' => 'hidden']);
        }

        $site = app(SiteSettings::class);
        $blocked = FanMvp::blocked($user);
        $candidates = $state === 'open' ? FanMvp::candidates($match) : collect();
        $results = $state === 'closed' || $canManage ? FanMvp::results($match) : collect();

        return view('livewire.fan-mvp-panel', [
            'state' => $state,
            'title' => FanMvp::title(),
            'intro' => (string) $site['fan_mvp_intro'],
            'color' => $site->color('fan_mvp_color'),
            'ends' => FanMvp::endsAt($match),
            'starts' => FanMvp::startsAt($match),
            // Własnej karty (zawodnik) i kart swoich dzieci (rodzic) nie pokazujemy; pod listą zostaje wyjaśnienie.
            'candidates' => $candidates->reject(fn (Player $player) => isset($blocked[$player->id]))->values(),
            'blockedNotes' => $candidates->map(fn (Player $player) => $blocked[$player->id] ?? null)->filter()->unique()->values(),
            'votedFor' => $state === 'open' ? FanMvp::votedFor($user, $match) : null,
            'voteBlock' => FanMvp::voterBlock($user),
            'results' => $results,
            'winners' => $state === 'closed' ? $results->where('winner', true)->values() : collect(),
            'canManage' => $canManage,
            'roster' => $canManage ? FanMvp::roster($match) : collect(),
            'excluded' => $poll?->excludedIds() ?? [],
            'addable' => $canManage ? FanMvp::addable($match) : collect(),
            'disabled' => (bool) $poll?->disabled,
            'totalVotes' => $canManage ? (int) $results->sum('votes') : 0,
        ]);
    }
}

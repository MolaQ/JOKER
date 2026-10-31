<?php

namespace App\Livewire\Admin;

use App\AccountStatus;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\AuditLog;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Zakładka „Wpisujący wyniki” w panelu turnieju: konta, które w dniach turnieju (od dnia przed do dnia po)
 * mogą na telefonie wpisywać godzinę rozpoczęcia meczów i wyniki. Nic więcej w turnieju nie zmienią.
 */
class TournamentScorers extends Component
{
    use ShowsToasts;

    public int $tournamentId;

    public string $userId = '';

    public function mount(int $tournamentId): void
    {
        $this->tournamentId = $tournamentId;
    }

    public function updatedUserId(): void
    {
        $this->add();
    }

    public function add(): void
    {
        $user = User::find((int) $this->userId);
        $this->userId = '';

        if (! $user) {
            return;
        }

        $tournament = $this->tournament();
        $tournament->scorers()->syncWithoutDetaching([$user->id]);
        AuditLog::record('permissions.scorer', 'Wpisywanie wyników turnieju '.$tournament->fullName().': dodano '.$user->name, $user, ['dodane' => [$tournament->fullName()]]);
        $this->toast($user->name.' może wpisywać wyniki w dniach turnieju.');
    }

    public function remove(int $userId): void
    {
        $tournament = $this->tournament();
        $user = User::findOrFail($userId);
        $tournament->scorers()->detach($userId);
        AuditLog::record('permissions.scorer', 'Wpisywanie wyników turnieju '.$tournament->fullName().': odebrano '.$user->name, $user, ['odebrane' => [$tournament->fullName()]]);
        $this->toast('Odebrano wpisywanie wyników: '.$user->name.'.');
    }

    private function tournament(): Tournament
    {
        return Tournament::findOrFail($this->tournamentId);
    }

    public function render(): View
    {
        $tournament = $this->tournament();
        $scorers = $tournament->scorers()->orderBy('name')->get(['users.id', 'users.name', 'users.email']);

        return view('livewire.admin.tournament-scorers', [
            'tournament' => $tournament,
            'scorers' => $scorers,
            'options' => User::withAccountStatus(AccountStatus::Active)->whereNotIn('id', $scorers->pluck('id'))->orderBy('name')->get(['id', 'name', 'email'])
                ->map(fn (User $user) => ['value' => (string) $user->id, 'label' => $user->name.' ('.$user->email.')'])->all(),
            'from' => $tournament->start_date->copy()->subDay(),
            'until' => ($tournament->end_date ?? $tournament->start_date)->copy()->addDay(),
        ]);
    }
}

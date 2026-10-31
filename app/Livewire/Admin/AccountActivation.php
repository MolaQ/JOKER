<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\AuditLog;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Notifications\AccountActivated;
use App\Notifications\AccountRejected;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Konta do aktywacji: nowe konto działa dopiero po powiązaniu z profilem zawodnika
 * albo z drużyną (trener, sztab). Rodziców przypisuje się do dzieci w ParentAssignment.
 * Trener widzi tylko konta zgłoszone do swoich drużyn i aktywuje tylko rodziców i zawodników.
 */
class AccountActivation extends Component
{
    use ShowsToasts;

    public ?int $activatingId = null;

    public string $role = '';

    public ?int $playerId = null;

    /** @var list<int> */
    public array $childIds = [];

    public ?int $teamId = null;

    public string $function = '';

    public bool $headCoach = false;

    public string $playerSearch = '';

    public ?int $rejectingId = null;

    public string $rejectionReason = '';

    /**
     * Konta do aktywacji w tej zakładce. Rodzice mają własną stronę: „Rodzice do przypisania”.
     */
    public static function pendingFor(User $user): Builder
    {
        return self::awaitingFor($user)->where(fn (Builder $query) => $query
            ->whereNull('requested_role')
            ->orWhere('requested_role', '!=', UserRole::Parent->value));
    }

    /**
     * Konta, które jeszcze nigdy nie były aktywne, widoczne dla zalogowanej osoby.
     */
    public static function awaitingFor(User $user): Builder
    {
        $teamIds = $user->managedTeamIds();

        return User::query()
            ->where('is_active', false)
            ->whereNull('activated_at')
            ->whereNull('rejected_at')
            ->whereNull('disabled_at')
            ->whereNull('anonymized_at')
            ->when($teamIds !== null, fn ($query) => $query
                ->whereIn('requested_team_id', $teamIds)
                ->whereIn('requested_role', [UserRole::Parent->value, UserRole::Player->value]));
    }

    /**
     * @return list<UserRole>
     */
    public function rolesToGrant(): array
    {
        return Auth::user()->can('teams.all')
            ? [UserRole::Player, UserRole::Parent, UserRole::Trainer, UserRole::Staff, UserRole::Fan]
            : [UserRole::Player, UserRole::Parent];
    }

    public function start(int $id): void
    {
        $user = self::pendingFor(Auth::user())->findOrFail($id);
        $this->rejectingId = null;

        $this->resetErrorBag();
        $this->activatingId = $user->id;
        $this->role = in_array($user->requested_role, $this->rolesToGrant(), true) ? $user->requested_role->value : $this->rolesToGrant()[0]->value;
        $this->playerId = null;
        $this->childIds = [];
        $this->teamId = $user->requested_team_id;
        $this->function = '';
        $this->headCoach = false;
        $this->playerSearch = '';
    }

    public function cancel(): void
    {
        $this->activatingId = null;
    }

    public function activate(): void
    {
        $user = self::pendingFor(Auth::user())->findOrFail($this->activatingId);
        $players = Player::managedBy(Auth::user())->pluck('id')->all();
        $teams = Team::managedBy(Auth::user())->pluck('id')->all();

        $this->validate([
            'role' => ['required', Rule::in(array_map(fn (UserRole $role) => $role->value, $this->rolesToGrant()))],
            'playerId' => ['nullable', 'required_if:role,player', Rule::in($players)],
            'childIds' => ['array', 'required_if:role,parent'],
            'childIds.*' => [Rule::in($players)],
            'teamId' => ['nullable', 'required_if:role,trainer', 'required_if:role,staff', Rule::in($teams)],
            'function' => 'nullable|string|max:100',
        ], [
            'playerId.required_if' => 'Wybierz profil zawodnika.',
            'childIds.required_if' => 'Zaznacz co najmniej jedno dziecko.',
            'teamId.required_if' => 'Wybierz drużynę.',
        ]);

        $role = UserRole::from($this->role);
        $linked = match ($role) {
            UserRole::Player => $this->linkPlayer($user),
            UserRole::Parent => $this->linkChildren($user),
            UserRole::Trainer, UserRole::Staff => $this->linkTeam($user),
            default => 'kibic',
        };

        $user->activate($role);
        AuditLog::record('account.linked', 'Konto '.$user->name.' powiązano: '.$linked, $user);
        $user->notify(new AccountActivated);

        $this->activatingId = null;
        $this->toast('Konto '.$user->name.' jest aktywne.');
    }

    private function linkPlayer(User $user): string
    {
        $player = Player::findOrFail($this->playerId);
        $player->update(['user_id' => $user->id]);

        return 'profil zawodnika '.$player->full_name;
    }

    private function linkChildren(User $user): string
    {
        $user->children()->syncWithoutDetaching($this->childIds);

        return 'rodzic: '.Player::whereKey($this->childIds)->get()->map->full_name->join(', ');
    }

    private function linkTeam(User $user): string
    {
        $team = Team::findOrFail($this->teamId);

        if ($this->headCoach) {
            $team->update(['trainer_id' => $user->id]);
        } else {
            $team->assistantTrainers()->syncWithoutDetaching([$user->id => ['role' => trim($this->function) ?: null]]);
        }

        return ($this->headCoach ? 'trener główny' : (trim($this->function) ?: 'sztab')).' drużyny '.$team->name;
    }

    public function startRejecting(int $id): void
    {
        $this->activatingId = null;
        $this->rejectingId = self::pendingFor(Auth::user())->findOrFail($id)->id;
        $this->rejectionReason = '';
        $this->resetErrorBag();
    }

    public function cancelRejecting(): void
    {
        $this->rejectingId = null;
    }

    /**
     * Odrzucenie z powodem: konto zostaje, użytkownik dostaje e-mail i może poprawić dane.
     */
    public function reject(): void
    {
        $user = self::pendingFor(Auth::user())->findOrFail($this->rejectingId);
        $this->validate(['rejectionReason' => 'required|string|min:5|max:500'], [
            'rejectionReason.required' => 'Napisz powód: zobaczy go osoba, która zakładała konto.',
            'rejectionReason.min' => 'Napisz powód trochę dokładniej.',
        ]);

        $user->rejectActivation(trim($this->rejectionReason));
        $user->notify(new AccountRejected);

        $this->rejectingId = null;
        $this->toast('Prośba odrzucona. '.$user->name.' dostał(a) e-mail z powodem i może poprawić dane.');
    }

    /**
     * Konto założone przez robota albo dla żartu: usuwamy je całkiem.
     */
    public function deleteSpam(int $id): void
    {
        $user = self::pendingFor(Auth::user())->findOrFail($id);

        AuditLog::record('account.deleted', 'Usunięto konto (spam) '.$user->name.' ('.$user->email.')');
        $user->delete();
        $this->activatingId = null;
        $this->rejectingId = null;
        $this->toast('Konto usunięte.');
    }

    public function render()
    {
        $pending = self::pendingFor(Auth::user())->with('requestedTeam')->orderBy('created_at')->get();
        $active = $pending->firstWhere('id', $this->activatingId);
        $search = trim($this->playerSearch);

        $players = $active
            ? Player::managedBy(Auth::user())
                ->with('team')
                ->when($search !== '', fn ($query) => $query->where(fn ($names) => $names->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")))
                ->when($search === '' && $this->teamId, fn ($query) => $query->where('team_id', $this->teamId))
                ->orderBy('last_name')
                ->limit(40)
                ->get()
            : collect();

        return view('livewire.admin.account-activation', [
            'pending' => $pending,
            'players' => $players,
            'teams' => Team::managedBy(Auth::user())->orderBy('display_order')->get(['id', 'name']),
        ]);
    }
}

<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\AuditLog;
use App\Models\Player;
use App\Models\User;
use App\Notifications\AccountActivated;
use App\Notifications\AccountRejected;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * „Rodzice do przypisania”: nowe konta rodziców i aktywni rodzice bez dziecka.
 * Przy każdym koncie podpowiedź, o którym zawodniku pisał rodzic; jedno kliknięcie aktywuje konto i przypisuje dziecko.
 * Trener i koordynator widzą tylko rodziców zgłoszonych do swoich drużyn i tylko ich zawodników.
 */
class ParentAssignment extends Component
{
    use ShowsToasts;

    /**
     * Wybór „inne dziecko” przy każdym koncie, np. [12 => '345'].
     *
     * @var array<int, string|null>
     */
    public array $otherChild = [];

    /**
     * Konta przypisane w tej wizycie: zostają na liście, żeby dopisać kolejne dziecko.
     *
     * @var list<int>
     */
    #[Locked]
    public array $assignedIds = [];

    public ?int $rejectingId = null;

    public string $rejectionReason = '';

    /**
     * Nowe konta rodziców czekające na aktywację.
     */
    public static function pendingFor(User $user): Builder
    {
        return AccountActivation::awaitingFor($user)->where('requested_role', UserRole::Parent->value);
    }

    /**
     * Aktywne konta z rolą Rodzic, które nie mają jeszcze przypisanego dziecka.
     */
    public static function childlessFor(User $user): Builder
    {
        $teamIds = $user->managedTeamIds();

        return User::withRole(UserRole::Parent)
            ->where('is_active', true)
            ->doesntHave('children')
            ->when($teamIds !== null, fn (Builder $query) => $query->whereIn('requested_team_id', $teamIds));
    }

    public static function countFor(User $user): int
    {
        return self::pendingFor($user)->count() + self::childlessFor($user)->count();
    }

    /**
     * Zawodnicy, o których najpewniej pisał rodzic: nazwisko i imię z notatki, nazwisko rodzica, wybrana drużyna.
     * Gdy zgadza się też imię, zostają tylko takie trafienia („sure”); samo nazwisko to tylko podpowiedź do sprawdzenia.
     *
     * @param  Collection<int, Player>  $players
     * @return Collection<int, array{player: Player, sure: bool}>
     */
    public static function suggestions(User $parent, Collection $players, int $limit = 3): Collection
    {
        $note = self::words((string) $parent->registration_note);
        $surname = collect(self::words((string) $parent->name))->last();

        $matches = $players
            ->reject(fn (Player $player) => $parent->relationLoaded('children') && $parent->children->contains($player))
            ->map(function (Player $player) use ($note, $surname, $parent) {
                $last = self::words((string) $player->last_name);
                $first = self::words((string) $player->first_name);
                $lastMentioned = $last !== [] && collect($last)->every(fn (string $word) => self::mentions($note, $word));
                $firstMentioned = $first !== [] && collect($first)->every(fn (string $word) => self::mentions($note, $word));
                $score = ($lastMentioned ? 3 : 0) + ($firstMentioned ? 2 : 0)
                    + ($surname && collect($last)->contains(fn (string $word) => self::sameSurname($surname, $word)) ? 2 : 0);

                return [
                    'player' => $player,
                    'sure' => $firstMentioned && $score >= 4,
                    'score' => $score + ($score > 0 && $player->team_id === $parent->requested_team_id ? 1 : 0),
                ];
            })
            ->filter(fn (array $match) => $match['score'] >= 3);

        if ($matches->contains('sure', true)) {
            $matches = $matches->where('sure', true);
        }

        return $matches->sortByDesc('score')->take($limit)
            ->map(fn (array $match) => ['player' => $match['player'], 'sure' => $match['sure']])
            ->values();
    }

    /**
     * To samo nazwisko także w formie żeńskiej i męskiej: Kowalska i Kowalski.
     */
    private static function sameSurname(string $a, string $b): bool
    {
        return $a === $b || (strlen($a) >= 5 && strlen($a) === strlen($b) && substr($a, 0, -1) === substr($b, 0, -1));
    }

    /**
     * @return list<string>
     */
    private static function words(string $text): array
    {
        return array_values(array_filter(preg_split('/[^a-z]+/', Str::lower(Str::ascii($text))), fn (string $word) => strlen($word) > 1));
    }

    /**
     * Czy w słowach notatki jest to słowo, także odmienione („Kowalskiego”, „Jasia” nie, „Jana” tak).
     *
     * @param  list<string>  $note
     */
    private static function mentions(array $note, string $word): bool
    {
        $stem = strlen($word) >= 4 ? substr($word, 0, -1) : $word;

        return collect($note)->contains(fn (string $token) => $token === $word || (strlen($word) >= 3 && str_starts_with($token, $stem) && strlen($token) - strlen($word) <= 3));
    }

    /**
     * Jedno kliknięcie: aktywuje konto (jeśli czeka), nadaje rolę Rodzic i przypisuje dziecko.
     */
    public function assign(int $userId, ?int $playerId = null): void
    {
        $playerId ??= (int) ($this->otherChild[$userId] ?? 0);
        $parent = $this->findParent($userId);
        $player = Player::managedBy(Auth::user())->with('team')->find($playerId);

        if (! $player) {
            $this->toast('Wybierz zawodnika.', 'error');

            return;
        }

        $parent->children()->syncWithoutDetaching([$player->id]);
        $label = $player->full_name.($player->team ? ' ('.$player->team->name.')' : '');

        if (! $parent->is_active) {
            $parent->activate(UserRole::Parent);
            $parent->notify(new AccountActivated);
            AuditLog::record('account.linked', 'Konto '.$parent->name.' aktywowano i przypisano do: '.$label, $parent);
        } else {
            $parent->assignRole(UserRole::Parent);
            AuditLog::record('account.linked', 'Rodzica '.$parent->name.' przypisano do: '.$label, $parent);
        }

        $this->assignedIds = array_values(array_unique([...$this->assignedIds, $parent->id]));
        unset($this->otherChild[$userId]);
        $this->toast($parent->name.' jest teraz rodzicem: '.$label.'.');
    }

    public function unassign(int $userId, int $playerId): void
    {
        $parent = $this->findParent($userId);
        $player = Player::managedBy(Auth::user())->findOrFail($playerId);

        $parent->children()->detach($player->id);
        AuditLog::record('account.linked', 'Rodzicowi '.$parent->name.' odpięto dziecko: '.$player->full_name, $parent);
        $this->toast('Odpięto '.$player->full_name.'.');
    }

    public function startRejecting(int $id): void
    {
        $this->rejectingId = self::pendingFor(Auth::user())->findOrFail($id)->id;
        $this->rejectionReason = '';
        $this->resetErrorBag();
    }

    public function cancelRejecting(): void
    {
        $this->rejectingId = null;
    }

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

    public function deleteSpam(int $id): void
    {
        $user = self::pendingFor(Auth::user())->findOrFail($id);

        AuditLog::record('account.deleted', 'Usunięto konto (spam) '.$user->name.' ('.$user->email.')');
        $user->delete();
        $this->toast('Konto usunięte.');
    }

    /**
     * Rodzic z listy albo przypisany w tej wizycie (wtedy tylko z drużyn zalogowanej osoby).
     */
    private function findParent(int $id): User
    {
        $me = Auth::user();

        return self::pendingFor($me)->find($id)
            ?? self::childlessFor($me)->find($id)
            ?? (in_array($id, $this->assignedIds, true) ? User::withRole(UserRole::Parent)->find($id) : null)
            ?? throw (new ModelNotFoundException)->setModel(User::class, [$id]);
    }

    public function render()
    {
        $me = Auth::user();
        $players = Player::managedBy($me)->where('is_active', true)->with('team')->orderBy('last_name')->orderBy('first_name')->get();
        $relations = ['requestedTeam', 'children.team'];

        $pending = self::pendingFor($me)->with($relations)->orderBy('created_at')->get();
        $childless = self::childlessFor($me)->with($relations)->orderBy('name')->get();
        $assigned = User::whereKey($this->assignedIds)->with($relations)->get()
            ->reject(fn (User $user) => $pending->contains($user) || $childless->contains($user));

        return view('livewire.admin.parent-assignment', [
            'groups' => collect([
                'Nowe konta rodziców' => $pending,
                'Aktywni rodzice bez przypisanego dziecka' => $childless,
                'Przypisani przed chwilą' => $assigned,
            ])->filter->isNotEmpty(),
            'suggestions' => $pending->concat($childless)->concat($assigned)->mapWithKeys(fn (User $user) => [$user->id => self::suggestions($user, $players)]),
            'playerOptions' => $players->sortBy(fn (Player $player) => sprintf('%03d', $player->team?->display_order ?? 999).$player->team_id)->map(fn (Player $player) => [
                'value' => (string) $player->id,
                'label' => $player->full_name.($player->jersey_number ? ' #'.$player->jersey_number : ''),
                'group' => $player->team?->name ?? 'Bez drużyny',
            ])->values()->all(),
        ]);
    }
}

<?php

namespace App\Livewire\Admin;

use App\AccountStatus;
use App\Livewire\Concerns\EditsPermissionOverrides;
use App\Livewire\Concerns\FiltersList;
use App\Livewire\Concerns\ShowsToasts;
use App\Livewire\Concerns\SortsTable;
use App\Models\AccessSnapshot;
use App\Models\AuditLog;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Notifications\AccountActivated;
use App\Support\AccessHistory;
use App\Support\SafeMail;
use App\Support\Trash;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class UserManager extends Component
{
    use EditsPermissionOverrides, FiltersList, ShowsToasts, SortsTable, WithFileUploads, WithPagination;

    public bool $showModal = false;

    /** Zakładka okna edycji: dane konta albo uprawnienia. */
    public string $modalTab = 'dane';

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    /** @var list<string> */
    public array $selectedRoles = [];

    /**
     * Daty „ważne do” zaznaczonych ról, np. ['trainer' => '2026-12-31']. Puste = bezterminowo.
     *
     * @var array<string, string|null>
     */
    public array $roleExpiries = [];

    /**
     * Drużyny koordynowane przez konto z rolą Koordynator drużyny.
     *
     * @var list<int|string>
     */
    public array $coordinatedTeamIds = [];

    public bool $is_active = true;

    public ?string $birth_date = null;

    /** Jak pokazać urodziny w kalendarzu meczów: ukryte, sam torcik albo z wiekiem. */
    public string $birthday_display = 'age';

    /** Profil trenera i sztabu na stronie (/staff/…): media społecznościowe i opis „Sylwetka”. */
    /** @var array<string, string> */
    public array $social_links = [];

    public string $additional_info = '';

    /** Zdjęcie trenera w kadrze 4:5 jak u zawodników. */
    public $photo = null;

    public ?string $existingPhoto = null;

    public bool $removePhoto = false;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'rola', except: '')]
    public string $roleFilter = '';

    #[Url(as: 'stan', except: '')]
    public string $statusFilter = '';

    #[Url(as: 'logowanie', except: '')]
    public string $loginFilter = '';

    public const LOGIN_FILTERS = [
        'recent' => 'W ostatnim miesiącu',
        'stale' => 'Ponad pół roku temu',
        'never' => 'Nigdy',
    ];

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            'password' => $this->editingId ? 'nullable|string|min:8' : 'required|string|min:8',
            'is_active' => 'boolean',
            'selectedRoles' => 'array',
            'selectedRoles.*' => Rule::in(array_map(fn (UserRole $role) => $role->value, $this->assignableRoles())),
            'roleExpiries' => 'array',
            'roleExpiries.*' => 'nullable|date|after_or_equal:today',
            'coordinatedTeamIds' => ['array', Rule::requiredIf(in_array(UserRole::Coordinator->value, $this->selectedRoles, true))],
            'coordinatedTeamIds.*' => 'integer|exists:teams,id',
            'birth_date' => 'nullable|date|before:today',
            'birthday_display' => 'required|in:hidden,cake,age',
            'social_links' => 'array',
            'social_links.*' => ['nullable', 'string', 'max:500', 'regex:#^\s*https?://#i'],
            'additional_info' => 'nullable|string|max:2000',
            'photo' => 'nullable|image|max:4096|dimensions:ratio=4/5',
            'removePhoto' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return ['social_links.*.regex' => 'Adres profilu zacznij od https://'];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    /** Dziecko wybrane w edycji konta do przypisania (rodzic). */
    public string $childToAdd = '';

    /**
     * Przypisuje dziecko do edytowanego konta od razu (bez „Zapisz”). Czekające konto zaczyna działać jako rodzic.
     */
    public function addChild(): void
    {
        $user = User::findOrFail($this->editingId);
        $player = Player::managedBy(Auth::user())->find((int) $this->childToAdd);

        if (! $player || ! $this->mayEdit($user)) {
            $this->toast('Wybierz dziecko z listy.', 'error');

            return;
        }

        $user->children()->syncWithoutDetaching([$player->id]);

        if (! $user->is_active && ! $user->activated_at && ! $user->rejected_at && ! $user->disabled_at && ! $user->anonymized_at) {
            $user->activate(UserRole::Parent);
            SafeMail::notify($user, new AccountActivated);
            $this->is_active = true;
        }

        $user->assignRole(UserRole::Parent);
        $this->selectedRoles = $user->roles()->map->value->all();
        AuditLog::record('account.linked', 'Rodzica '.$user->name.' przypisano do: '.$player->full_name, $user);
        $this->childToAdd = '';
        $this->toast($user->name.' jest teraz rodzicem: '.$player->full_name.'.');
    }

    public function removeChild(int $playerId): void
    {
        $user = User::findOrFail($this->editingId);
        abort_unless($this->mayEdit($user), 403);

        $user->children()->detach($playerId);
        AuditLog::record('account.unlinked', 'Odpięto dziecko od rodzica '.$user->name, $user);
        $this->toast('Dziecko odpięte od konta.');
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $user = User::findOrFail($id);

        if (! $this->mayEdit($user)) {
            $this->toast('Konto administratora zmienia tylko administrator.', 'error');

            return;
        }

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->selectedRoles = $user->roles()->map->value->all();
        $this->roleExpiries = collect($user->roleExpiries())->map->toDateString()->all();
        $this->coordinatedTeamIds = $user->coordinatedTeams()->pluck('teams.id')->all();
        $this->is_active = $user->is_active;
        $this->birth_date = $user->birth_date?->format('Y-m-d');
        $this->birthday_display = $user->birthday_display?->value ?? 'age';
        $this->social_links = collect($user->socialProfiles())->mapWithKeys(fn (array $profile) => [$profile['key'] => $profile['url']])->all();
        $this->additional_info = (string) $user->additional_info;
        $this->existingPhoto = $user->avatar_path;
        $this->photo = null;
        $this->removePhoto = false;
        $this->childToAdd = '';

        $this->showModal = true;
    }

    /**
     * Role, które może nadać zalogowana osoba: rolę administratora nadaje tylko administrator.
     *
     * @return list<UserRole>
     */
    public function assignableRoles(): array
    {
        return array_values(array_filter(UserRole::cases(), fn (UserRole $role) => $role !== UserRole::Admin || Auth::user()->isAdmin()));
    }

    private function mayEdit(User $user): bool
    {
        return Auth::user()->isAdmin() || ! $user->isAdmin();
    }

    public function save(): void
    {
        $validated = $this->validate();
        $roles = $validated['selectedRoles'] ?? [];
        $expiries = $validated['roleExpiries'] ?? [];
        $coordinated = in_array(UserRole::Coordinator->value, $roles, true) ? array_map('intval', $validated['coordinatedTeamIds'] ?? []) : [];
        $active = $validated['is_active'];
        unset($validated['selectedRoles'], $validated['roleExpiries'], $validated['coordinatedTeamIds'], $validated['is_active']);

        if ($this->editingId === Auth::id() && ! $active) {
            $this->addError('is_active', 'Nie możesz wyłączyć własnego konta.');

            return;
        }

        if ($this->editingId === Auth::id() && Auth::user()->isAdmin() && ! in_array(UserRole::Admin->value, $roles, true)) {
            $this->addError('selectedRoles', 'Nie możesz odebrać sobie roli administratora.');

            return;
        }

        $photo = $this->photo;
        $removePhoto = (bool) ($validated['removePhoto'] ?? false);
        unset($validated['photo'], $validated['removePhoto']);

        $validated['social_links'] = array_filter(array_map(fn ($url) => trim((string) $url), $validated['social_links'] ?? [])) ?: null;
        // Stare pole Instagram (propozycje zmian w „Mój profil”) idzie za listą serwisów.
        $validated['instagram'] = $validated['social_links']['instagram'] ?? null;
        $validated['additional_info'] = filled(trim(strip_tags((string) ($validated['additional_info'] ?? '')))) ? $validated['additional_info'] : null;

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            abort_unless($this->mayEdit($user), 403);
            AccessHistory::remember($user, 'Przed zapisem w oknie konta');
            if ($photo || $removePhoto) {
                if ($user->avatar_path) {
                    Storage::disk('public')->delete($user->avatar_path);
                }
                $validated['avatar_path'] = $photo ? $photo->store('avatars', 'public') : null;
            }
            $user->update($validated);
            $user->syncRoles($roles);
            $this->saveRoleExpiries($user, $roles, $expiries);
            $this->saveCoordinatedTeams($user, $coordinated);
            $active ? $user->activate() : $user->deactivate('wyłączone w panelu');
            $this->toast('Użytkownik został zaktualizowany.');
        } else {
            $validated['email_verified_at'] = now();
            if ($photo) {
                $validated['avatar_path'] = $photo->store('avatars', 'public');
            }
            $user = User::create($validated);
            $user->syncRoles($roles);
            $this->saveRoleExpiries($user, $roles, $expiries);
            $this->saveCoordinatedTeams($user, $coordinated);

            if ($active) {
                $user->activate();
            }
            $this->toast('Użytkownik został dodany.');
        }

        $this->closeModal();
    }

    /**
     * @param  list<string>  $roles
     * @param  array<string, string|null>  $expiries
     */
    private function saveRoleExpiries(User $user, array $roles, array $expiries): void
    {
        foreach ($roles as $role) {
            $user->setRoleExpiry(UserRole::from($role), filled($expiries[$role] ?? null) ? Carbon::parse($expiries[$role]) : null);
        }
    }

    /**
     * @param  list<int>  $teamIds
     */
    private function saveCoordinatedTeams(User $user, array $teamIds): void
    {
        $changes = $user->coordinatedTeams()->sync($teamIds);

        if ($changes['attached'] || $changes['detached']) {
            $names = Team::pluck('name', 'id');
            AuditLog::record('roles.coordinator', 'Drużyny koordynatora '.$user->name, $user, [
                'dodane' => collect($changes['attached'])->map(fn ($id) => $names[$id] ?? '#'.$id)->values()->all(),
                'odebrane' => collect($changes['detached'])->map(fn ($id) => $names[$id] ?? '#'.$id)->values()->all(),
            ]);
        }
    }

    /**
     * „Przywróć ten stan” w historii uprawnień konta.
     */
    public function restoreAccess(int $snapshotId): void
    {
        $user = User::findOrFail($this->editingId);
        abort_unless($this->mayEdit($user) && $this->mayEditUser($user), 403);
        $snapshot = AccessSnapshot::where('user_id', $user->id)->findOrFail($snapshotId);

        if ($user->id === Auth::id() && $user->isAdmin() && ! array_key_exists(UserRole::Admin->value, $snapshot->data['roles'] ?? [])) {
            $this->toast('Nie możesz odebrać sobie roli administratora.', 'error');

            return;
        }

        AccessHistory::restore($snapshot);
        $this->openEditModal($user->id);
        $this->modalTab = 'uprawnienia';
        $this->toast('Przywrócono uprawnienia ze stanu z '.$snapshot->created_at->format('d.m.Y H:i').'.');
    }

    public function setOverride(string $permission, string $mode): void
    {
        $user = User::findOrFail($this->editingId);
        abort_unless($this->mayEdit($user), 403);
        $this->applyOverride($user, $permission, $mode);
    }

    public function setOverrideExpiry(string $permission, ?string $date): void
    {
        $user = User::findOrFail($this->editingId);
        abort_unless($this->mayEdit($user), 403);
        $this->applyOverrideExpiry($user, $permission, $date);
    }

    public function delete(int $id): void
    {
        if ($id === Auth::id()) {
            $this->toast('Nie możesz usunąć własnego konta.', 'error');

            return;
        }

        $user = User::findOrFail($id);

        if (! $this->mayEdit($user)) {
            $this->toast('Konto administratora usuwa tylko administrator.', 'error');

            return;
        }

        Trash::delete($user);
        $this->toast('Konto trafiło do kosza. Przywrócisz je w: System → Kosz.');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->modalTab = 'dane';
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->selectedRoles = [];
        $this->roleExpiries = [];
        $this->coordinatedTeamIds = [];
        $this->is_active = true;
        $this->birth_date = null;
        $this->birthday_display = 'age';
        $this->social_links = [];
        $this->additional_info = '';
        $this->photo = null;
        $this->existingPhoto = null;
        $this->removePhoto = false;
        $this->resetErrorBag();
    }

    protected function filterDefaults(): array
    {
        return ['search' => '', 'roleFilter' => '', 'statusFilter' => '', 'loginFilter' => ''];
    }

    protected function sortableColumns(): array
    {
        return [
            'nazwa' => 'name',
            'email' => 'email',
            // Konto zawodnika: liczymy też wyświetlenia i serduszka jego profilu zawodnika.
            'wyswietlenia' => fn ($q, $d) => $q->orderByRaw('users.views_count + coalesce((select sum(players.views_count) from players where players.user_id = users.id), 0) '.($d === 'desc' ? 'desc' : 'asc')),
            'polubienia' => fn ($q, $d) => $q->orderByRaw('(select count(*) from likes where likes.likeable_type = ? and likes.likeable_id = users.id)'
                .' + (select count(*) from likes join players on players.id = likes.likeable_id and likes.likeable_type = ? where players.user_id = users.id) '.($d === 'desc' ? 'desc' : 'asc'), [User::class, Player::class]),
            'logowanie' => 'last_login_at',
        ];
    }

    protected function sortDescendingFirst(): array
    {
        return ['wyswietlenia', 'polubienia', 'logowanie'];
    }

    public function render()
    {
        $term = trim($this->search);
        $users = $this->applySort(User::when($term !== '', fn ($q) => $q->where(fn ($q2) => $q2
            ->where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhereHas('playerProfile', fn (Builder $player) => $player->where('last_name', 'like', "%{$term}%")->orWhere('first_name', 'like', "%{$term}%"))
            ->orWhereHas('children', fn (Builder $child) => $child->where('last_name', 'like', "%{$term}%")->orWhere('first_name', 'like', "%{$term}%"))
        ))
            ->when($this->roleFilter === 'none', fn ($q) => $q->doesntHave('roleAssignments'))
            ->when(UserRole::tryFrom($this->roleFilter), fn ($q, UserRole $role) => $q->withRole($role))
            ->when(AccountStatus::tryFrom($this->statusFilter), fn ($q, AccountStatus $status) => $q->withAccountStatus($status))
            ->when($this->loginFilter === 'recent', fn (Builder $q) => $q->where('last_login_at', '>=', now()->subMonth()))
            ->when($this->loginFilter === 'stale', fn (Builder $q) => $q->where('last_login_at', '<', now()->subMonths(6)))
            ->when($this->loginFilter === 'never', fn (Builder $q) => $q->whereNull('last_login_at'))
            ->tap(fn (Builder $q) => $this->applyDateFilter($q, 'created_at'))
            ->with(['roleAssignments', 'activatedBy:id,name', 'children' => fn ($q) => $q->with(['team:id,name', 'miniGroup:id,name']), 'playerProfile' => fn ($q) => $q->select(['id', 'user_id', 'views_count'])->withCount('likes')])->withCount('receivedLikes')
            ->orderBy('name')
        )->paginate(15);

        $editing = $this->showModal && $this->editingId ? User::with(['children.team', 'children.miniGroup'])->find($this->editingId) : null;
        $showChildren = $editing && ($editing->children->isNotEmpty() || in_array(UserRole::Parent->value, $this->selectedRoles, true));

        return view('livewire.admin.user-manager', $this->overrideViewData($editing) + [
            'editingUser' => $editing,
            'childOptions' => $showChildren ? Player::managedBy(Auth::user())->where('is_active', true)->whereNotIn('id', $editing->children->pluck('id'))
                ->with(['team', 'miniGroup'])->orderBy('last_name')->orderBy('first_name')->get()
                ->map(fn (Player $player) => ['value' => (string) $player->id, 'label' => $player->full_name.($player->jersey_number ? ' #'.$player->jersey_number : ''), 'group' => ParentAssignment::groupLabel($player) ?? 'Bez drużyny'])
                ->sortBy('group')->values()->all() : [],
            'showChildren' => $showChildren,
            'users' => $users,
            'roleOptions' => UserRole::cases(),
            'statusOptions' => AccountStatus::cases(),
            'assignableRoles' => $this->assignableRoles(),
            'teams' => in_array(UserRole::Coordinator->value, $this->selectedRoles, true) ? Team::orderBy('display_order')->get(['id', 'name']) : collect(),
        ]);
    }
}

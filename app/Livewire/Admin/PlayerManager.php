<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Livewire\Concerns\SortsTable;
use App\Models\MiniGroup;
use App\Models\Player;
use App\Models\PlayerAchievement;
use App\Models\PlayerAward;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\PlayerPosition;
use App\Support\ClubAccounts;
use App\Support\PlayerMerge;
use App\Support\SiteSettings;
use App\Support\Trash;
use App\UserRole;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class PlayerManager extends Component
{
    private const MINI_PREFIX = 'mini-';

    use ShowsToasts, SortsTable, WithFileUploads, WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $first_name = '';

    public string $last_name = '';

    public ?int $jersey_number = null;

    public string $position = '';

    public bool $is_captain = false;

    public string $interests = '';

    /** @var array<string, string> Linki do mediów społecznościowych, klucz = serwis z ustawień */
    public array $social_links = [];

    /** E-mail, na który klub zakłada konto zawodnika (gdy nie wybrano istniejącego). */
    public string $account_email = '';

    /** @var array<int, array{email: string, name: string}> Rodzice dopisywani po e-mailu (konto zakłada się samo) */
    public array $new_guardians = [];

    /** @var array<int, string> Dodatkowe pozycje (np. libero i przyjmujący) */
    public array $extra_positions = [];

    public ?string $birth_date = null;

    public ?int $height = null;

    public ?int $weight = null;

    public ?int $reach = null;

    public ?int $spike_reach = null;

    public ?int $block_reach = null;

    public string $birthday_display = 'age';

    /** Konto zawodnika (rola „Zawodnik”) - może proponować zmiany profilu i zdjęcie. */
    public ?int $user_id = null;

    /** @var array<int, int> Rodzice powiązani z zawodnikiem (mogą proponować zmiany) */
    public array $guardian_ids = [];

    /** @var array<int, array{season: string, title: string}> Osiągnięcia pokazywane w profilu */
    public array $achievements = [];

    /** @var list<array{id: ?int, tournament: bool, name: string, event: string, date: string, place: string, note: string}> Nagrody indywidualne w kolejności z profilu. */
    public array $awards = [];

    public string $bio = '';

    public ?int $team_id = null;

    /** Grupa minisiatkówki (dziecko bez drużyny); wybierana na tej samej liście co drużyna. */
    public ?int $mini_group_id = null;

    /** Wybór z listy „Drużyna”: identyfikator drużyny albo „mini-ID” grupy minisiatkówki. */
    public string $group = '';

    /** @var array<int, int> Dodatkowe drużyny w bieżącym sezonie (gra w kilku drużynach) */
    public array $extra_team_ids = [];

    /** @var array<int|string, int|string|null> Numer na koszulce w dodatkowej drużynie, np. [5 => 10]. Puste = bez numeru. */
    public array $extra_team_numbers = [];

    public bool $is_active = true;

    public bool $image_consent = true;

    public $photo = null;

    public ?string $existingPhoto = null;

    public string $search = '';

    public string $teamFilter = '';

    /** Filtr pozycji, np. sami atakujący; „brak” = bez pozycji. */
    public string $positionFilter = '';

    /**
     * Inni zawodnicy tej drużyny z tym samym numerem (ostrzeżenie, nie blokada).
     *
     * @return list<string>
     */
    public function numberTakenBy(): array
    {
        if (! $this->team_id || $this->jersey_number === null) {
            return [];
        }

        return Player::where('team_id', $this->team_id)->where('jersey_number', $this->jersey_number)
            ->when($this->editingId, fn ($query) => $query->whereKeyNot($this->editingId))
            ->get()->map->full_name->all();
    }

    protected function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'jersey_number' => array_filter(['nullable', 'integer', 'min:0', 'max:99', $this->team_id ? Rule::unique('players', 'jersey_number')->where('team_id', $this->team_id)->ignore($this->editingId) : null]),
            'position' => 'nullable|in:setter,outside_hitter,opposite,middle_blocker,libero',
            'is_captain' => 'boolean',
            'interests' => 'nullable|string|max:500',
            'social_links' => 'array',
            'social_links.*' => ['nullable', 'string', 'max:500', 'regex:#^https?://#i'],
            'account_email' => ['nullable', 'email', 'max:255'],
            'new_guardians' => 'array|max:6',
            'new_guardians.*.email' => 'nullable|email|max:255',
            'new_guardians.*.name' => 'nullable|string|max:255',
            'extra_positions' => 'array',
            'extra_positions.*' => 'in:setter,outside_hitter,opposite,middle_blocker,libero',
            'birth_date' => 'nullable|date',
            'height' => 'nullable|integer|min:100|max:250',
            'weight' => 'nullable|integer|min:30|max:150',
            'reach' => 'nullable|integer|min:150|max:400',
            'spike_reach' => 'nullable|integer|min:150|max:400',
            'block_reach' => 'nullable|integer|min:150|max:400',
            'birthday_display' => 'required|in:hidden,cake,age',
            'user_id' => 'nullable|exists:users,id',
            'guardian_ids' => 'array',
            'guardian_ids.*' => 'exists:users,id',
            'achievements' => 'array',
            'achievements.*.season' => 'nullable|string|max:20',
            'achievements.*.title' => 'nullable|string|max:255',
            'awards' => 'array',
            'awards.*.name' => 'nullable|string|max:150',
            'awards.*.event' => 'nullable|string|max:200',
            'awards.*.date' => 'nullable|date',
            'awards.*.place' => 'nullable|string|max:120',
            'awards.*.note' => 'nullable|string|max:200',
            'bio' => 'nullable|string',
            // Bez drużyny zapisze tylko ktoś, kto widzi wszystkie drużyny (trener straciłby dostęp do zawodnika).
            // Dziecko z minisiatkówki może zostać bez drużyny (jest w grupie mini).
            'team_id' => [Auth::user()->managedTeamIds() === null || $this->mini_group_id ? 'nullable' : 'required', Rule::in(Team::managedBy(Auth::user())->pluck('id'))],
            'mini_group_id' => ['nullable', Rule::in($this->miniGroupOptions()->pluck('id'))],
            'extra_team_ids' => 'array',
            'extra_team_ids.*' => 'exists:teams,id',
            'extra_team_numbers' => 'array',
            'extra_team_numbers.*' => 'nullable|integer|min:0|max:99',
            'is_active' => 'boolean',
            'image_consent' => 'boolean',
            'photo' => 'nullable|image|max:4096|dimensions:ratio=4/5',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return ['jersey_number' => 'numer', 'account_email' => 'e-mail zawodnika', 'social_links.*' => 'adres profilu', 'new_guardians.*.email' => 'e-mail rodzica', 'team_id' => 'drużyna'];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'social_links.*.regex' => 'Adres profilu zacznij od https://',
            'jersey_number.unique' => 'Ten numer ma już inny zawodnik tej drużyny. Zmień numer albo najpierw popraw tamtego zawodnika.',
        ];
    }

    public function addGuardian(): void
    {
        $this->new_guardians[] = ['email' => '', 'name' => ''];
    }

    public function removeGuardian(int $index): void
    {
        unset($this->new_guardians[$index]);
        $this->new_guardians = array_values($this->new_guardians);
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTeamFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPositionFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $player = Player::managedBy(Auth::user())->findOrFail($id);

        $this->editingId = $player->id;
        $this->first_name = $player->first_name;
        $this->last_name = $player->last_name;
        $this->jersey_number = $player->jersey_number;
        $this->position = $player->position?->value ?? '';
        $this->is_captain = $player->isCaptain();
        $this->interests = (string) $player->interests;
        $this->social_links = array_map('strval', (array) ($player->social_links ?? []));
        $this->extra_positions = $player->extra_positions ?? [];
        $this->birth_date = $player->birth_date?->format('Y-m-d');
        $this->height = $player->height;
        $this->weight = $player->weight;
        $this->reach = $player->reach;
        $this->spike_reach = $player->spike_reach;
        $this->block_reach = $player->block_reach;
        $this->birthday_display = $player->birthday_display?->value ?? 'age';
        $this->user_id = $player->user_id;
        $this->guardian_ids = $player->guardians()->pluck('users.id')->all();
        $this->achievements = $player->achievements->whereNull('tournament_award_id')->map(fn (PlayerAchievement $achievement) => ['season' => (string) $achievement->season, 'title' => $achievement->title])->all();
        $this->awards = $player->awards->map(fn (PlayerAward $award) => [
            'id' => $award->id,
            'tournament' => $award->tournament_award_id !== null,
            'name' => $award->name,
            'event' => (string) $award->event,
            'date' => (string) $award->awarded_on?->toDateString(),
            'place' => (string) $award->place,
            'note' => (string) $award->note,
        ])->values()->all();
        $this->bio = (string) $player->bio;
        $this->team_id = $player->team_id;
        $this->mini_group_id = $player->team_id ? null : $player->mini_group_id;
        $this->group = $this->team_id ? (string) $this->team_id : ($this->mini_group_id ? self::MINI_PREFIX.$this->mini_group_id : '');
        $this->is_active = $player->is_active;
        $this->image_consent = $player->image_consent;
        $this->existingPhoto = $player->photo_path;
        $this->photo = null;

        $currentSeasonId = Season::current()->value('id');
        $extraTeams = $currentSeasonId
            ? $player->teamsForSeason($currentSeasonId)->get()->reject(fn ($team) => $team->id === $player->team_id)->values()
            : collect();
        $this->extra_team_ids = $extraTeams->pluck('id')->all();
        $this->extra_team_numbers = $extraTeams->mapWithKeys(fn ($team) => [$team->id => $team->pivot->jersey_number])->all();

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();
        $extraTeamIds = $validated['extra_team_ids'] ?? [];
        $extraNumbers = $validated['extra_team_numbers'] ?? [];
        $guardianIds = $validated['guardian_ids'] ?? [];
        $captain = $validated['is_captain'];
        $accountEmail = trim($validated['account_email'] ?? '');
        $newGuardians = array_filter($validated['new_guardians'] ?? [], fn (array $row) => trim($row['email'] ?? '') !== '');
        unset($validated['extra_team_numbers'], $validated['extra_team_ids'], $validated['guardian_ids'], $validated['achievements'], $validated['awards'], $validated['photo'], $validated['is_captain'], $validated['account_email'], $validated['new_guardians']);
        $validated['position'] = $validated['position'] ?: null;
        $validated['interests'] = trim($validated['interests'] ?? '') ?: null;
        $validated['social_links'] = array_filter(array_map(fn ($url) => trim((string) $url), $validated['social_links'] ?? [])) ?: null;
        $validated['extra_positions'] = array_values(array_diff($this->extra_positions, [$this->position])) ?: null;

        if ($this->photo) {
            $validated['photo_path'] = $this->photo->store('players', 'public');
        }

        if ($this->editingId) {
            $player = Player::managedBy(Auth::user())->findOrFail($this->editingId);
            $player->update($validated);
            $this->toast('Zawodnik został zaktualizowany.');
        } else {
            $player = Player::create($validated);
            $this->toast('Zawodnik został dodany.');
        }

        $player->guardians()->sync($guardianIds);

        if ($accountEmail !== '' && ! $player->user_id) {
            ClubAccounts::connectPlayer($player, $accountEmail);
            $this->toast('Zawodnik ma konto: '.$accountEmail.'. Wysłaliśmy mu link do ustawienia hasła.');
        }

        foreach ($newGuardians as $row) {
            ClubAccounts::connectGuardian($player, $row['email'], trim($row['name'] ?? '') ?: null);
        }
        $player->achievements()->whereNull('tournament_award_id')->delete();

        foreach (array_values(array_filter($this->achievements, fn (array $row) => trim($row['title'] ?? '') !== '')) as $order => $row) {
            $player->achievements()->create([
                'season' => trim($row['season'] ?? '') ?: null,
                'title' => trim($row['title']),
                'display_order' => $order,
            ]);
        }

        // Nagrody: poprawione zostają (także kopie z turniejów), usunięte z listy znikają, kolejność jak na liście.
        $kept = [];

        foreach (array_values(array_filter($this->awards, fn (array $row) => trim($row['name'] ?? '') !== '')) as $order => $row) {
            $values = [
                'name' => trim($row['name']),
                'event' => trim($row['event'] ?? '') ?: null,
                'awarded_on' => ($row['date'] ?? '') ?: null,
                'place' => trim($row['place'] ?? '') ?: null,
                'note' => trim($row['note'] ?? '') ?: null,
                'display_order' => $order,
            ];
            $award = filled($row['id'] ?? null) ? $player->awards()->find((int) $row['id']) : null;
            $award ? $award->update($values) : $award = $player->awards()->create($values);
            $kept[] = $award->id;
        }

        $player->awards()->whereNotIn('id', $kept)->get()->each->delete();

        $currentSeasonId = Season::current()->value('id');

        if ($currentSeasonId) {
            // Zawsze zapewnij wpis w macierzystej drużynie na bieżący sezon.
            $player->teams()->wherePivot('season_id', $currentSeasonId)->detach();

            if ($player->team_id) {
                $player->teams()->attach($player->team_id, [
                    'season_id' => $currentSeasonId,
                    'jersey_number' => $player->jersey_number,
                    'is_captain' => $captain,
                ]);
            }

            foreach ($extraTeamIds as $extraTeamId) {
                if ((int) $extraTeamId === (int) $player->team_id) {
                    continue;
                }

                $number = $extraNumbers[$extraTeamId] ?? null;
                $player->teams()->attach($extraTeamId, [
                    'season_id' => $currentSeasonId,
                    'jersey_number' => $number === null || $number === '' ? null : (int) $number,
                    'is_captain' => false,
                ]);
            }
        }

        $this->closeModal();
    }

    public function addAward(): void
    {
        $this->awards[] = ['id' => null, 'tournament' => false, 'name' => '', 'event' => '', 'date' => '', 'place' => '', 'note' => ''];
    }

    public function moveAward(int $index, int $direction): void
    {
        $target = $index + $direction;

        if (! isset($this->awards[$index], $this->awards[$target])) {
            return;
        }

        [$this->awards[$index], $this->awards[$target]] = [$this->awards[$target], $this->awards[$index]];
    }

    public function removeAward(int $index): void
    {
        unset($this->awards[$index]);
        $this->awards = array_values($this->awards);
    }

    public function addAchievement(): void
    {
        $this->achievements[] = ['season' => '', 'title' => ''];
    }

    public function removeAchievement(int $index): void
    {
        unset($this->achievements[$index]);
        $this->achievements = array_values($this->achievements);
    }

    public function delete(int $id): void
    {
        Trash::delete(Player::managedBy(Auth::user())->findOrFail($id));
        $this->toast('Zawodnik trafił do kosza. Przywrócisz go w: System → Kosz.');
    }

    /**
     * Ta sama osoba wpisana dwa razy: duplikat przechodzi do edytowanego zawodnika (zob. PlayerMerge).
     */
    public function mergeDuplicate(int $duplicateId): void
    {
        $keep = Player::managedBy(Auth::user())->findOrFail((int) $this->editingId);
        $duplicate = Player::managedBy(Auth::user())->findOrFail($duplicateId);
        abort_unless(PlayerMerge::candidates($keep)->contains('id', $duplicate->id), 403);

        PlayerMerge::merge($keep, $duplicate);
        $this->openEditModal($keep->id);
        $this->toast('Scalono: '.$keep->full_name.' ma teraz jeden profil (drugi wpis jest w koszu).');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->first_name = '';
        $this->last_name = '';
        $this->jersey_number = null;
        $this->position = '';
        $this->is_captain = false;
        $this->interests = '';
        $this->social_links = [];
        $this->account_email = '';
        $this->new_guardians = [];
        $this->extra_positions = [];
        $this->birth_date = null;
        $this->height = null;
        $this->weight = null;
        $this->reach = null;
        $this->spike_reach = null;
        $this->block_reach = null;
        $this->birthday_display = 'age';
        $this->user_id = null;
        $this->guardian_ids = [];
        $this->achievements = [];
        $this->awards = [];
        $this->bio = '';
        $this->team_id = null;
        $this->mini_group_id = null;
        $this->group = '';
        $this->extra_team_ids = [];
        $this->extra_team_numbers = [];
        $this->is_active = true;
        $this->image_consent = true;
        $this->photo = null;
        $this->existingPhoto = null;
        $this->resetErrorBag();
    }

    protected function sortableColumns(): array
    {
        return [
            'numer' => 'jersey_number',
            'nazwisko' => fn ($q, $d) => $q->orderBy('last_name', $d)->orderBy('first_name', $d),
            'pozycja' => 'position',
            'druzyna' => fn ($q, $d) => $q->orderBy(Team::select('display_order')->whereColumn('teams.id', 'players.team_id'), $d),
            'status' => 'is_active',
            'wyswietlenia' => 'views_count',
            'polubienia' => 'likes_count',
            'logowanie' => fn ($q, $d) => $q->orderBy(User::select('last_login_at')->whereColumn('users.id', 'players.user_id'), $d),
        ];
    }

    protected function sortDescendingFirst(): array
    {
        return ['wyswietlenia', 'polubienia', 'logowanie'];
    }

    /**
     * Lista „Drużyna” to drużyny i (pod nimi) grupy minisiatkówki; wybór ustawia drużynę albo grupę mini.
     */
    public function updatedGroup(string $value): void
    {
        $this->team_id = ctype_digit($value) ? (int) $value : null;
        $this->mini_group_id = str_starts_with($value, self::MINI_PREFIX) ? (int) substr($value, strlen(self::MINI_PREFIX)) : null;
        $this->resetValidation(['team_id', 'mini_group_id']);

        // Nowe dziecko z minisiatkówki: wizerunek dopiero po zgodzie rodzica.
        if ($this->mini_group_id && ! $this->editingId) {
            $this->image_consent = false;
        }
    }

    /**
     * Grupy minisiatkówki do wyboru: wszystkie aktywne dla zarządu, dla trenera jego grupy;
     * zawsze też grupa edytowanego dziecka, żeby zapis jej nie zgubił.
     *
     * @return Collection<int, MiniGroup>
     */
    private function miniGroupOptions(): Collection
    {
        $user = Auth::user();
        $groups = app(SiteSettings::class)['module_mini']
            ? ($user->managedTeamIds() === null ? MiniGroup::where('is_active', true)->ordered()->get() : $user->miniGroups()->ordered()->get())
            : collect();

        if ($this->editingId && ($current = Player::find($this->editingId)?->mini_group_id) && ! $groups->contains('id', $current)) {
            $groups->push(MiniGroup::find($current));
        }

        return $groups->filter()->values();
    }

    public function render()
    {
        $search = trim($this->search);
        $base = Player::managedBy(Auth::user())
            ->when($search !== '', fn ($q) => $q->where(fn ($q2) => $q2
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhereRaw((DB::getDriverName() === 'sqlite' ? "first_name || ' ' || last_name" : "CONCAT(first_name, ' ', last_name)").' like ?', ["%{$search}%"])
                ->when(preg_match('/^#?(\d{1,2})$/', $search, $number), fn ($q3) => $q3->orWhere('jersey_number', (int) $number[1]))
            ));

        // Drużyna to drużyna macierzysta albo skład w bieżącym sezonie (np. junior grający też w seniorach).
        $seasonId = Season::current()->value('id');

        $players = $this->applySort((clone $base)->with(['team', 'miniGroup', 'user:id,last_login_at', 'teams' => fn ($q) => $q->where('team_player_season.season_id', $seasonId)])->withCount('likes')
            ->when($this->teamFilter === 'brak', fn ($q) => $q->whereNull('team_id')->whereNull('mini_group_id'))
            ->when($this->teamFilter === 'mini', fn ($q) => $q->mini())
            ->when(ctype_digit($this->teamFilter), fn ($q) => $q->inTeam($this->teamFilter, $seasonId))
            ->when($this->positionFilter === 'brak', fn ($q) => $q->whereNull('position'))
            ->when(PlayerPosition::tryFrom($this->positionFilter), fn ($q, PlayerPosition $position) => $q->where(fn ($q2) => $q2->where('position', $position)->orWhereJsonContains('extra_positions', $position->value)))
            ->orderBy('team_id')
            ->orderBy('jersey_number')
        )->paginate(12);

        $teams = Team::managedBy(Auth::user())->orderBy('display_order')->get();
        $positions = PlayerPosition::cases();

        return view('livewire.admin.player-manager', [
            'players' => $players,
            // Liczniki przy przyciskach filtrów (z uwzględnieniem wyszukiwania).
            'teamCounts' => $teams->mapWithKeys(fn (Team $team) => [$team->id => (clone $base)->inTeam($team->id, $seasonId)->count()])
                ->put('', (clone $base)->whereNull('team_id')->whereNull('mini_group_id')->count())
                ->put('mini', (clone $base)->mini()->count()),
            'totalCount' => (clone $base)->count(),
            'positionCounts' => (clone $base)->selectRaw('position, count(*) as total')->groupBy('position')->pluck('total', 'position'),
            'duplicateNumbers' => Player::whereNotNull('team_id')->whereNotNull('jersey_number')->selectRaw('team_id, jersey_number')->groupBy('team_id', 'jersey_number')->havingRaw('count(*) > 1')->get()->map(fn ($row) => $row->team_id.'-'.$row->jersey_number)->all(),
            // Ta sama osoba wpisana dwa razy (to samo imię i nazwisko): podpowiedź do scalenia.
            'duplicatePeople' => Player::get(['id', 'first_name', 'last_name'])
                ->groupBy(fn (Player $player) => mb_strtolower(trim($player->first_name).' '.trim($player->last_name)))
                ->filter(fn ($group) => $group->count() > 1)->flatten()->pluck('id')->all(),
            'teams' => $teams,
            'positions' => $positions,
            'miniGroups' => $this->showModal ? $this->miniGroupOptions() : collect(),
            'playerAccounts' => $this->showModal ? User::withRole(UserRole::Player)->orderBy('name')->get(['id', 'name', 'email']) : collect(),
            'socialPlatforms' => app(SiteSettings::class)->playerSocialPlatforms(),
            'linkedAccount' => $this->showModal && $this->user_id ? User::find($this->user_id) : null,
            'parentAccounts' => $this->showModal ? User::withRole(UserRole::Parent)->orderBy('name')->get(['id', 'name', 'email']) : collect(),
        ]);
    }
}

<?php

namespace App\Livewire\Admin;

use App\AccountStatus;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\MiniGroup;
use App\Models\MiniTrip;
use App\Models\MiniTripTeam;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\User;
use App\Support\AgeColors;
use App\Support\MiniRules;
use App\Support\SiteSettings;
use App\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Minisiatkówka w panelu: wyjazdy z drużynami, składami i wynikami (wygodne na telefonie), dzieci i grupy.
 */
class MiniVolleyballManager extends Component
{
    use ShowsToasts, WithFileUploads;

    #[Url(as: 'section')]
    public string $section = 'wyjazdy';

    /** Otwarta grupa (zakładka z nazwą grupy): strona grupy i baza dzieci tej grupy. */
    #[Url(as: 'group')]
    public ?int $groupTab = null;

    /** W zakładce grupy: „strona” (treść) albo „dzieci” (baza dzieci). */
    #[Url(as: 'pane')]
    public string $groupPane = 'strona';

    #[Url(as: 'trip')]
    public ?int $tripId = null;

    /** @var array<string, mixed> */
    public array $trip = [];

    public bool $showTripForm = false;

    /** @var array{group: string, name: string} */
    public array $newTeam = ['group' => '', 'name' => ''];

    /** @var array<int, array{place: string, teams_count: string, note: string}> */
    public array $results = [];

    public ?int $photoTeamId = null;

    public $teamPhoto = null;

    /** @var array<string, mixed> */
    public array $player = [];

    public ?int $editingPlayerId = null;

    public $playerPhoto = null;

    public string $playerGroup = '';

    /** @var array<string, mixed> */
    public array $group = [];

    public ?int $editingGroupId = null;

    /** Zmienia się po wstawieniu propozycji zasad, żeby edytor pokazał nową treść. */
    public int $rulesVersion = 0;

    /**
     * Wstawia propozycję zasad gry (Kinder Joy of Moving / PZPS) dla liczby zawodników na boisku.
     */
    public function insertRulesTemplate(): void
    {
        $players = (int) ($this->group['players_on_court'] ?? 0);
        abort_unless(MiniRules::has($players), 422);

        $this->group['rules'] = MiniRules::template($players);
        $this->rulesVersion++;
        $this->toast(__('Wstawiono propozycję zasad. Sprawdź ją i zapisz grupę.'));
    }

    public $groupPhoto = null;

    public function mount(): void
    {
        $this->resetTrip();
        $this->resetPlayer();
        $this->resetGroup();
        $this->loadResults();

        if ($this->section === 'grupa' && $this->groupTab && MiniGroup::whereKey($this->groupTab)->exists()) {
            $this->openGroup($this->groupTab, $this->groupPane);
        } elseif ($this->section === 'grupa') {
            $this->section = 'grupy';
        }
    }

    /**
     * Zakładka grupy: formularz strony grupy i lista dzieci tej grupy.
     */
    public function openGroup(int $id, string $pane = 'strona'): void
    {
        $this->section = 'grupa';
        $this->groupTab = $id;
        $this->groupPane = in_array($pane, ['strona', 'dzieci', 'kadra'], true) ? $pane : 'strona';
        $this->editGroup($id);
        $this->playerGroup = (string) $id;
        $this->resetPlayer($id);
    }

    /** Osoba dopisywana do kadry grupy (id konta). */
    public string $staffToAdd = '';

    /**
     * Konta, które mogą prowadzić grupę: trenerzy mini, koordynatorzy mini, trenerzy i sztab.
     *
     * @return Collection<int, User>
     */
    private static function staffCandidates()
    {
        return User::withAccountStatus(AccountStatus::Active)
            ->withRole(UserRole::MiniCoordinator, UserRole::MiniTrainer, UserRole::Trainer, UserRole::Staff)
            ->orderBy('name')->get();
    }

    public function addGroupStaff(): void
    {
        $group = MiniGroup::findOrFail($this->groupTab);
        $user = self::staffCandidates()->firstWhere('id', (int) $this->staffToAdd);
        abort_unless($user, 422);

        $group->trainers()->syncWithoutDetaching([$user->id]);
        $this->staffToAdd = '';
        $this->toast(__(':name prowadzi teraz grupę :group.', ['name' => $user->name, 'group' => $group->name]));
    }

    public function removeGroupStaff(int $userId): void
    {
        $group = MiniGroup::findOrFail($this->groupTab);
        $group->trainers()->detach($userId);
        $this->toast(__('Usunięto z grupy :group.', ['group' => $group->name]));
    }

    public function toggleGroupVisible(int $id): void
    {
        $group = MiniGroup::findOrFail($id);
        $group->update(['is_active' => ! $group->is_active]);
        $this->group['is_active'] = $this->editingGroupId === $group->id ? $group->is_active : ($this->group['is_active'] ?? true);
        $this->toast($group->is_active ? 'Grupa jest widoczna na stronie (także kafel na stronie głównej).' : 'Grupa ukryta na stronie.');
    }

    // ── Wyjazdy ──────────────────────────────────────────────

    public function newTrip(): void
    {
        $this->resetTrip();
        $this->tripId = null;
        $this->showTripForm = true;
    }

    public function openTrip(int $id): void
    {
        $trip = MiniTrip::findOrFail($id);
        $this->tripId = $trip->id;
        $this->trip = [
            'title' => $trip->title,
            'starts_on' => $trip->starts_on->toDateString(),
            'ends_on' => (string) $trip->ends_on?->toDateString(),
            'city' => (string) $trip->city,
            'venue' => (string) $trip->venue,
            'organizer' => (string) $trip->organizer,
            'description' => (string) $trip->description,
            'parent_info' => (string) $trip->parent_info,
            'tournament_id' => (string) $trip->tournament_id,
        ];
        $this->showTripForm = false;
        $this->newTeam = ['group' => '', 'name' => $this->teamName($trip->teams()->count() + 1)];
        $this->loadResults();
    }

    public function editTripDetails(): void
    {
        $this->showTripForm = true;
    }

    public function closeTrip(): void
    {
        $this->tripId = null;
        $this->showTripForm = false;
        $this->resetTrip();
    }

    public function saveTrip(): void
    {
        $data = $this->validate([
            'trip.title' => 'required|string|max:255',
            'trip.starts_on' => 'required|date',
            'trip.ends_on' => 'nullable|date|after_or_equal:trip.starts_on',
            'trip.city' => 'nullable|string|max:100',
            'trip.venue' => 'nullable|string|max:255',
            'trip.organizer' => 'nullable|string|max:255',
            'trip.description' => 'nullable|string|max:3000',
            'trip.parent_info' => 'nullable|string|max:3000',
            'trip.tournament_id' => 'nullable|exists:tournaments,id',
        ], [], ['trip.title' => 'nazwa turnieju', 'trip.starts_on' => 'data'])['trip'];

        $data = array_map(fn ($value) => is_string($value) ? (trim($value) ?: null) : $value, $data);
        $trip = MiniTrip::updateOrCreate(['id' => $this->tripId], $data);

        $this->toast($this->tripId ? 'Zapisano wyjazd.' : 'Dodano wyjazd. Teraz dodaj drużyny i dzieci.');
        $this->openTrip($trip->id);
    }

    public function deleteTrip(int $id): void
    {
        MiniTrip::findOrFail($id)->delete();
        $this->closeTrip();
        $this->toast('Usunięto wyjazd.');
    }

    public function addTeam(): void
    {
        $trip = MiniTrip::findOrFail($this->tripId);
        $data = $this->validate([
            'newTeam.group' => 'nullable|exists:mini_groups,id',
            'newTeam.name' => 'required|string|max:40',
        ], [], ['newTeam.name' => 'nazwa drużyny'])['newTeam'];

        $trip->teams()->create(['mini_group_id' => $data['group'] ?: null, 'name' => trim($data['name'])]);
        $this->newTeam['name'] = $this->teamName($trip->teams()->count() + 1);
        $this->loadResults();
    }

    public function deleteTeam(int $id): void
    {
        $this->tripTeam($id)->delete();
        $this->loadResults();
    }

    /**
     * Dziecko w składzie drużyny: klik dodaje albo usuwa (dziecko może grać w starszej grupie).
     */
    public function togglePlayer(int $teamId, int $playerId): void
    {
        $this->tripTeam($teamId)->players()->toggle([self::children()->findOrFail($playerId)->id]);
    }

    /**
     * Wynik zapisuje się od razu po wpisaniu: miejsce, na ile drużyn, krótka notka.
     */
    public function updatedResults(mixed $value, string $key): void
    {
        $teamId = (int) explode('.', $key)[0];
        $data = $this->validate([
            "results.{$teamId}.place" => 'nullable|integer|min:1|max:999',
            "results.{$teamId}.teams_count" => 'nullable|integer|min:1|max:999',
            "results.{$teamId}.note" => 'nullable|string|max:255',
        ])['results'][$teamId];

        $this->tripTeam($teamId)->update([
            'place' => filled($data['place'] ?? null) ? (int) $data['place'] : null,
            'teams_count' => filled($data['teams_count'] ?? null) ? (int) $data['teams_count'] : null,
            'note' => trim((string) ($data['note'] ?? '')) ?: null,
        ]);
        $this->toast('Zapisano wynik.');
    }

    public function updatedTeamPhoto(): void
    {
        $this->validate(['teamPhoto' => 'image|max:8192']);
        $this->tripTeam((int) $this->photoTeamId)->update(['photo_path' => $this->teamPhoto->store('mini', 'public')]);
        $this->reset(['teamPhoto', 'photoTeamId']);
        $this->toast('Dodano zdjęcie.');
    }

    private function teamName(int $number): string
    {
        return trim((app(SiteSettings::class)['mini_team_prefix'] ?: '').' '.$number);
    }

    private function tripTeam(int $id): MiniTripTeam
    {
        return MiniTripTeam::where('mini_trip_id', $this->tripId)->findOrFail($id);
    }

    private function loadResults(): void
    {
        $this->results = $this->tripId
            ? MiniTripTeam::where('mini_trip_id', $this->tripId)->get()->mapWithKeys(fn (MiniTripTeam $team) => [$team->id => [
                'place' => (string) $team->place,
                'teams_count' => (string) $team->teams_count,
                'note' => (string) $team->note,
            ]])->all()
            : [];
    }

    private function resetTrip(): void
    {
        $this->trip = ['title' => '', 'starts_on' => '', 'ends_on' => '', 'city' => '', 'venue' => '', 'organizer' => '', 'description' => '', 'parent_info' => '', 'tournament_id' => ''];
        $this->resetErrorBag();
    }

    // ── Dzieci ───────────────────────────────────────────────

    /**
     * Dzieci z minisiatkówki: profile zawodników bez drużyny, z grupą mini (albo przeniesione z dawnej listy mini bez grupy).
     *
     * @return Builder<Player>
     */
    public static function children(): Builder
    {
        return Player::query()->whereNull('players.team_id')
            ->where(fn (Builder $query) => $query->whereNotNull('players.mini_group_id')->orWhereNotNull('players.mini_player_id'));
    }

    public function editPlayer(int $id): void
    {
        $player = self::children()->findOrFail($id);
        $this->editingPlayerId = $player->id;
        $this->player = [
            'first_name' => $player->first_name,
            'last_name' => $player->last_name,
            'birth_year' => (string) $player->birth_year,
            'number' => (string) $player->jersey_number,
            'group' => (string) $player->mini_group_id,
            'extra_groups' => $player->extraMiniGroups()->pluck('mini_groups.id')->map(fn ($id) => (string) $id)->all(),
            'image_consent' => $player->image_consent,
            'is_active' => $player->is_active,
            'photo_path' => $player->photo_path,
        ];
        $this->playerPhoto = null;
    }

    public function savePlayer(): void
    {
        $data = $this->validate([
            'player.first_name' => 'required|string|max:60',
            'player.last_name' => 'required|string|max:80',
            'player.birth_year' => 'nullable|integer|min:2000|max:2100',
            'player.number' => 'nullable|integer|min:0|max:99',
            'player.group' => 'nullable|exists:mini_groups,id',
            'player.extra_groups' => 'array',
            'player.extra_groups.*' => 'integer|exists:mini_groups,id',
            'player.image_consent' => 'boolean',
            'player.is_active' => 'boolean',
            'playerPhoto' => 'nullable|image|max:8192',
        ], [], ['player.first_name' => 'imię', 'player.last_name' => 'nazwisko']);
        $player = $data['player'];

        $attributes = [
            'first_name' => trim($player['first_name']),
            'last_name' => trim($player['last_name']),
            'birth_year' => filled($player['birth_year'] ?? null) ? (int) $player['birth_year'] : null,
            'jersey_number' => filled($player['number'] ?? null) ? (int) $player['number'] : null,
            'mini_group_id' => filled($player['group'] ?? null) ? (int) $player['group'] : null,
            'image_consent' => (bool) $player['image_consent'],
            'is_active' => (bool) $player['is_active'],
        ];

        if ($this->playerPhoto) {
            $attributes['photo_path'] = $this->playerPhoto->store('mini', 'public');
        }

        // Dziecko z mini to zwykły profil zawodnika: bez drużyny, z grupą mini.
        if ($this->editingPlayerId) {
            $child = self::children()->findOrFail($this->editingPlayerId);
            $child->update($attributes);
        } else {
            $child = Player::create($attributes + ['team_id' => null]);
        }
        // Dodatkowe grupy (np. gra też w singielkach); główna grupa nie jest dodatkową.
        $child->extraMiniGroups()->sync(collect($player['extra_groups'] ?? [])->map(fn ($id) => (int) $id)->reject(fn (int $id) => $id === $attributes['mini_group_id'])->unique()->values()->all());
        $this->toast($this->editingPlayerId ? 'Zapisano dane dziecka.' : 'Dodano dziecko.');
        $this->resetPlayer($attributes['mini_group_id']);
    }

    public function deletePlayer(int $id): void
    {
        self::children()->findOrFail($id)->delete();
        $this->resetPlayer();
        $this->toast('Usunięto dziecko z listy.');
    }

    public function resetPlayer(?int $group = null): void
    {
        $this->editingPlayerId = null;
        $this->playerPhoto = null;
        $this->player = ['first_name' => '', 'last_name' => '', 'birth_year' => '', 'number' => '', 'group' => (string) ($group ?? ($this->playerGroup ?: '')), 'extra_groups' => [], 'image_consent' => false, 'is_active' => true, 'photo_path' => null];
        $this->resetErrorBag();
    }

    // ── Grupy ────────────────────────────────────────────────

    public function editGroup(int $id): void
    {
        $group = MiniGroup::findOrFail($id);
        $this->editingGroupId = $group->id;
        $this->group = collect($group->only(['name', 'slug', 'players_on_court', 'age_label', 'birth_year_from', 'birth_year_to', 'gender', 'color', 'lead', 'about', 'rules', 'history', 'display_order']))
            ->map(fn ($value) => (string) $value)->all() + ['is_active' => $group->is_active, 'photo_path' => $group->photo_path];
        $this->groupPhoto = null;
    }

    public function saveGroup(): void
    {
        $data = $this->validate([
            'group.name' => 'required|string|max:60',
            'group.slug' => ['nullable', 'alpha_dash', 'max:80', Rule::unique('mini_groups', 'slug')->ignore($this->editingGroupId)],
            'group.players_on_court' => 'required|integer|between:1,6',
            'group.age_label' => 'nullable|string|max:40',
            'group.birth_year_from' => 'nullable|integer|min:2000|max:2100',
            'group.birth_year_to' => 'nullable|integer|min:2000|max:2100',
            'group.gender' => ['nullable', Rule::in(array_keys(MiniGroup::GENDERS))],
            'group.lead' => 'nullable|string|max:255',
            'group.color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'group.about' => 'nullable|string|max:20000',
            'group.rules' => 'nullable|string|max:20000',
            'group.history' => 'nullable|string|max:20000',
            'group.display_order' => 'nullable|integer|min:0',
            'group.is_active' => 'boolean',
            'groupPhoto' => 'nullable|image|max:8192',
        ], [], ['group.name' => 'nazwa'])['group'];

        $attributes = collect($data)->except('photo_path')->map(fn ($value) => is_string($value) ? (trim($value) === '' ? null : trim($value)) : $value)->all();

        // Kolor jest wspólny dla wszystkich grup z tą liczbą graczy (i dla treningów oraz turniejów): zapis w ustawieniach.
        if (filled($attributes['color'] ?? null)) {
            app(SiteSettings::class)->set([AgeColors::key('mini'.max(1, min(4, (int) $attributes['players_on_court']))) => strtolower($attributes['color'])]);
        }
        $attributes['color'] = null;
        $attributes['slug'] = $attributes['slug'] ?? Str::slug($attributes['name']);
        $attributes['display_order'] = (int) ($attributes['display_order'] ?? 0);

        if ($this->groupPhoto) {
            $attributes['photo_path'] = $this->groupPhoto->store('mini', 'public');
        }

        $saved = MiniGroup::updateOrCreate(['id' => $this->editingGroupId], $attributes);
        $this->toast($this->editingGroupId ? 'Zapisano grupę.' : 'Dodano grupę.');

        // W zakładce grupy zostajemy przy niej, nowa grupa od razu dostaje swoją zakładkę.
        if ($this->section === 'grupa' || ! $this->editingGroupId) {
            $this->openGroup($saved->id, 'strona');

            return;
        }

        $this->resetGroup();
    }

    public function deleteGroup(int $id): void
    {
        MiniGroup::findOrFail($id)->delete();
        $this->resetGroup();
        $this->section = 'grupy';
        $this->groupTab = null;
        $this->toast('Usunięto grupę. Dzieci zostały na liście bez grupy.');
    }

    public function resetGroup(): void
    {
        $this->editingGroupId = null;
        $this->groupPhoto = null;
        $this->group = ['name' => '', 'slug' => '', 'players_on_court' => '4', 'age_label' => '', 'birth_year_from' => '', 'birth_year_to' => '', 'gender' => '', 'color' => '', 'lead' => '', 'about' => '', 'rules' => '', 'history' => '', 'display_order' => '0', 'is_active' => true, 'photo_path' => null];
        $this->resetErrorBag();
    }

    public function render(): View
    {
        $groups = MiniGroup::ordered()->withCount('players')->get();

        return view('livewire.admin.mini-volleyball-manager', [
            'groups' => $groups,
            'trips' => MiniTrip::with('teams')->orderByDesc('starts_on')->get(),
            'openTrip' => $this->tripId ? MiniTrip::with(['teams.players', 'teams.group'])->find($this->tripId) : null,
            'allPlayers' => self::children()->with('miniGroup')->where('is_active', true)->orderBy('last_name')->orderBy('first_name')->get(),
            'players' => self::children()->with(['miniGroup', 'extraMiniGroups:id,name', 'guardians:id,name'])
                ->when($this->playerGroup !== '', fn ($query) => $this->playerGroup === 'none' ? $query->whereNull('mini_group_id') : $query->where(fn ($inGroup) => $inGroup
                    ->where('mini_group_id', (int) $this->playerGroup)
                    ->orWhereHas('extraMiniGroups', fn ($extra) => $extra->whereKey((int) $this->playerGroup))))
                ->orderByDesc('is_active')->orderBy('last_name')->orderBy('first_name')->get(),
            'groupStaff' => $this->section === 'grupa' && $this->groupTab ? MiniGroup::find($this->groupTab)?->trainers()->orderBy('name')->get() ?? collect() : collect(),
            'staffCandidates' => $this->section === 'grupa' && $this->groupPane === 'kadra' ? self::staffCandidates() : collect(),
            'tournaments' => Tournament::where('is_organizer', true)->orderByDesc('start_date')->get(['id', 'name', 'edition', 'start_date']),
        ]);
    }
}

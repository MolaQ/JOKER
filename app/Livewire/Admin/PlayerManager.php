<?php

namespace App\Livewire\Admin;

use App\Models\Player;
use App\Models\PlayerAchievement;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\PlayerPosition;
use App\UserRole;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class PlayerManager extends Component
{
    use WithFileUploads, WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $first_name = '';

    public string $last_name = '';

    public ?int $jersey_number = null;

    public string $position = 'outside_hitter';

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

    public string $bio = '';

    public ?int $team_id = null;

    /** @var array<int, int> Dodatkowe drużyny w bieżącym sezonie (gra w kilku drużynach) */
    public array $extra_team_ids = [];

    public bool $is_active = true;

    public $photo = null;

    public ?string $existingPhoto = null;

    public string $search = '';

    public string $teamFilter = '';

    protected function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'jersey_number' => 'required|integer|min:0|max:99',
            'position' => 'required|in:setter,outside_hitter,opposite,middle_blocker,libero',
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
            'bio' => 'nullable|string',
            'team_id' => 'required|exists:teams,id',
            'extra_team_ids' => 'array',
            'extra_team_ids.*' => 'exists:teams,id',
            'is_active' => 'boolean',
            'photo' => 'nullable|image|max:4096|dimensions:ratio=4/5',
        ];
    }

    public ?string $flashType = null;

    public ?string $flashMessage = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTeamFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->flashType = null;
        $this->flashMessage = null;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $player = Player::findOrFail($id);

        $this->editingId = $player->id;
        $this->first_name = $player->first_name;
        $this->last_name = $player->last_name;
        $this->jersey_number = $player->jersey_number;
        $this->position = $player->position->value;
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
        $this->achievements = $player->achievements->map(fn (PlayerAchievement $achievement) => ['season' => (string) $achievement->season, 'title' => $achievement->title])->all();
        $this->bio = (string) $player->bio;
        $this->team_id = $player->team_id;
        $this->is_active = $player->is_active;
        $this->existingPhoto = $player->photo_path;
        $this->photo = null;

        $currentSeasonId = Season::current()->value('id');
        $this->extra_team_ids = $currentSeasonId
            ? $player->teamsForSeason($currentSeasonId)->pluck('teams.id')->reject(fn ($id) => $id === $player->team_id)->values()->all()
            : [];

        $this->flashType = null;
        $this->flashMessage = null;

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();
        $extraTeamIds = $validated['extra_team_ids'] ?? [];
        $guardianIds = $validated['guardian_ids'] ?? [];
        unset($validated['extra_team_ids'], $validated['guardian_ids'], $validated['achievements'], $validated['photo']);
        $validated['extra_positions'] = array_values(array_diff($this->extra_positions, [$this->position])) ?: null;

        if ($this->photo) {
            $validated['photo_path'] = $this->photo->store('players', 'public');
        }

        if ($this->editingId) {
            $player = Player::findOrFail($this->editingId);
            $player->update($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Zawodnik został zaktualizowany.';
        } else {
            $player = Player::create($validated);
            $this->flashType = 'success';
            $this->flashMessage = 'Zawodnik został dodany.';
        }

        $player->guardians()->sync($guardianIds);
        $player->achievements()->delete();

        foreach (array_values(array_filter($this->achievements, fn (array $row) => trim($row['title'] ?? '') !== '')) as $order => $row) {
            $player->achievements()->create([
                'season' => trim($row['season'] ?? '') ?: null,
                'title' => trim($row['title']),
                'display_order' => $order,
            ]);
        }

        $currentSeasonId = Season::current()->value('id');

        if ($currentSeasonId) {
            // Zawsze zapewnij wpis w macierzystej drużynie na bieżący sezon.
            $player->teams()->wherePivot('season_id', $currentSeasonId)->detach();

            $player->teams()->attach($player->team_id, [
                'season_id' => $currentSeasonId,
                'jersey_number' => $player->jersey_number,
                'is_captain' => false,
            ]);

            foreach ($extraTeamIds as $extraTeamId) {
                if ((int) $extraTeamId === (int) $player->team_id) {
                    continue;
                }

                $player->teams()->attach($extraTeamId, [
                    'season_id' => $currentSeasonId,
                    'jersey_number' => null,
                    'is_captain' => false,
                ]);
            }
        }

        $this->closeModal();
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
        Player::findOrFail($id)->delete();
        $this->flashType = 'success';
        $this->flashMessage = 'Zawodnik został usunięty.';
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
        $this->position = 'outside_hitter';
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
        $this->bio = '';
        $this->team_id = null;
        $this->extra_team_ids = [];
        $this->is_active = true;
        $this->photo = null;
        $this->existingPhoto = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $players = Player::with('team')
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('first_name', 'like', "%{$this->search}%")
                ->orWhere('last_name', 'like', "%{$this->search}%")
            ))
            ->when($this->teamFilter, fn ($q) => $q->where('team_id', $this->teamFilter))
            ->orderBy('team_id')
            ->orderBy('jersey_number')
            ->paginate(12);

        $teams = Team::orderBy('display_order')->get();
        $positions = PlayerPosition::cases();

        return view('livewire.admin.player-manager', [
            'players' => $players,
            'teams' => $teams,
            'positions' => $positions,
            'playerAccounts' => $this->showModal ? User::where('role', UserRole::Player)->orderBy('name')->get(['id', 'name', 'email']) : collect(),
            'parentAccounts' => $this->showModal ? User::where('role', UserRole::Parent)->orderBy('name')->get(['id', 'name', 'email']) : collect(),
        ]);
    }
}

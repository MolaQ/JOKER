<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Competition;
use App\Models\CompetitionLevel;
use App\Models\Season;
use App\Models\Team;
use App\Services\ScheduleImporter;
use Livewire\Component;
use Livewire\WithPagination;

class CompetitionManager extends Component
{
    use ShowsToasts, WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $level = '';

    public ?int $level_id = null;

    public ?int $season_id = null;

    public string $description = '';

    public int $display_order = 0;

    public int $points_win_3_0 = 3;

    public int $points_win_3_1 = 3;

    public int $points_win_3_2 = 2;

    public int $points_loss_2_3 = 1;

    public int $points_loss_1_3 = 0;

    public int $points_loss_0_3 = 0;

    /** @var array<int, string> */
    public array $standings_criteria = ['points', 'sets_ratio', 'points_ratio'];

    /** @var array<int, int> */
    public array $team_ids = [];

    /**
     * Nazwa naszej drużyny w tych rozgrywkach (gdy inna niż na stronie), klucz = id drużyny.
     *
     * @var array<int, string>
     */
    public array $external_names = [];

    /**
     * Jak podpisać drużynę w tabelach i kalendarzu (np. „LOMS Joker Piła”), klucz = id drużyny.
     *
     * @var array<int, string>
     */
    public array $display_names = [];

    /**
     * Dopisek mniejszą kursywą obok nazwy (np. „(kadeci)”), klucz = id drużyny.
     *
     * @var array<int, string>
     */
    public array $display_suffixes = [];

    public string $source_url = '';

    public string $search = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'level' => 'nullable|string|max:255',
            'level_id' => 'nullable|exists:competition_levels,id',
            'season_id' => 'required|exists:seasons,id',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer|min:0',
            'points_win_3_0' => 'required|integer|min:0|max:10',
            'points_win_3_1' => 'required|integer|min:0|max:10',
            'points_win_3_2' => 'required|integer|min:0|max:10',
            'points_loss_2_3' => 'required|integer|min:0|max:10',
            'points_loss_1_3' => 'required|integer|min:0|max:10',
            'points_loss_0_3' => 'required|integer|min:0|max:10',
            'standings_criteria' => 'array|min:1',
            'standings_criteria.*' => 'in:points,sets_ratio,sets_diff,points_ratio,points_diff,wins',
            'team_ids' => 'array',
            'team_ids.*' => 'exists:teams,id',
            'external_names' => 'array',
            'external_names.*' => 'nullable|string|max:255',
            'display_names' => 'array',
            'display_names.*' => 'nullable|string|max:255',
            'display_suffixes' => 'array',
            'display_suffixes.*' => 'nullable|string|max:60',
            'source_url' => ['nullable', 'string', 'max:2000', 'regex:/^(\s*https?:\/\/\S+\s*)*$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'source_url.regex' => 'Wklej pełne adresy stron (zaczynające się od https://), każdy w osobnej linii.',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLevelId(): void
    {
        if ($this->level_id && $level = CompetitionLevel::find($this->level_id)) {
            $this->level = $level->name;
        }
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->season_id = Season::current()->value('id');
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $competition = Competition::with('teams')->findOrFail($id);

        $this->editingId = $competition->id;
        $this->name = $competition->name;
        $this->level = (string) $competition->level;
        $this->level_id = $competition->level_id;
        $this->season_id = $competition->season_id;
        $this->description = (string) $competition->description;
        $this->display_order = $competition->display_order;
        $this->points_win_3_0 = $competition->points_win_3_0;
        $this->points_win_3_1 = $competition->points_win_3_1;
        $this->points_win_3_2 = $competition->points_win_3_2;
        $this->points_loss_2_3 = $competition->points_loss_2_3;
        $this->points_loss_1_3 = $competition->points_loss_1_3;
        $this->points_loss_0_3 = $competition->points_loss_0_3;
        $this->standings_criteria = $competition->sortCriteria();
        $this->team_ids = $competition->teams->pluck('id')->all();
        $this->external_names = $competition->teams->mapWithKeys(fn (Team $team) => [$team->id => (string) $team->pivot->external_name])->all();
        $this->display_names = $competition->teams->mapWithKeys(fn (Team $team) => [$team->id => (string) $team->pivot->display_name])->all();
        $this->display_suffixes = $competition->teams->mapWithKeys(fn (Team $team) => [$team->id => (string) $team->pivot->display_suffix])->all();
        $this->source_url = (string) $competition->source_url;

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();
        $teamIds = $validated['team_ids'] ?? [];
        unset($validated['team_ids'], $validated['external_names'], $validated['display_names'], $validated['display_suffixes']);
        $validated['source_url'] = trim($validated['source_url'] ?? '') ?: null;

        if ($this->editingId) {
            $competition = Competition::findOrFail($this->editingId);
            $competition->update($validated);
            $this->toast('Rozgrywki zostały zaktualizowane.');
        } else {
            $competition = Competition::create($validated);
            $this->toast('Rozgrywki zostały dodane.');
        }

        $competition->teams()->sync(collect($teamIds)->mapWithKeys(fn ($teamId) => [
            $teamId => [
                'external_name' => trim($this->external_names[$teamId] ?? '') ?: null,
                'display_name' => trim($this->display_names[$teamId] ?? '') ?: null,
                'display_suffix' => trim($this->display_suffixes[$teamId] ?? '') ?: null,
            ],
        ])->all());

        if ($competition->source_url) {
            $this->syncCompetition($competition);
        }

        $this->closeModal();
    }

    /**
     * Pobiera terminarz i wyniki ze strony związku (przycisk w tabeli).
     */
    public function sync(int $id): void
    {
        $this->syncCompetition(Competition::findOrFail($id));
    }

    private function syncCompetition(Competition $competition): void
    {
        app(ScheduleImporter::class)->sync($competition);

        $this->toast($competition->name.': '.$competition->sync_message, str_contains((string) $competition->sync_message, 'Nie udało się') ? 'error' : 'success');
    }

    public function delete(int $id): void
    {
        Competition::findOrFail($id)->delete();
        $this->toast('Rozgrywki zostały usunięte.');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->level = '';
        $this->level_id = null;
        $this->season_id = null;
        $this->description = '';
        $this->display_order = 0;
        $this->points_win_3_0 = 3;
        $this->points_win_3_1 = 3;
        $this->points_win_3_2 = 2;
        $this->points_loss_2_3 = 1;
        $this->points_loss_1_3 = 0;
        $this->points_loss_0_3 = 0;
        $this->standings_criteria = ['points', 'sets_ratio', 'points_ratio'];
        $this->team_ids = [];
        $this->external_names = [];
        $this->display_names = [];
        $this->display_suffixes = [];
        $this->source_url = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $competitions = Competition::with(['season', 'teams', 'competitionLevel'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('season_id', 'desc')
            ->orderBy('display_order')
            ->paginate(10);

        $seasons = Season::orderBy('start_date', 'desc')->get();
        $teams = Team::orderBy('display_order')->get();
        $levels = CompetitionLevel::orderBy('display_order')->orderBy('name')->get();

        return view('livewire.admin.competition-manager', [
            'competitions' => $competitions,
            'seasons' => $seasons,
            'teams' => $teams,
            'levels' => $levels,
        ]);
    }
}

<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Livewire\Concerns\SortsTable;
use App\Models\Competition;
use App\Models\CompetitionLevel;
use App\Models\Season;
use App\Models\Team;
use App\Services\ScheduleImporter;
use Livewire\Component;
use Livewire\WithPagination;

class CompetitionManager extends Component
{
    use ShowsToasts, SortsTable, WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $level = '';

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

    /** Drużyny wycofane z rozgrywek, każda w osobnej linii. */
    public string $withdrawn_teams = '';

    public string $search = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'level' => 'nullable|string|max:255',
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
            'withdrawn_teams' => ['nullable', 'string', 'max:2000'],
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
        $this->level = (string) ($competition->level ?: $competition->competitionLevel?->name);
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
        $this->withdrawn_teams = implode("\n", $competition->withdrawn_teams ?? []);

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();
        $teamIds = $validated['team_ids'] ?? [];
        unset($validated['team_ids'], $validated['external_names'], $validated['display_names'], $validated['display_suffixes']);
        $validated['source_url'] = trim($validated['source_url'] ?? '') ?: null;
        $validated['withdrawn_teams'] = collect(preg_split('/\R/u', (string) ($validated['withdrawn_teams'] ?? '')))->map(fn (string $name) => trim($name))->filter()->unique()->values()->all() ?: null;
        $validated['level'] = trim($validated['level'] ?? '') ?: null;
        $validated['level_id'] = $validated['level'] ? CompetitionLevel::where('name', $validated['level'])->value('id') : null;

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

        if ($cancelled = $competition->applyWithdrawals()) {
            $this->toast('Zmieniono mecze z wycofanymi drużynami: '.$cancelled.'.');
        }

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

    /**
     * Nowy sezon: kopiuje rozgrywki z poprzedniego sezonu do bieżącego i od razu pobiera terminarze.
     */
    public function copyFromPreviousSeason(): void
    {
        [$current, $previous] = $this->seasonPair();

        if (! $current || ! $previous) {
            $this->toast('Najpierw dodaj nowy sezon w „Sezony” i ustaw go jako bieżący.', 'error');

            return;
        }

        $copied = Competition::copyToSeason($previous, $current);

        Competition::where('season_id', $current->id)->whereNull('synced_at')->whereNotNull('source_url')->get()
            ->each(fn (Competition $competition) => rescue(fn () => app(ScheduleImporter::class)->sync($competition), report: false));

        $this->toast($copied > 0
            ? "Skopiowano rozgrywki do sezonu {$current->name}: {$copied}. Sprawdź adresy stron związku."
            : 'Wszystkie rozgrywki z poprzedniego sezonu są już w bieżącym.');
    }

    /**
     * Bieżący sezon i ten przed nim.
     *
     * @return array{0: ?Season, 1: ?Season}
     */
    private function seasonPair(): array
    {
        $current = Season::current()->first();
        $previous = $current ? Season::where('start_date', '<', $current->start_date)->orderByDesc('start_date')->first() : null;

        return [$current, $previous];
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
        $this->withdrawn_teams = '';
        $this->resetErrorBag();
    }

    protected function sortableColumns(): array
    {
        return [
            'nazwa' => 'name',
            'poziom' => fn ($q, $d) => $q->orderBy(CompetitionLevel::select('display_order')->whereColumn('competition_levels.id', 'competitions.level_id'), $d),
            'sezon' => fn ($q, $d) => $q->orderBy(Season::select('start_date')->whereColumn('seasons.id', 'competitions.season_id'), $d),
            'druzyny' => fn ($q, $d) => $q->withCount('teams')->orderBy('teams_count', $d),
        ];
    }

    protected function sortDescendingFirst(): array
    {
        return ['sezon', 'druzyny'];
    }

    public function render()
    {
        $competitions = $this->applySort(Competition::with(['season', 'teams', 'competitionLevel'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('season_id', 'desc')
            ->orderBy('display_order')
        )->paginate(10);

        $seasons = Season::orderBy('start_date', 'desc')->get();
        $teams = Team::orderBy('display_order')->get();
        $levels = CompetitionLevel::orderBy('display_order')->pluck('name')
            ->merge(Competition::whereNotNull('level')->distinct()->pluck('level'))
            ->filter()->unique()->values();

        [$currentSeason, $previousSeason] = $this->seasonPair();
        $canCopy = $currentSeason && $previousSeason
            && Competition::where('season_id', $previousSeason->id)->exists()
            && Competition::where('season_id', $currentSeason->id)->doesntExist();

        return view('livewire.admin.competition-manager', [
            'currentSeason' => $currentSeason,
            'previousSeason' => $previousSeason,
            'canCopy' => $canCopy,
            'competitions' => $competitions,
            'seasons' => $seasons,
            'teams' => $teams,
            'levels' => $levels,
        ]);
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\Season;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Turnieje w trakcie sezonu: nazwa, termin, miejsce, nasze drużyny i zajęte miejsca.
 * Mecze rozegrane na turnieju dodaje się w „Mecze i wyniki” (rodzaj: turniejowy).
 */
class TournamentManager extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $edition = '';

    public string $start_date = '';

    public string $end_date = '';

    public string $venue = '';

    public string $city = '';

    public bool $is_organizer = false;

    public string $description = '';

    /** @var array<int, int> */
    public array $team_ids = [];

    /** @var array<int, string> Zajęte miejsce, klucz = id drużyny (np. „2. miejsce”) */
    public array $placements = [];

    public ?string $flashMessage = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'edition' => 'nullable|string|max:60',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'venue' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'is_organizer' => 'boolean',
            'description' => 'nullable|string|max:3000',
            'team_ids' => 'required|array|min:1',
            'team_ids.*' => 'exists:teams,id',
            'placements.*' => 'nullable|string|max:60',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return ['team_ids.required' => 'Zaznacz co najmniej jedną naszą drużynę.'];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $tournament = Tournament::with('teams')->findOrFail($id);

        $this->editingId = $tournament->id;
        $this->name = $tournament->name;
        $this->edition = (string) $tournament->edition;
        $this->start_date = $tournament->start_date->format('Y-m-d');
        $this->end_date = (string) $tournament->end_date?->format('Y-m-d');
        $this->venue = (string) $tournament->venue;
        $this->city = (string) $tournament->city;
        $this->is_organizer = $tournament->is_organizer;
        $this->description = (string) $tournament->description;
        $this->team_ids = $tournament->teams->pluck('id')->all();
        $this->placements = $tournament->teams->mapWithKeys(fn (Team $team) => [$team->id => (string) $team->pivot->placement])->all();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => trim($this->name),
            'edition' => trim($this->edition) ?: null,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date ?: null,
            'venue' => trim($this->venue) ?: null,
            'city' => trim($this->city) ?: null,
            'is_organizer' => $this->is_organizer,
            'description' => trim($this->description) ?: null,
            'season_id' => Season::whereDate('start_date', '<=', $this->start_date)->whereDate('end_date', '>=', $this->start_date)->value('id')
                ?? Season::current()->value('id'),
        ];

        $tournament = $this->editingId
            ? tap(Tournament::findOrFail($this->editingId))->update($data)
            : Tournament::create($data);

        $tournament->teams()->sync(collect($this->team_ids)->mapWithKeys(fn ($teamId) => [
            $teamId => ['placement' => trim($this->placements[$teamId] ?? '') ?: null],
        ])->all());

        $this->flashMessage = $this->editingId ? 'Turniej został zaktualizowany.' : 'Turniej został dodany.';
        $this->closeModal();
    }

    public function delete(int $id): void
    {
        Tournament::findOrFail($id)->delete();
        $this->flashMessage = 'Turniej został usunięty.';
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'edition', 'start_date', 'end_date', 'venue', 'city', 'is_organizer', 'description', 'team_ids', 'placements']);
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.admin.tournament-manager', [
            'tournaments' => Tournament::with('teams')->withCount('games')->orderByDesc('start_date')->paginate(15),
            'teams' => Team::orderBy('display_order')->get(),
        ]);
    }
}

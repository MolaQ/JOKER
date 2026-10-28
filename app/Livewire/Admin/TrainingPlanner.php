<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Team;
use App\Models\TrainingSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Stały plan treningów: trener i członek sztabu układają zajęcia swoich drużyn, szef i administrator wszystkich.
 * Ten sam trening można od razu dodać na kilka dni tygodnia.
 */
class TrainingPlanner extends Component
{
    use ShowsToasts;

    public ?int $editingId = null;

    public ?int $team_id = null;

    /** @var list<int> */
    public array $weekdays = [];

    public string $starts_at = '16:00';

    public string $ends_at = '17:30';

    public string $venue = 'court1';

    public string $note = '';

    public string $filterTeam = '';

    public function mount(): void
    {
        $this->team_id = $this->teams->first()?->id;
    }

    /**
     * @return Collection<int, Team>
     */
    #[Computed]
    public function teams(): Collection
    {
        return Team::managedBy(Auth::user())->orderBy('display_order')->get();
    }

    protected function rules(): array
    {
        return [
            'team_id' => ['required', Rule::in($this->teams->pluck('id')->all())],
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:1,7'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'venue' => ['required', Rule::in(array_keys(TrainingSession::VENUES))],
            'note' => ['nullable', 'string', 'max:120'],
        ];
    }

    protected function messages(): array
    {
        return [
            'weekdays.required' => 'Zaznacz co najmniej jeden dzień.',
            'ends_at.after' => 'Koniec musi być później niż początek.',
        ];
    }

    /**
     * Treningi w tym samym miejscu, które nakładają się z wpisanymi godzinami (podpowiedź, nie blokada).
     *
     * @return Collection<int, TrainingSession>
     */
    #[Computed]
    public function conflicts(): Collection
    {
        if (! preg_match('/^\d{2}:\d{2}$/', $this->starts_at) || ! preg_match('/^\d{2}:\d{2}$/', $this->ends_at) || $this->starts_at >= $this->ends_at) {
            return collect();
        }

        return collect($this->weekdays)
            ->flatMap(fn ($weekday) => TrainingSession::conflicts((int) $weekday, $this->venue, $this->starts_at.':00', $this->ends_at.':00', $this->editingId))
            ->values();
    }

    public function save(): void
    {
        $data = $this->validate();
        $attributes = [
            'team_id' => $data['team_id'],
            'starts_at' => $data['starts_at'].':00',
            'ends_at' => $data['ends_at'].':00',
            'venue' => $data['venue'],
            'note' => trim($data['note'] ?? '') ?: null,
            'updated_by' => Auth::id(),
        ];

        if ($this->editingId) {
            $session = $this->editable($this->editingId);
            $session->update($attributes + ['weekday' => (int) $data['weekdays'][0]]);
            $this->toast('Trening zapisany.');
        } else {
            foreach (array_unique($data['weekdays']) as $weekday) {
                TrainingSession::create($attributes + ['weekday' => (int) $weekday]);
            }
            $this->toast(count($data['weekdays']) > 1 ? 'Dodano treningi: '.count(array_unique($data['weekdays'])).'.' : 'Trening dodany.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $session = $this->editable($id);

        $this->editingId = $session->id;
        $this->team_id = $session->team_id;
        $this->weekdays = [$session->weekday];
        $this->starts_at = substr((string) $session->starts_at, 0, 5);
        $this->ends_at = substr((string) $session->ends_at, 0, 5);
        $this->venue = $session->venue;
        $this->note = (string) $session->note;
        $this->resetErrorBag();
    }

    public function delete(int $id): void
    {
        $session = $this->editable($id);
        $session->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        $this->toast('Trening usunięty.');
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->weekdays = [];
        $this->note = '';
        $this->resetErrorBag();
    }

    private function editable(int $id): TrainingSession
    {
        $session = TrainingSession::findOrFail($id);
        abort_unless($this->teams->contains('id', $session->team_id), 403);

        return $session;
    }

    public function render()
    {
        $sessions = TrainingSession::with('team')
            ->when($this->filterTeam !== '', fn ($query) => $query->where('team_id', (int) $this->filterTeam))
            ->ordered()
            ->get();

        return view('livewire.admin.training-planner', [
            'days' => $sessions->groupBy('weekday')->sortKeys(),
            'allTeams' => Team::whereIn('id', TrainingSession::distinct()->pluck('team_id'))->orderBy('display_order')->get(['id', 'name']),
            'managedIds' => $this->teams->pluck('id'),
        ]);
    }
}

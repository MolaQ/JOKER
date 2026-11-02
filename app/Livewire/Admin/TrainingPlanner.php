<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\MiniGroup;
use App\Models\Team;
use App\Models\TrainingSession;
use App\Support\SiteSettings;
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

    /** id drużyny albo „mini” (trening minisiatkówki) */
    public string $team_id = '';

    /** @var list<int> grupy mini na wspólnym treningu; pusta lista = wszystkie grupy */
    public array $mini_groups = [];

    /** @var list<int> */
    public array $weekdays = [];

    public string $starts_at = '16:00';

    public string $ends_at = '17:30';

    public string $venue = 'court1';

    public string $note = '';

    public string $filterTeam = '';

    public function mount(): void
    {
        $this->team_id = (string) ($this->teams->first()?->id ?? ($this->canMini() ? 'mini' : ''));
    }

    /**
     * Treningi minisiatkówki układa ten, kto prowadzi minisiatkówkę (gdy moduł jest włączony).
     */
    public function canMini(): bool
    {
        return (bool) app(SiteSettings::class)['module_mini'] && (Auth::user()?->can('mini.manage') ?? false);
    }

    /**
     * @return Collection<int, MiniGroup>
     */
    #[Computed]
    public function miniGroupOptions(): Collection
    {
        return $this->canMini() ? MiniGroup::ordered()->get(['id', 'name', 'players_on_court', 'is_active']) : collect();
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
            'team_id' => ['required', Rule::in([...$this->teams->pluck('id')->map(fn ($id) => (string) $id)->all(), ...($this->canMini() ? ['mini'] : [])])],
            'mini_groups' => ['array'],
            'mini_groups.*' => ['integer', Rule::in($this->miniGroupOptions->pluck('id')->all())],
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
        $attributes = $this->who($data) + [
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

    /**
     * Edycja istniejącego treningu zapisuje się od razu po każdej zmianie, bez przycisku „Zapisz”.
     */
    public function updated(string $property): void
    {
        if ($this->editingId === null || ! in_array(explode('.', $property)[0], ['team_id', 'mini_groups', 'weekdays', 'starts_at', 'ends_at', 'venue', 'note'], true)) {
            return;
        }

        $data = $this->validate();
        $this->editable($this->editingId)->update($this->who($data) + [
            'weekday' => (int) $data['weekdays'][0],
            'starts_at' => $data['starts_at'].':00',
            'ends_at' => $data['ends_at'].':00',
            'venue' => $data['venue'],
            'note' => trim($data['note'] ?? '') ?: null,
            'updated_by' => Auth::id(),
        ]);
        $this->toast('Zapisano.');
    }

    public function edit(int $id): void
    {
        $session = $this->editable($id);

        $this->editingId = $session->id;
        $this->team_id = $session->isMini() ? 'mini' : (string) $session->team_id;
        $this->mini_groups = array_map('intval', $session->mini_group_ids ?? []);
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
        $this->dispatch('training-saved');
    }

    private function editable(int $id): TrainingSession
    {
        $session = TrainingSession::findOrFail($id);
        abort_unless($this->canEdit($session), 403);

        return $session;
    }

    public function canEdit(TrainingSession $session): bool
    {
        return $session->isMini() ? $this->canMini() : $this->teams->contains('id', $session->team_id);
    }

    /**
     * Kto trenuje: drużyna albo grupy minisiatkówki (bez drużyny).
     *
     * @param  array<string, mixed>  $data
     * @return array{team_id: int|null, mini_group_ids: list<int>|null}
     */
    private function who(array $data): array
    {
        if ($data['team_id'] === 'mini') {
            return ['team_id' => null, 'mini_group_ids' => array_values(array_unique(array_map('intval', $data['mini_groups'] ?? [])))];
        }

        return ['team_id' => (int) $data['team_id'], 'mini_group_ids' => null];
    }

    public function render()
    {
        $sessions = TrainingSession::with('team')
            ->when($this->filterTeam === 'mini', fn ($query) => $query->whereNull('team_id'))
            ->when($this->filterTeam !== '' && $this->filterTeam !== 'mini', fn ($query) => $query->where('team_id', (int) $this->filterTeam))
            ->ordered()
            ->get();

        return view('livewire.admin.training-planner', [
            'days' => $sessions->groupBy('weekday')->sortKeys(),
            'allTeams' => Team::whereIn('id', TrainingSession::whereNotNull('team_id')->distinct()->pluck('team_id'))->orderBy('display_order')->get(['id', 'name']),
            'hasMini' => TrainingSession::whereNull('team_id')->exists(),
            'allSessions' => $this->filterTeam === '' ? $sessions : TrainingSession::with('team')->ordered()->get(),
            'highlight' => match ($this->filterTeam) {
                '' => null, 'mini' => 'mini', default => (int) $this->filterTeam
            },
        ]);
    }
}

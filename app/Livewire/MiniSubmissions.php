<?php

namespace App\Livewire;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\ContentProposal;
use App\Models\MiniGroup;
use App\Models\MiniTrip;
use App\Models\Player;
use App\Support\Media;
use App\Support\MessageHtml;
use App\Support\MiniResults;
use App\Support\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Zakładka „Zgłoś” na stronie minisiatkówki: nowy turniej, wyniki po turnieju i relacja.
 * Trener mini i koordynator mini zgłaszają do zatwierdzenia; osoba z uprawnieniem do minisiatkówki zapisuje od razu.
 */
class MiniSubmissions extends Component
{
    use ShowsToasts, WithFileUploads;

    public ?int $groupId = null;

    public string $section = 'przeglad';

    /** Przegląd: szukanie po nazwie i miejscowości, filtr kategorii i rodzaju (zaplanowane / zakończone). */
    public string $search = '';

    public string $filterGroup = '';

    public string $filterWhen = '';

    /** @var array{title: string, starts_on: string, ends_on: string, city: string, venue: string} */
    public array $trip = ['title' => '', 'starts_on' => '', 'ends_on' => '', 'city' => '', 'venue' => ''];

    /** @var list<string> grupy, które grają w zgłaszanym turnieju (każda dostaje drużynę „Joker 1”) */
    public array $tripGroups = [];

    public string $resultsTrip = '';

    /** @var list<array{id: ?int, group: string, name: string, place: string, teams_count: string, players: list<int>}> */
    public array $rows = [];

    /** @var array{first_name: string, last_name: string, birth_date: string, birth_year: string, number: string, group: string, image_consent: bool, photo_url: ?string} */
    public array $child = ['first_name' => '', 'last_name' => '', 'birth_date' => '', 'birth_year' => '', 'number' => '', 'group' => '', 'image_consent' => false, 'photo_url' => null];

    public ?int $editingChildId = null;

    /** @var TemporaryUploadedFile|null zdjęcie dziecka w kadrze 1:1 */
    public $childPhoto = null;

    public string $reportTrip = '';

    public string $reportTitle = '';

    /** Lead: dwa, trzy zdania na kafelek w aktualnościach. */
    public string $reportLead = '';

    public string $reportContent = '';

    /** @var TemporaryUploadedFile|null */
    public $reportPhoto = null;

    public function mount(?int $groupId = null): void
    {
        abort_unless(self::canSubmit(), 403);
        $this->groupId = $groupId;
        $this->tripGroups = $groupId ? [(string) $groupId] : [];
        $this->child['group'] = (string) ($groupId ?? '');
    }

    /**
     * Dziecko na liście grupy: imię, nazwisko, data urodzenia (albo sam rocznik), numer i zdjęcie.
     * Zdjęcie widać na stronie dopiero przy zaznaczonej zgodzie rodzica na wizerunek.
     */
    public function saveChild(): void
    {
        $data = $this->validate([
            'child.first_name' => 'required|string|max:60',
            'child.last_name' => 'required|string|max:60',
            'child.birth_date' => 'nullable|date|after:2000-01-01|before:today',
            'child.birth_year' => 'nullable|integer|min:2000|max:2100',
            'child.number' => 'nullable|integer|min:0|max:99',
            'child.group' => 'required|exists:mini_groups,id',
            'child.image_consent' => 'boolean',
            'childPhoto' => 'nullable|image|max:8192',
        ], ['child.group.required' => __('Wybierz grupę.')], ['child.first_name' => __('imię'), 'child.last_name' => __('nazwisko'), 'child.birth_date' => __('data urodzenia'), 'child.birth_year' => __('rocznik'), 'child.number' => __('numer'), 'childPhoto' => __('zdjęcie')])['child'];

        $birthDate = filled($data['birth_date'] ?? null) ? $data['birth_date'] : null;
        $attributes = [
            'mini_group_id' => (int) $data['group'],
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'birth_date' => $birthDate,
            'birth_year' => $birthDate ? (int) substr($birthDate, 0, 4) : (filled($data['birth_year'] ?? null) ? (int) $data['birth_year'] : null),
            'jersey_number' => ($data['number'] ?? '') === '' ? null : (int) $data['number'],
            'image_consent' => (bool) ($data['image_consent'] ?? false),
        ];

        if ($this->childPhoto) {
            $attributes['photo_path'] = $this->childPhoto->store('mini', 'public');
        }

        if ($this->editingChildId) {
            $kid = Player::mini()->where('is_active', true)->findOrFail($this->editingChildId);

            if ($kid->image_consent !== $attributes['image_consent']) {
                $attributes['image_consent_changed_at'] = now();
            }

            $kid->update($attributes);
            $this->toast(__('Zapisano dane dziecka.'));
        } else {
            // Dziecko dostaje zwykły profil zawodnika (bez drużyny, z grupą mini).
            Player::create($attributes + [
                'is_active' => true,
                'image_consent_changed_at' => $attributes['image_consent'] ? now() : null,
            ]);
            $this->toast(__('Dziecko dopisane do grupy.'));
        }

        $this->resetChild($data['group']);
    }

    public function editChild(int $id): void
    {
        $kid = Player::mini()->where('is_active', true)->findOrFail($id);
        $this->editingChildId = $kid->id;
        $this->childPhoto = null;
        $this->child = [
            'first_name' => (string) $kid->first_name,
            'last_name' => (string) $kid->last_name,
            'birth_date' => (string) $kid->birth_date?->format('Y-m-d'),
            'birth_year' => (string) $kid->birth_year,
            'number' => (string) $kid->jersey_number,
            'group' => (string) $kid->mini_group_id,
            'image_consent' => (bool) $kid->image_consent,
            'photo_url' => $kid->photo_path ? Media::url($kid->photo_path) : null,
        ];
        $this->resetErrorBag();
    }

    public function resetChild(?string $group = null): void
    {
        $this->editingChildId = null;
        $this->childPhoto = null;
        $this->child = ['first_name' => '', 'last_name' => '', 'birth_date' => '', 'birth_year' => '', 'number' => '', 'group' => (string) ($group ?? $this->groupId ?? ''), 'image_consent' => false, 'photo_url' => null];
        $this->resetErrorBag();
    }

    public function moveChild(int $id, string $groupId): void
    {
        abort_unless(MiniGroup::whereKey($groupId)->exists(), 422);
        Player::mini()->where('is_active', true)->findOrFail($id)->update(['mini_group_id' => (int) $groupId]);
        $this->toast(__('Dziecko przeniesione.'));
    }

    /**
     * Dziecko, które skończyło treningi, znika z listy grupy, ale zostaje w składach z dawnych turniejów.
     */
    public function retireChild(int $id): void
    {
        Player::mini()->where('is_active', true)->findOrFail($id)->update(['is_active' => false]);
        $this->toast(__('Dziecko usunięte z listy grupy. W wynikach dawnych turniejów zostaje.'));
    }

    /**
     * Z przeglądu: otwiera skład i wyniki albo relację wybranego turnieju.
     */
    public function openTrip(int $id, string $section): void
    {
        abort_unless($this->tripQuery()->whereKey($id)->exists(), 404);

        if ($section === 'relacja') {
            $this->reportTrip = (string) $id;
        } else {
            $section = 'wyniki';
            $this->resultsTrip = (string) $id;
            $this->updatedResultsTrip();
        }

        $this->section = $section;
    }

    public static function canSubmit(): bool
    {
        $user = Auth::user();

        return (bool) $user?->is_active && ($user->can('mini.submit') || $user->can('mini.manage'));
    }

    private static function canPublish(): bool
    {
        return (bool) Auth::user()?->can('mini.manage');
    }

    public function saveTrip(): void
    {
        $data = $this->validate([
            'trip.title' => 'required|string|max:255',
            'trip.starts_on' => 'required|date',
            'trip.ends_on' => 'nullable|date|after_or_equal:trip.starts_on',
            'trip.city' => 'required|string|max:100',
            'trip.venue' => 'nullable|string|max:255',
            'tripGroups' => 'array',
            'tripGroups.*' => 'exists:mini_groups,id',
        ], [], ['trip.title' => __('nazwa turnieju'), 'trip.starts_on' => __('data'), 'trip.city' => __('miejscowość')])['trip'];

        $trip = MiniTrip::create(array_map(fn ($value) => trim((string) $value) ?: null, $data) + [
            'status' => self::canPublish() ? MiniTrip::STATUS_PUBLISHED : MiniTrip::STATUS_PENDING,
            'submitted_by' => Auth::id(),
        ]);

        foreach (array_unique($this->tripGroups) as $groupId) {
            $trip->teams()->create(['mini_group_id' => (int) $groupId, 'name' => trim((app(SiteSettings::class)['mini_team_prefix'] ?: '').' 1')]);
        }

        $this->reset('trip');
        $this->section = 'przeglad';
        $this->toast(self::canPublish() ? __('Turniej dodany do kalendarza.') : __('Dziękujemy! Turniej pojawi się w kalendarzu po zatwierdzeniu.'));
    }

    public function updatedResultsTrip(): void
    {
        $trip = $this->tripQuery()->find((int) $this->resultsTrip);
        $this->rows = $trip ? MiniResults::rows($trip) : [];

        if ($trip && $this->rows === []) {
            $this->addRow();
        }
    }

    public function addRow(): void
    {
        $this->rows[] = [
            'id' => null,
            'group' => (string) ($this->groupId ?? ''),
            'name' => trim((app(SiteSettings::class)['mini_team_prefix'] ?: '').' '.(count($this->rows) + 1)),
            'place' => '',
            'teams_count' => '',
            'players' => [],
        ];
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function saveResults(): void
    {
        $this->validate([
            'resultsTrip' => 'required',
            'rows' => 'required|array|min:1|max:30',
            'rows.*.group' => 'nullable|exists:mini_groups,id',
            'rows.*.name' => 'required|string|max:40',
            'rows.*.place' => 'nullable|integer|min:1|max:999',
            'rows.*.teams_count' => 'nullable|integer|min:1|max:999',
            'rows.*.players' => 'array',
            'rows.*.players.*' => 'integer|exists:players,id',
        ], ['resultsTrip.required' => __('Wybierz turniej.')], ['rows.*.name' => __('nazwa drużyny'), 'rows.*.place' => __('miejsce'), 'rows.*.teams_count' => __('liczba drużyn')]);

        $trip = $this->tripQuery()->findOrFail((int) $this->resultsTrip);

        if (self::canPublish()) {
            MiniResults::apply($trip, $this->rows);
            $this->toast(__('Wyniki zapisane na stronie.'));
        } else {
            MiniResults::submit($trip, $this->rows, (int) Auth::id());
            $this->toast(__('Dziękujemy! Wyniki pojawią się po zatwierdzeniu.'));
        }

        $this->reset('resultsTrip', 'rows');
        $this->section = 'przeglad';
    }

    public function sendReport(): void
    {
        $this->validate([
            'reportTrip' => 'required',
            'reportTitle' => 'required|string|max:160',
            'reportLead' => 'nullable|string|max:2000',
            'reportContent' => ['required', 'string', 'max:40000', function (string $attribute, mixed $value, \Closure $fail) {
                if (mb_strlen(trim(html_entity_decode(strip_tags((string) $value)))) < 20) {
                    $fail(__('Napisz choć kilka zdań relacji.'));
                }
            }],
            'reportPhoto' => 'nullable|image|max:10240',
        ], ['reportTrip.required' => __('Wybierz turniej.')], ['reportTitle' => __('tytuł'), 'reportLead' => __('lead'), 'reportContent' => __('treść')]);

        $trip = $this->tripQuery()->findOrFail((int) $this->reportTrip);
        $lead = MessageHtml::sanitize($this->reportLead);

        ContentProposal::create([
            'user_id' => Auth::id(),
            'type' => ContentProposal::TYPE_ARTICLE,
            'match_type' => $trip->getMorphClass(),
            'match_id' => $trip->id,
            'title' => trim($this->reportTitle),
            'excerpt' => trim(strip_tags($lead)) !== '' ? $lead : null,
            'content' => MessageHtml::sanitize($this->reportContent),
            'files' => $this->reportPhoto ? [$this->reportPhoto->store('content-proposals', 'public')] : null,
        ]);

        $this->reset('reportTrip', 'reportTitle', 'reportLead', 'reportContent', 'reportPhoto');
        $this->section = 'przeglad';
        $this->toast(__('Dziękujemy! Relacja pojawi się w aktualnościach po zatwierdzeniu.'));
    }

    /**
     * Turnieje do składu, wyników i relacji: opublikowane albo zgłoszone przez tę osobę, zaplanowane i z ostatniego roku.
     * Skład można ustalić przed turniejem (pokaże się w terminarzu), miejsce wpisuje się po turnieju.
     */
    private function tripQuery()
    {
        return MiniTrip::query()
            ->where(fn ($query) => $query->where('status', MiniTrip::STATUS_PUBLISHED)->orWhere('submitted_by', Auth::id()))
            ->whereDate('starts_on', '>=', today()->subYear())
            ->orderByDesc('starts_on');
    }

    /**
     * Turnieje w przeglądzie po filtrach: zaplanowane od najbliższego, zakończone od najnowszego.
     *
     * @return array{upcoming: Collection<int, MiniTrip>, past: Collection<int, MiniTrip>}
     */
    private function overview(): array
    {
        $search = trim($this->search);
        // Na stronie grupy przegląd pokazuje tylko jej turnieje i jej drużyny.
        $group = $this->groupId ?: ($this->filterGroup !== '' ? (int) $this->filterGroup : null);
        $trips = $this->tripQuery()
            ->with(['teams' => fn ($query) => $query->with(['group', 'players'])->when($this->groupId, fn ($teams) => $teams->where('mini_group_id', $this->groupId))->orderBy('name')])
            // turniej bez drużyn jest jeszcze bez kategorii, więc widać go wszędzie
            ->when($group, fn ($query) => $query->where(fn ($where) => $where->whereHas('teams', fn ($teams) => $teams->where('mini_group_id', $group))->orWhereDoesntHave('teams')))
            ->when($search !== '', fn ($query) => $query->where(fn ($where) => $where->where('title', 'like', "%{$search}%")->orWhere('city', 'like', "%{$search}%")->orWhere('venue', 'like', "%{$search}%")))
            ->get();

        return [
            'upcoming' => $this->filterWhen === 'past' ? collect() : $trips->reject->isPast()->sortBy('starts_on')->values(),
            'past' => $this->filterWhen === 'upcoming' ? collect() : $trips->filter->isPast()->values(),
        ];
    }

    public function render(): View
    {
        $groups = MiniGroup::where('is_active', true)->ordered()->with(['players' => fn ($query) => $query->where('is_active', true)])->get()
            // do składu na turniej: dzieci z grupy razem z grającymi w niej dodatkowo
            ->each(fn (MiniGroup $group) => $group->setRelation('squad', $group->members()->where('is_active', true)->get()));

        /** @var Collection<int, MiniTrip> $trips */
        $trips = $this->tripQuery()->get();

        return view('livewire.mini-submissions', [
            'groups' => $groups,
            'trips' => $trips,
            'selectedTrip' => $this->resultsTrip !== '' ? $trips->firstWhere('id', (int) $this->resultsTrip) : null,
            'overview' => $this->section === 'przeglad' ? $this->overview() : null,
            'publishes' => self::canPublish(),
        ]);
    }
}

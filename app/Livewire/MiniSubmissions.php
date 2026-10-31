<?php

namespace App\Livewire;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\ContentProposal;
use App\Models\MiniGroup;
use App\Models\MiniPlayer;
use App\Models\MiniTrip;
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

    public string $section = 'turniej';

    /** @var array{title: string, starts_on: string, ends_on: string, city: string, venue: string} */
    public array $trip = ['title' => '', 'starts_on' => '', 'ends_on' => '', 'city' => '', 'venue' => ''];

    /** @var list<string> grupy, które grają w zgłaszanym turnieju (każda dostaje drużynę „Joker 1”) */
    public array $tripGroups = [];

    public string $resultsTrip = '';

    /** @var list<array{id: ?int, group: string, name: string, place: string, teams_count: string, players: list<int>}> */
    public array $rows = [];

    /** @var array{first_name: string, last_name: string, birth_year: string, group: string} */
    public array $child = ['first_name' => '', 'last_name' => '', 'birth_year' => '', 'group' => ''];

    public string $reportTrip = '';

    public string $reportTitle = '';

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
     * Dziecko na liście grupy: tylko imię, nazwisko i rocznik, bez konta i bez profilu.
     * Zgodę na zdjęcie i zdjęcie ustawia administrator w panelu.
     */
    public function addChild(): void
    {
        $data = $this->validate([
            'child.first_name' => 'required|string|max:60',
            'child.last_name' => 'required|string|max:60',
            'child.birth_year' => 'nullable|integer|min:2000|max:2100',
            'child.group' => 'required|exists:mini_groups,id',
        ], ['child.group.required' => __('Wybierz grupę.')], ['child.first_name' => __('imię'), 'child.last_name' => __('nazwisko'), 'child.birth_year' => __('rocznik')])['child'];

        MiniPlayer::create([
            'mini_group_id' => (int) $data['group'],
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'birth_year' => $data['birth_year'] ?: null,
            'is_active' => true,
        ]);

        $this->child = ['first_name' => '', 'last_name' => '', 'birth_year' => '', 'group' => $data['group']];
        $this->toast(__('Dziecko dopisane do grupy.'));
    }

    public function moveChild(int $id, string $groupId): void
    {
        abort_unless(MiniGroup::whereKey($groupId)->exists(), 422);
        MiniPlayer::where('is_active', true)->findOrFail($id)->update(['mini_group_id' => (int) $groupId]);
        $this->toast(__('Dziecko przeniesione.'));
    }

    /**
     * Dziecko, które skończyło treningi, znika z listy grupy, ale zostaje w składach z dawnych turniejów.
     */
    public function retireChild(int $id): void
    {
        MiniPlayer::where('is_active', true)->findOrFail($id)->update(['is_active' => false]);
        $this->toast(__('Dziecko zdjęte z listy grupy.'));
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
            'rows.*.players.*' => 'integer|exists:mini_players,id',
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
    }

    public function sendReport(): void
    {
        $this->validate([
            'reportTrip' => 'required',
            'reportTitle' => 'required|string|max:160',
            'reportContent' => 'required|string|min:20|max:20000',
            'reportPhoto' => 'nullable|image|max:10240',
        ], ['reportTrip.required' => __('Wybierz turniej.')], ['reportTitle' => __('tytuł'), 'reportContent' => __('treść')]);

        $trip = $this->tripQuery()->findOrFail((int) $this->reportTrip);

        ContentProposal::create([
            'user_id' => Auth::id(),
            'type' => ContentProposal::TYPE_ARTICLE,
            'match_type' => $trip->getMorphClass(),
            'match_id' => $trip->id,
            'title' => trim($this->reportTitle),
            'content' => trim($this->reportContent),
            'files' => $this->reportPhoto ? [$this->reportPhoto->store('content-proposals', 'public')] : null,
        ]);

        $this->reset('reportTrip', 'reportTitle', 'reportContent', 'reportPhoto');
        $this->toast(__('Dziękujemy! Relacja pojawi się po zatwierdzeniu.'));
    }

    /**
     * Turnieje do wyników i relacji: opublikowane albo zgłoszone przez tę osobę, od pół roku wstecz do dziś.
     */
    private function tripQuery()
    {
        return MiniTrip::query()
            ->where(fn ($query) => $query->where('status', MiniTrip::STATUS_PUBLISHED)->orWhere('submitted_by', Auth::id()))
            ->whereDate('starts_on', '<=', today())
            ->whereDate('starts_on', '>=', today()->subMonths(6))
            ->orderByDesc('starts_on');
    }

    public function render(): View
    {
        $groups = MiniGroup::where('is_active', true)->ordered()->with(['players' => fn ($query) => $query->where('is_active', true)])->get();

        /** @var Collection<int, MiniTrip> $trips */
        $trips = $this->tripQuery()->get();

        return view('livewire.mini-submissions', [
            'groups' => $groups,
            'trips' => $trips,
            'publishes' => self::canPublish(),
        ]);
    }
}

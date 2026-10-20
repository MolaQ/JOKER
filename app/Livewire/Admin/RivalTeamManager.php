<?php

namespace App\Livewire\Admin;

use App\Models\RivalClub;
use App\Models\RivalTeam;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Rywale w panelu: kluby (zwinięte, ze wspólnym logo) i ich drużyny, lista „Do przypisania”
 * dla nowych nazw z importu oraz podpowiedzi klubów dla drużyn o tej samej nazwie bazowej.
 */
class RivalTeamManager extends Component
{
    use WithFileUploads;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $full_name = '';

    public string $short_name = '';

    public string $rival_club_id = '';

    public string $aliases = '';

    public $logo = null;

    public ?string $existingLogo = null;

    public bool $showClubModal = false;

    public ?int $editingClubId = null;

    public string $clubName = '';

    public $clubLogo = null;

    public ?string $existingClubLogo = null;

    public string $search = '';

    public bool $showArchived = false;

    /** @var array<int, string> */
    public array $selected = [];

    public string $groupClubId = '';

    public string $groupClubName = '';

    /** @var array<int, array{club: string, merge: string}> */
    public array $review = [];

    public ?string $flashType = null;

    public ?string $flashMessage = null;

    protected function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:255',
            'rival_club_id' => 'nullable|exists:rival_clubs,id',
            'aliases' => 'nullable|string|max:3000',
            'logo' => 'nullable|image|max:2048',
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $rivalTeam = RivalTeam::findOrFail($id);

        $this->resetForm();
        $this->editingId = $rivalTeam->id;
        $this->full_name = $rivalTeam->full_name;
        $this->short_name = (string) $rivalTeam->short_name;
        $this->rival_club_id = (string) $rivalTeam->rival_club_id;
        $this->aliases = implode("\n", $rivalTeam->aliases ?? []);
        $this->existingLogo = $rivalTeam->logo_path;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'full_name' => trim($validated['full_name']),
            'short_name' => trim((string) $validated['short_name']) ?: null,
            'rival_club_id' => $validated['rival_club_id'] ?: null,
            'aliases' => Str::of($this->aliases)->explode("\n")->map(fn (string $alias) => trim($alias))->filter()->unique()->values()->all() ?: null,
            'needs_review' => false,
        ];

        if ($this->logo) {
            $data['logo_path'] = $this->logo->store('rival-teams', 'public');
        }

        if ($this->editingId) {
            RivalTeam::findOrFail($this->editingId)->update($data);
            $this->flash('Drużyna rywali została zaktualizowana.');
        } else {
            RivalTeam::create($data);
            $this->flash('Drużyna rywali została dodana.');
        }

        $this->closeModal();
    }

    /**
     * Usuwa własne logo drużyny, od teraz pokazuje się logo klubu.
     */
    public function removeOwnLogo(): void
    {
        RivalTeam::whereKey($this->editingId)->update(['logo_path' => null]);
        $this->existingLogo = null;
    }

    public function delete(int $id): void
    {
        RivalTeam::findOrFail($id)->delete();
        $this->flash('Drużyna rywali została usunięta.');
    }

    public function detachFromClub(int $id): void
    {
        RivalTeam::whereKey($id)->update(['rival_club_id' => null]);
    }

    public function openClubModal(?int $id = null): void
    {
        $club = $id ? RivalClub::findOrFail($id) : null;

        $this->editingClubId = $club?->id;
        $this->clubName = (string) $club?->name;
        $this->existingClubLogo = $club?->logo_path;
        $this->clubLogo = null;
        $this->resetErrorBag();
        $this->showClubModal = true;
    }

    public function saveClub(): void
    {
        $this->validate(['clubName' => 'required|string|max:255', 'clubLogo' => 'nullable|image|max:2048']);

        $data = ['name' => trim($this->clubName)];

        if ($this->clubLogo) {
            $data['logo_path'] = $this->clubLogo->store('rival-clubs', 'public');
        }

        RivalClub::updateOrCreate(['id' => $this->editingClubId], $data);
        $this->showClubModal = false;
        $this->flash('Zapisano klub.');
    }

    /**
     * Usuwa klub; jego drużyny zostają, tylko bez klubu.
     */
    public function deleteClub(int $id): void
    {
        RivalClub::findOrFail($id)->delete();
        $this->flash('Klub usunięty, jego drużyny zostały bez klubu.');
    }

    /**
     * Zaznaczone drużyny trafiają do wybranego albo nowego klubu.
     */
    public function groupSelected(): void
    {
        $ids = collect($this->selected)->filter()->keys()->all();

        if ($ids === []) {
            $this->flash('Zaznacz drużyny, które chcesz połączyć w klub.', 'error');

            return;
        }

        if ($this->groupClubId !== '') {
            $club = RivalClub::findOrFail((int) $this->groupClubId);
        } else {
            $this->validate(['groupClubName' => 'required|string|max:255'], ['groupClubName.required' => 'Wybierz klub albo wpisz nazwę nowego.']);
            $club = RivalClub::create(['name' => trim($this->groupClubName)]);
        }

        RivalTeam::whereIn('id', $ids)->update(['rival_club_id' => $club->id, 'needs_review' => false]);
        $this->reset('selected', 'groupClubId', 'groupClubName');
        $this->flash('Drużyny dodane do klubu '.$club->name.'.');
    }

    /**
     * Podpowiedź: drużyny bez klubu o tej samej nazwie bazowej („Energetyk Poznań 1”, „2”, „II”).
     *
     * @param  array<int, int>  $ids
     */
    public function createSuggestedClub(array $ids): void
    {
        $teams = RivalTeam::whereIn('id', $ids)->orderByRaw('length(full_name)')->get();
        $club = RivalClub::find($teams->pluck('rival_club_id')->filter()->first());

        if ($teams->count() < 2) {
            return;
        }

        $club ??= RivalClub::create([
            'name' => $teams->first()->full_name,
            'logo_path' => $teams->pluck('logo_path')->filter()->first(),
        ]);
        RivalTeam::whereIn('id', $teams->pluck('id'))->update(['rival_club_id' => $club->id]);
        $this->flash('Klub '.$club->name.': '.$teams->count().' drużyny.');
    }

    /**
     * Nowa nazwa z importu: zostaje osobną drużyną (opcjonalnie w klubie) albo okazuje się
     * tą samą drużyną pod inną nazwą i zostaje z nią połączona.
     */
    public function resolveReview(int $id): void
    {
        $team = RivalTeam::findOrFail($id);
        $row = $this->review[$id] ?? ['club' => '', 'merge' => ''];

        if (($row['merge'] ?? '') !== '') {
            $target = RivalTeam::findOrFail((int) $row['merge']);
            $team->mergeInto($target);
            $this->flash('„'.$team->full_name.'” to teraz dawna nazwa drużyny '.$target->full_name.'. Następny import ją rozpozna.');
        } else {
            $team->update(['rival_club_id' => ($row['club'] ?? '') !== '' ? (int) $row['club'] : $team->rival_club_id, 'needs_review' => false]);
            $this->flash('Zapisano: '.$team->full_name.'.');
        }

        unset($this->review[$id]);
    }

    public function acceptAllReview(): void
    {
        RivalTeam::where('needs_review', true)->update(['needs_review' => false]);
        $this->review = [];
        $this->flash('Wszystkie nowe nazwy zostały jako osobne drużyny.');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'full_name', 'short_name', 'rival_club_id', 'aliases', 'logo', 'existingLogo');
        $this->resetErrorBag();
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->flashType = $type;
        $this->flashMessage = $message;
    }

    /**
     * Podpowiedzi: drużyny, których nazwy bazowe się pokrywają („Energetyk Poznań” i „ENEA Energetyk Poznań 2”,
     * „LUKS Wilki Wilczyn” i „Ogrobet … LUKS Wilki Wilczyn”), a przynajmniej jedna jest jeszcze bez klubu.
     *
     * @param  Collection<int, RivalTeam>  $teams
     * @return Collection<int, Collection<int, RivalTeam>>
     */
    private function clubSuggestions(Collection $teams): Collection
    {
        $keys = $teams->mapWithKeys(fn (RivalTeam $team) => [$team->id => RivalTeam::clubKey($team->full_name)]);
        $parent = $keys->keys()->mapWithKeys(fn (int $id) => [$id => $id])->all();
        $find = function (int $id) use (&$parent, &$find): int {
            return $parent[$id] === $id ? $id : ($parent[$id] = $find($parent[$id]));
        };

        foreach ($keys as $id => $key) {
            foreach ($keys as $otherId => $otherKey) {
                $shorter = strlen($key) <= strlen($otherKey) ? $key : $otherKey;
                $longer = $shorter === $key ? $otherKey : $key;

                if ($id < $otherId && strlen($shorter) >= 8 && str_contains($longer, $shorter)) {
                    $parent[$find($id)] = $find($otherId);
                }
            }
        }

        return $teams->groupBy(fn (RivalTeam $team) => $find($team->id))
            ->filter(fn (Collection $group) => $group->count() > 1
                && $group->contains(fn (RivalTeam $team) => $team->rival_club_id === null)
                && $group->pluck('rival_club_id')->filter()->unique()->count() <= 1)
            ->values();
    }

    public function render(): View
    {
        $all = RivalTeam::with('club')->withCount('games')->orderBy('full_name')->get();
        $activeIds = RivalTeam::active()->pluck('id');
        $term = Str::lower(trim($this->search));

        $visible = $all
            ->when(! $this->showArchived && $term === '', fn (Collection $teams) => $teams->whereIn('id', $activeIds))
            ->when($term !== '', fn (Collection $teams) => $teams->filter(fn (RivalTeam $team) => Str::contains(Str::lower($team->full_name.' '.$team->short_name.' '.$team->club?->name.' '.implode(' ', $team->aliases ?? [])), $term)));

        $toReview = $all->where('needs_review', true)->values();

        foreach ($toReview as $team) {
            $this->review[$team->id] ??= [
                'club' => (string) ($all->where('rival_club_id', '!==', null)->first(fn (RivalTeam $other) => RivalTeam::clubKey($other->full_name) === RivalTeam::clubKey($team->full_name))?->rival_club_id ?? ''),
                'merge' => '',
            ];
        }

        return view('livewire.admin.rival-team-manager', [
            'clubs' => RivalClub::orderBy('name')->get()
                ->map(fn (RivalClub $club) => $club->setRelation('visibleTeams', $visible->where('rival_club_id', $club->id)->values()))
                ->filter(fn (RivalClub $club) => $club->visibleTeams->isNotEmpty() || ($term === '' && $all->where('rival_club_id', $club->id)->isEmpty()) || Str::contains(Str::lower($club->name), $term)),
            'unclubbed' => $visible->whereNull('rival_club_id')->where('needs_review', false)->values(),
            'toReview' => $toReview,
            'suggestions' => $this->clubSuggestions($all->where('needs_review', false)->values()),
            'allClubs' => RivalClub::orderBy('name')->get(['id', 'name']),
            'allTeams' => $all,
            'archivedCount' => $all->whereNotIn('id', $activeIds)->count(),
        ]);
    }
}

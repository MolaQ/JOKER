<?php

namespace App\Livewire\Admin;

use App\Models\RivalTeam;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentCategory;
use App\Models\TournamentMatch;
use App\Models\TournamentParticipant;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Strona turnieju w panelu: informacje i informator, kategorie wiekowe, drużyny ze składami,
 * grupy (z generowaniem meczów „każdy z każdym”), mecze grupowe, drabinka i mecze o miejsca.
 */
class TournamentEditor extends Component
{
    use WithFileUploads;

    public Tournament $tournament;

    #[Url(as: 'sekcja')]
    public string $section = 'strona';

    #[Url(as: 'kategoria')]
    public ?int $categoryId = null;

    public bool $has_page = false;

    public string $series = '';

    public string $rules = '';

    public string $guest_info = '';

    public $image = null;

    public string $newCategory = '';

    public string $newGroup = '';

    /** Nowa drużyna: „rival:12”, „team:3” albo pusta (wtedy nazwa nowego rywala). */
    public string $newParticipant = '';

    public string $newParticipantName = '';

    public $newParticipantLogo = null;

    /** @var array<int, array{group: ?string, roster: string, final_position: ?string, display_name: string}> */
    public array $participants = [];

    /** @var array<string, mixed> */
    public array $match = [];

    /** @var array<int, array{sets: string, starts_at: string, court: string}> */
    public array $results = [];

    public ?string $flashMessage = null;

    public function mount(Tournament $tournament): void
    {
        $this->tournament = $tournament;
        $this->has_page = $tournament->has_page;
        $this->series = (string) $tournament->series;
        $this->rules = (string) $tournament->rules;
        $this->guest_info = (string) $tournament->guest_info;
        $this->categoryId ??= $tournament->categories()->value('id');
        $this->resetMatchForm();
        $this->loadCategoryState();
    }

    public function updatedCategoryId(): void
    {
        $this->resetMatchForm();
        $this->loadCategoryState();
    }

    public function saveInfo(): void
    {
        $this->validate([
            'series' => 'nullable|string|max:60',
            'rules' => 'nullable|string',
            'guest_info' => 'nullable|string',
            'image' => 'nullable|image|max:6144',
        ]);

        $this->tournament->update([
            'has_page' => $this->has_page,
            'series' => trim($this->series) ?: null,
            'rules' => $this->rules ?: null,
            'guest_info' => $this->guest_info ?: null,
        ] + ($this->image ? ['image_path' => $this->image->store('tournaments', 'public')] : []));

        $this->image = null;
        $this->flashMessage = 'Zapisano informacje o turnieju.';
    }

    public function addCategory(): void
    {
        $this->validate(['newCategory' => 'required|string|max:60']);

        $category = $this->tournament->categories()->create([
            'name' => trim($this->newCategory),
            'display_order' => $this->tournament->categories()->count(),
        ]);

        $this->newCategory = '';
        $this->categoryId = $category->id;
        $this->loadCategoryState();
    }

    public function deleteCategory(int $id): void
    {
        $this->tournament->categories()->whereKey($id)->delete();
        $this->categoryId = $this->tournament->categories()->value('id');
        $this->loadCategoryState();
    }

    public function addGroup(): void
    {
        $category = $this->category();
        $this->validate(['newGroup' => 'required|string|max:60']);

        $category->groups()->create(['name' => trim($this->newGroup), 'display_order' => $category->groups()->count()]);
        $this->newGroup = '';
    }

    public function deleteGroup(int $id): void
    {
        $this->category()->groups()->whereKey($id)->delete();
        $this->loadCategoryState();
    }

    /**
     * Mecze „każdy z każdym” w grupie (pomija pary, które już mają mecz).
     */
    public function generateGroupMatches(int $groupId): void
    {
        $group = $this->category()->groups()->with(['participants', 'matches'])->findOrFail($groupId);
        $ids = $group->participants->pluck('id')->values();
        $created = 0;

        foreach ($ids as $i => $homeId) {
            foreach ($ids->slice($i + 1) as $awayId) {
                $exists = $group->matches->contains(fn (TournamentMatch $match) => collect([$match->home_participant_id, $match->away_participant_id])->sort()->values()->all() === collect([$homeId, $awayId])->sort()->values()->all());

                if (! $exists) {
                    $group->matches()->create([
                        'tournament_category_id' => $group->tournament_category_id,
                        'stage' => TournamentMatch::STAGE_GROUP,
                        'home_participant_id' => $homeId,
                        'away_participant_id' => $awayId,
                    ]);
                    $created++;
                }
            }
        }

        $this->loadCategoryState();
        $this->flashMessage = $created ? "Dodano {$created} meczów w grupie {$group->name}." : 'Wszystkie mecze tej grupy już są.';
    }

    public function addParticipant(): void
    {
        $category = $this->category();
        $this->validate([
            'newParticipant' => 'nullable|string',
            'newParticipantName' => 'required_without:newParticipant|nullable|string|max:255',
            'newParticipantLogo' => 'nullable|image|max:2048',
        ], ['newParticipantName.required_without' => 'Wybierz drużynę z listy albo wpisz nazwę nowej.']);

        if ($this->newParticipant !== '') {
            [$type, $id] = explode(':', $this->newParticipant);
            $competitor = $type === 'team' ? Team::findOrFail((int) $id) : RivalTeam::findOrFail((int) $id);
        } else {
            $competitor = RivalTeam::create([
                'full_name' => trim($this->newParticipantName),
                'logo_path' => $this->newParticipantLogo?->store('rival-teams', 'public'),
            ]);
        }

        $category->participants()->create([
            'competitor_type' => $competitor instanceof Team ? 'team' : 'rival_team',
            'competitor_id' => $competitor->id,
        ]);

        $this->reset('newParticipant', 'newParticipantName', 'newParticipantLogo');
        $this->loadCategoryState();
    }

    public function saveParticipants(): void
    {
        $this->validate([
            'participants.*.display_name' => 'nullable|string|max:255',
            'participants.*.roster' => 'nullable|string|max:3000',
            'participants.*.final_position' => 'nullable|integer|min:1|max:99',
        ]);

        foreach ($this->category()->participants as $participant) {
            $row = $this->participants[$participant->id] ?? null;

            if ($row !== null) {
                $participant->update([
                    'tournament_group_id' => ($row['group'] ?? '') !== '' ? (int) $row['group'] : null,
                    'display_name' => trim($row['display_name'] ?? '') ?: null,
                    'roster' => trim($row['roster'] ?? '') ?: null,
                    'final_position' => ($row['final_position'] ?? '') !== '' ? (int) $row['final_position'] : null,
                ]);
            }
        }

        $this->flashMessage = 'Zapisano drużyny.';
    }

    public function deleteParticipant(int $id): void
    {
        $this->category()->participants()->whereKey($id)->delete();
        $this->loadCategoryState();
    }

    public function addMatch(): void
    {
        $category = $this->category();
        $data = $this->validate([
            'match.stage' => 'required|in:group,playoff,placement',
            'match.group' => 'nullable|required_if:match.stage,group|integer',
            'match.label' => 'nullable|string|max:80',
            'match.placement' => 'nullable|required_if:match.stage,placement|integer|min:1|max:99',
            'match.bracket_round' => 'nullable|required_if:match.stage,playoff|integer|min:1|max:9',
            'match.bracket_slot' => 'nullable|integer|min:1|max:64',
            'match.home' => 'nullable|integer',
            'match.away' => 'nullable|integer',
            'match.home_placeholder' => 'nullable|string|max:60',
            'match.away_placeholder' => 'nullable|string|max:60',
            'match.starts_at' => 'nullable|date',
            'match.court' => 'nullable|string|max:60',
        ], [
            'match.group.required_if' => 'Wybierz grupę.',
            'match.placement.required_if' => 'Podaj, o które miejsce jest mecz (np. 7 dla meczu o 7.-8. miejsce).',
            'match.bracket_round.required_if' => 'Podaj rundę drabinki (1 = pierwsza runda).',
        ])['match'];

        $participantIds = $category->participants()->pluck('id');

        $category->matches()->create([
            'stage' => $data['stage'],
            'tournament_group_id' => $data['stage'] === TournamentMatch::STAGE_GROUP ? (int) $data['group'] : null,
            'label' => trim((string) ($data['label'] ?? '')) ?: null,
            'placement' => $data['stage'] === TournamentMatch::STAGE_PLACEMENT ? (int) $data['placement'] : null,
            'bracket_round' => $data['stage'] === TournamentMatch::STAGE_PLAYOFF ? (int) $data['bracket_round'] : null,
            'bracket_slot' => $data['stage'] === TournamentMatch::STAGE_PLAYOFF && filled($data['bracket_slot'] ?? null) ? (int) $data['bracket_slot'] : null,
            'home_participant_id' => $participantIds->contains((int) ($data['home'] ?? 0)) ? (int) $data['home'] : null,
            'away_participant_id' => $participantIds->contains((int) ($data['away'] ?? 0)) ? (int) $data['away'] : null,
            'home_placeholder' => trim((string) ($data['home_placeholder'] ?? '')) ?: null,
            'away_placeholder' => trim((string) ($data['away_placeholder'] ?? '')) ?: null,
            'starts_at' => $data['starts_at'] ?: null,
            'court' => trim((string) ($data['court'] ?? '')) ?: null,
        ]);

        $this->resetMatchForm($data['stage']);
        $this->loadCategoryState();
        $this->flashMessage = 'Dodano mecz.';
    }

    /**
     * Wynik meczu wpisany jako sety, np. „25:20, 23:25, 15:12”, plus godzina i boisko.
     */
    public function saveResult(int $matchId): void
    {
        $match = $this->category()->matches()->findOrFail($matchId);
        $row = $this->results[$matchId] ?? [];
        $sets = self::parseSets((string) ($row['sets'] ?? ''), "results.{$matchId}.sets");

        $match->update([
            'sets_score' => $sets ?: null,
            'home_sets' => $sets ? collect($sets)->filter(fn (array $set) => $set[0] > $set[1])->count() : null,
            'away_sets' => $sets ? collect($sets)->filter(fn (array $set) => $set[1] > $set[0])->count() : null,
            'starts_at' => ($row['starts_at'] ?? '') ?: null,
            'court' => trim($row['court'] ?? '') ?: null,
        ]);

        $this->flashMessage = 'Zapisano: '.$match->fresh()->homeName().' – '.$match->awayName().'.';
    }

    public function setMatchTeams(int $matchId, string $side, string $participantId): void
    {
        $match = $this->category()->matches()->findOrFail($matchId);
        $column = $side === 'home' ? 'home_participant_id' : 'away_participant_id';
        $match->update([$column => $participantId !== '' && $this->category()->participants()->whereKey((int) $participantId)->exists() ? (int) $participantId : null]);
    }

    public function deleteMatch(int $id): void
    {
        $this->category()->matches()->whereKey($id)->delete();
        $this->loadCategoryState();
    }

    /**
     * @return array<int, array{0: int, 1: int}>
     */
    public static function parseSets(string $input, string $errorKey = 'sets'): array
    {
        if (trim($input) === '') {
            return [];
        }

        $sets = [];

        foreach (preg_split('/[,;\s]+/', trim($input)) as $chunk) {
            if (! preg_match('/^(\d{1,2})[:\-](\d{1,2})$/', $chunk, $points) || (int) $points[1] === (int) $points[2]) {
                throw ValidationException::withMessages([$errorKey => 'Wpisz sety jak „25:20, 23:25, 15:12” (bez remisów w secie).']);
            }

            $sets[] = [(int) $points[1], (int) $points[2]];
        }

        return $sets;
    }

    private function category(): TournamentCategory
    {
        return $this->tournament->categories()->findOrFail($this->categoryId);
    }

    private function resetMatchForm(string $stage = 'group'): void
    {
        $this->match = ['stage' => $stage, 'group' => '', 'label' => '', 'placement' => '', 'bracket_round' => '', 'bracket_slot' => '', 'home' => '', 'away' => '', 'home_placeholder' => '', 'away_placeholder' => '', 'starts_at' => $this->match['starts_at'] ?? '', 'court' => $this->match['court'] ?? ''];
    }

    private function loadCategoryState(): void
    {
        $category = $this->categoryId ? $this->tournament->categories()->with(['participants', 'matches'])->find($this->categoryId) : null;

        $this->participants = $category?->participants->mapWithKeys(fn (TournamentParticipant $participant) => [$participant->id => [
            'group' => (string) $participant->tournament_group_id,
            'display_name' => (string) $participant->display_name,
            'roster' => (string) $participant->roster,
            'final_position' => (string) $participant->final_position,
        ]])->all() ?? [];

        $this->results = $category?->matches->mapWithKeys(fn (TournamentMatch $match) => [$match->id => [
            'sets' => collect($match->sets_score ?? [])->map(fn ($set) => implode(':', (array) $set))->implode(', '),
            'starts_at' => (string) $match->starts_at?->format('Y-m-d\TH:i'),
            'court' => (string) $match->court,
        ]])->all() ?? [];
    }

    public function render(): View
    {
        $category = $this->categoryId
            ? $this->tournament->categories()->with(['groups.participants.competitor', 'participants.competitor', 'matches.home.competitor', 'matches.away.competitor', 'matches.group'])->find($this->categoryId)
            : null;

        $usedRivals = $category?->participants->where('competitor_type', 'rival_team')->pluck('competitor_id') ?? collect();
        $usedTeams = $category?->participants->where('competitor_type', 'team')->pluck('competitor_id') ?? collect();

        return view('livewire.admin.tournament-editor', [
            'categories' => $this->tournament->categories()->withCount(['participants', 'matches'])->get(),
            'category' => $category,
            'rivalOptions' => RivalTeam::with('club')->whereNotIn('id', $usedRivals)->orderBy('full_name')->get(['id', 'full_name', 'rival_club_id']),
            'teamOptions' => Team::whereNotIn('id', $usedTeams)->orderBy('display_order')->get(['id', 'name']),
        ]);
    }
}

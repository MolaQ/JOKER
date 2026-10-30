<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\RivalTeam;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentCategory;
use App\Models\TournamentMatch;
use App\Models\TournamentParticipant;
use App\Support\TournamentBracket;
use App\Support\TournamentTable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
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
    use ShowsToasts, WithFileUploads;

    public Tournament $tournament;

    #[Url(as: 'section')]
    public string $section = 'strona';

    #[Url(as: 'category')]
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

    public ?int $editingMatchId = null;

    /** @var array<string, mixed> */
    public array $editMatch = [];

    /** @var array<int, array{sets: string, starts_at: string, court: string}> */
    public array $results = [];

    /** Kategoria albo grupa, której nazwa jest właśnie edytowana („category:3”, „group:5”). */
    public string $renaming = '';

    /** @var array<int, string> */
    public array $categoryNames = [];

    /** @var array<int, string> */
    public array $groupNames = [];

    /** @var array<int, list<string>> Skład grup z reguł: „participant:4”, „group:2:1”, „winner:9”. */
    public array $groupSlots = [];

    /** @var array<int, array<int, string>> Reguły grup: miejsce w tabeli → miejsce w klasyfikacji. */
    public array $groupPlaces = [];

    /** @var array<string, string> Domyślna punktacja meczów grupowych w turnieju. */
    public array $scoring = [];

    /** @var array<int, array<string, mixed>> Własna punktacja grupy („custom” włącza ją, inaczej obowiązuje turniejowa). */
    public array $groupScoring = [];

    public function mount(Tournament $tournament): void
    {
        $this->tournament = $tournament;
        $this->has_page = $tournament->has_page;
        $this->series = (string) $tournament->series;
        $this->rules = (string) $tournament->rules;
        $this->guest_info = (string) $tournament->guest_info;
        $this->scoring = self::scoringState(TournamentTable::scoring(tournament: $tournament));
        $this->categoryId ??= $tournament->categories()->value('id');
        $this->resetMatchForm();
        $this->loadCategoryState();
    }

    /**
     * Ustawienia strony turnieju zapisują się same po zmianie pola.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['has_page', 'series', 'rules', 'guest_info', 'image'], true)) {
            $this->saveInfo();
        }
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
        $this->toast('Zapisano informacje o turnieju.');
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

    public function updatedScoring(): void
    {
        $this->validate(collect(TournamentTable::DEFAULT_SCORING)->mapWithKeys(fn ($default, string $field) => ["scoring.{$field}" => 'required|integer|min:0|max:20'])->all());
        $this->tournament->update(['scoring' => self::scoringFromState($this->scoring)]);
        $this->afterScoringChange('Zapisano punktację turnieju.');
    }

    public function updatedGroupScoring(mixed $value, string $key): void
    {
        $this->saveGroupScoring((int) explode('.', $key)[0]);
    }

    /**
     * Kryteria kolejności: „tournament” albo id grupy z własną punktacją; włącz/wyłącz albo przesuń w górę/dół.
     */
    public function toggleCriterion(string $target, string $criterion): void
    {
        $criteria = $this->criteriaFor($target);
        $criteria = in_array($criterion, $criteria, true) ? array_values(array_diff($criteria, [$criterion])) : [...$criteria, $criterion];
        $this->storeCriteria($target, $criteria ?: TournamentTable::DEFAULT_CRITERIA);
    }

    public function moveCriterion(string $target, string $criterion, int $direction): void
    {
        $criteria = $this->criteriaFor($target);
        $index = array_search($criterion, $criteria, true);
        $swap = $index === false ? null : $index + $direction;

        if ($swap !== null && isset($criteria[$swap])) {
            [$criteria[$index], $criteria[$swap]] = [$criteria[$swap], $criteria[$index]];
            $this->storeCriteria($target, $criteria);
        }
    }

    /**
     * Ręczna kolejność w tabeli grupy (np. po rozpisaniu małej tabeli): przesuwa drużynę o jedno miejsce.
     */
    public function moveTableRow(int $groupId, int $participantId, int $direction): void
    {
        $category = $this->category()->load(['groups.participants', 'participants', 'matches.group', 'tournament']);
        $group = $category->groups->firstWhere('id', $groupId) ?? abort(404);
        $order = TournamentBracket::for($category)->groupTable($group)->map(fn (array $row) => $row['participant']->id)->values()->all();
        $index = array_search($participantId, $order, true);
        $swap = $index === false ? null : $index + $direction;

        if ($swap === null || ! isset($order[$swap])) {
            return;
        }

        [$order[$index], $order[$swap]] = [$order[$swap], $order[$index]];
        $group->update(['manual_order' => $order]);
        $this->syncBracket();
        $this->toast('Ustawiono ręczną kolejność w '.$group->name.'.');
    }

    public function resetTableOrder(int $groupId): void
    {
        $this->category()->groups()->whereKey($groupId)->update(['manual_order' => null]);
        $this->syncBracket();
        $this->toast('Przywrócono kolejność według kryteriów.');
    }

    /**
     * @return list<string>
     */
    private function criteriaFor(string $target): array
    {
        return array_values($target === 'tournament' ? ($this->scoring['criteria'] ?? []) : ($this->groupScoring[(int) $target]['criteria'] ?? []));
    }

    /**
     * @param  list<string>  $criteria
     */
    private function storeCriteria(string $target, array $criteria): void
    {
        $criteria = array_values(array_intersect($criteria, array_keys(TournamentTable::CRITERIA)));

        if ($target === 'tournament') {
            $this->scoring['criteria'] = $criteria;
            $this->updatedScoring();

            return;
        }

        $this->groupScoring[(int) $target]['criteria'] = $criteria;
        $this->saveGroupScoring((int) $target);
    }

    private function saveGroupScoring(int $groupId): void
    {
        $row = $this->groupScoring[$groupId] ?? [];
        $this->category()->groups()->whereKey($groupId)->first()?->update(['scoring' => ! empty($row['custom']) ? self::scoringFromState($row) : null]);
        $this->afterScoringChange('Zapisano punktację grupy.');
    }

    private function afterScoringChange(string $message): void
    {
        foreach ($this->tournament->categories as $category) {
            TournamentBracket::sync($category);
        }

        $this->loadCategoryState();
        $this->toast($message);
    }

    /**
     * @param  array<string, mixed>  $scoring
     * @return array<string, mixed>
     */
    private static function scoringState(array $scoring): array
    {
        return collect($scoring)->except('criteria')->map(fn ($points) => (string) $points)->all() + ['criteria' => $scoring['criteria'] ?? TournamentTable::DEFAULT_CRITERIA];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private static function scoringFromState(array $state): array
    {
        return collect(TournamentTable::DEFAULT_SCORING)
            ->map(fn (int $default, string $field) => is_numeric($state[$field] ?? null) ? max(0, min(20, (int) $state[$field])) : $default)
            ->all() + ['criteria' => array_values(array_intersect((array) ($state['criteria'] ?? TournamentTable::DEFAULT_CRITERIA), array_keys(TournamentTable::CRITERIA)))];
    }

    public function updatedCategoryNames(string $value, string $id): void
    {
        $this->validate(["categoryNames.{$id}" => 'required|string|max:60']);
        $this->tournament->categories()->whereKey((int) $id)->update(['name' => trim($value)]);
        $this->toast('Zmieniono nazwę kategorii.');
    }

    public function updatedGroupNames(string $value, string $id): void
    {
        $this->validate(["groupNames.{$id}" => 'required|string|max:60']);
        $this->category()->groups()->whereKey((int) $id)->update(['name' => trim($value)]);
        $this->toast('Zmieniono nazwę grupy.');
    }

    /**
     * Skład grupy z reguł zapisuje się od razu; drużyny pojawią się w tabeli, gdy reguła się rozstrzygnie.
     */
    public function updatedGroupSlots(mixed $value, string $key): void
    {
        $this->saveGroupRules((int) explode('.', $key)[0]);
    }

    public function updatedGroupPlaces(mixed $value, string $key): void
    {
        $this->saveGroupRules((int) explode('.', $key)[0]);
    }

    public function addGroupSlot(int $groupId): void
    {
        $this->groupSlots[$groupId][] = '';
    }

    public function removeGroupSlot(int $groupId, int $index): void
    {
        unset($this->groupSlots[$groupId][$index]);
        $this->groupSlots[$groupId] = array_values($this->groupSlots[$groupId] ?? []);
        $this->saveGroupRules($groupId);
    }

    private function saveGroupRules(int $groupId): void
    {
        $category = $this->category()->load(['groups.participants', 'participants', 'matches.group']);
        $group = $category->groups->firstWhere('id', $groupId) ?? abort(404);
        $allowed = TournamentBracket::slotOptions($category, $group);

        $slots = collect($this->groupSlots[$groupId] ?? [])->filter(fn ($slot) => is_string($slot) && array_key_exists($slot, $allowed))->values()->all();
        $places = collect($this->groupPlaces[$groupId] ?? [])
            ->filter(fn ($place) => is_numeric($place) && (int) $place >= 1 && (int) $place <= 99)
            ->map(fn ($place) => (int) $place)
            ->all();

        $group->update(['slots' => $slots ?: null, 'final_places' => $places ?: null]);
        TournamentBracket::sync($category);
        $this->toast('Zapisano grupę '.$group->name.'.');
    }

    public function addGroup(): void
    {
        $category = $this->category();
        $this->validate(['newGroup' => 'required|string|max:60']);

        $category->groups()->create(['name' => trim($this->newGroup), 'display_order' => $category->groups()->count()]);
        $this->newGroup = '';
        $this->loadCategoryState();
    }

    public function deleteGroup(int $id): void
    {
        $category = $this->category();
        $category->matches()->where('tournament_group_id', $id)->delete();

        // Reguły „N. miejsce – usunięta grupa” w meczach tracą sens.
        foreach (['home_source', 'away_source'] as $column) {
            $category->matches()->where($column, 'like', "group:{$id}:%")->update([$column => null]);
        }

        $category->groups()->whereKey($id)->delete();
        $this->syncBracket();
    }

    /**
     * Mecze „każdy z każdym” w grupie (pomija pary, które już mają mecz).
     */
    public function generateGroupMatches(int $groupId): void
    {
        $category = $this->category()->load(['groups.participants', 'participants', 'matches.group']);
        $group = $category->groups->firstWhere('id', $groupId) ?? abort(404);
        $members = TournamentBracket::for($category)->members($group)->pluck('key')->unique()->values();
        $sideKey = fn (TournamentMatch $match, string $side) => $match->{$side.'_source'} ?? ($match->{$side.'_participant_id'} ? 'participant:'.$match->{$side.'_participant_id'} : null);
        $existing = $category->matches->where('tournament_group_id', $group->id)
            ->map(fn (TournamentMatch $match) => collect([$sideKey($match, 'home'), $sideKey($match, 'away')])->sort()->implode('|'));
        $side = fn (string $key) => str_starts_with($key, 'participant:') ? ['participant_id' => (int) substr($key, 12), 'source' => null] : ['participant_id' => null, 'source' => $key];
        $created = 0;

        foreach ($members as $i => $home) {
            foreach ($members->slice($i + 1) as $away) {
                if ($existing->contains(collect([$home, $away])->sort()->implode('|'))) {
                    continue;
                }

                $group->matches()->create([
                    'tournament_category_id' => $group->tournament_category_id,
                    'stage' => TournamentMatch::STAGE_GROUP,
                    'home_participant_id' => $side($home)['participant_id'],
                    'away_participant_id' => $side($away)['participant_id'],
                    'home_source' => $side($home)['source'],
                    'away_source' => $side($away)['source'],
                ]);
                $created++;
            }
        }

        $this->syncBracket();
        $this->toast($created ? "Dodano {$created} meczów w grupie {$group->name}." : 'Wszystkie mecze tej grupy już są.');
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

    public function updatedParticipants(): void
    {
        $this->saveParticipants();
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

        $this->toast('Zapisano drużyny.');
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
            'match.phase_name' => 'nullable|string|max:60',
            'match.decides_place' => 'nullable|integer|min:1|max:99',
            'match.placement' => 'nullable|required_if:match.stage,placement|integer|min:1|max:99',
            'match.bracket_round' => 'nullable|required_if:match.stage,playoff|integer|min:1|max:9',
            'match.bracket_slot' => 'nullable|integer|min:1|max:64',
            'match.home' => 'nullable|integer',
            'match.away' => 'nullable|integer',
            'match.home_placeholder' => 'nullable|string|max:60',
            'match.away_placeholder' => 'nullable|string|max:60',
            'match.home_source' => ['nullable', 'string', $this->sourceRule()],
            'match.away_source' => ['nullable', 'string', $this->sourceRule()],
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
            'phase_name' => $data['stage'] === TournamentMatch::STAGE_PLAYOFF ? (trim((string) ($data['phase_name'] ?? '')) ?: null) : null,
            'decides_place' => $data['stage'] === TournamentMatch::STAGE_PLAYOFF && filled($data['decides_place'] ?? null) ? (int) $data['decides_place'] : null,
            'placement' => match ($data['stage']) {
                TournamentMatch::STAGE_PLACEMENT => (int) $data['placement'],
                TournamentMatch::STAGE_PLAYOFF => filled($data['placement'] ?? null) && (int) $data['placement'] > 1 ? (int) $data['placement'] : null,
                default => null,
            },
            'bracket_round' => $data['stage'] === TournamentMatch::STAGE_PLAYOFF ? (int) $data['bracket_round'] : null,
            'bracket_slot' => $data['stage'] === TournamentMatch::STAGE_PLAYOFF && filled($data['bracket_slot'] ?? null) ? (int) $data['bracket_slot'] : null,
            'home_participant_id' => $participantIds->contains((int) ($data['home'] ?? 0)) ? (int) $data['home'] : null,
            'away_participant_id' => $participantIds->contains((int) ($data['away'] ?? 0)) ? (int) $data['away'] : null,
            'home_placeholder' => trim((string) ($data['home_placeholder'] ?? '')) ?: null,
            'away_placeholder' => trim((string) ($data['away_placeholder'] ?? '')) ?: null,
            'home_source' => ($data['home_source'] ?? '') ?: null,
            'away_source' => ($data['away_source'] ?? '') ?: null,
            'starts_at' => $data['starts_at'] ?: null,
            'court' => trim((string) ($data['court'] ?? '')) ?: null,
        ]);

        $this->resetMatchForm($data['stage']);
        $this->syncBracket();
        $this->toast('Dodano mecz.');
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
            'is_live' => (bool) ($row['live'] ?? false),
        ]);
        $this->syncBracket();

        $this->toast('Zapisano: '.$match->fresh()->homeName().' – '.$match->awayName().'.');
    }

    /**
     * Wynik, termin i boisko zapisują się same po wyjściu z pola, żeby zmiana nie przepadła bez kliknięcia „Zapisz”.
     */
    public function updatedResults(mixed $value, string $key): void
    {
        $this->saveResult((int) explode('.', $key)[0]);
    }

    /**
     * Otwiera edycję meczu: faza i pozycja w drabince, etykieta, miejsce, o które gra, drużyny.
     */
    public function startMatchEdit(int $matchId): void
    {
        $match = $this->category()->matches()->findOrFail($matchId);

        $this->editingMatchId = $match->id;
        $this->editMatch = [
            'number' => (string) $match->number,
            'label' => (string) $match->label,
            'stage' => $match->stage,
            'starts_at' => (string) $match->starts_at?->format('Y-m-d\TH:i'),
            'court' => (string) $match->court,
            'phase_name' => (string) $match->phase_name,
            'bracket_round' => (string) $match->bracket_round,
            'bracket_slot' => (string) $match->bracket_slot,
            'bracket_from' => $match->stage === TournamentMatch::STAGE_PLAYOFF ? (string) ($match->placement ?? '') : '',
            'placement' => $match->stage === TournamentMatch::STAGE_PLACEMENT ? (string) $match->placement : '',
            'decides_place' => (string) $match->decides_place,
            'group' => (string) $match->tournament_group_id,
            'home' => (string) $match->home_participant_id,
            'away' => (string) $match->away_participant_id,
            'home_placeholder' => (string) $match->home_placeholder,
            'away_placeholder' => (string) $match->away_placeholder,
            'home_source' => (string) $match->home_source,
            'away_source' => (string) $match->away_source,
        ];
        $this->resetErrorBag();
    }

    /**
     * Edycja meczu zapisuje się po każdej zmianie pola; panel zamyka przycisk „Gotowe”.
     */
    public function updatedEditMatch(): void
    {
        if ($this->editingMatchId !== null) {
            $this->saveMatchEdit(close: false);
        }
    }

    /**
     * „Gotowe”: zapisuje ewentualną ostatnią zmianę i zamyka edycję (przez metodę, bo zmiana pola
     * i zamknięcie przychodzą w jednym żądaniu, a zapis potrzebuje jeszcze numeru meczu).
     */
    public function finishMatchEdit(): void
    {
        $this->editingMatchId = null;
        $this->resetValidation();
    }

    public function saveMatchEdit(bool $close = true): void
    {
        $match = $this->category()->matches()->findOrFail($this->editingMatchId);
        $data = $this->validate([
            'editMatch.number' => ['required', 'integer', 'min:1', 'max:999', function (string $attribute, mixed $value, \Closure $fail) use ($match) {
                if ($this->category()->matches()->whereKeyNot($match->id)->where('number', (int) $value)->exists()) {
                    $fail('Ten numer ma już inny mecz w tej kategorii.');
                }
            }],
            'editMatch.label' => 'nullable|string|max:80',
            'editMatch.stage' => 'required|in:group,playoff,placement',
            'editMatch.starts_at' => 'nullable|date',
            'editMatch.court' => 'nullable|string|max:60',
            'editMatch.phase_name' => 'nullable|string|max:60',
            'editMatch.bracket_round' => 'nullable|integer|min:1|max:9',
            'editMatch.bracket_slot' => 'nullable|integer|min:1|max:64',
            'editMatch.bracket_from' => 'nullable|integer|min:1|max:99',
            'editMatch.placement' => 'nullable|integer|min:1|max:99',
            'editMatch.decides_place' => 'nullable|integer|min:1|max:99',
            'editMatch.group' => 'nullable|integer',
            'editMatch.home' => 'nullable|integer',
            'editMatch.away' => 'nullable|integer',
            'editMatch.home_placeholder' => 'nullable|string|max:60',
            'editMatch.away_placeholder' => 'nullable|string|max:60',
            'editMatch.home_source' => ['nullable', 'string', $this->sourceRule($match->id)],
            'editMatch.away_source' => ['nullable', 'string', $this->sourceRule($match->id)],
        ])['editMatch'];

        $participantIds = $this->category()->participants()->pluck('id');
        $number = fn (string $key): ?int => filled($data[$key] ?? null) ? (int) $data[$key] : null;
        $text = fn (string $key): ?string => trim((string) ($data[$key] ?? '')) ?: null;

        $stage = $data['stage'];
        $match->update([
            'number' => (int) $data['number'],
            'home_source' => ($data['home_source'] ?? '') ?: null,
            'away_source' => ($data['away_source'] ?? '') ?: null,
            'stage' => $stage,
            'label' => $text('label'),
            'starts_at' => $text('starts_at'),
            'court' => $text('court'),
            'home_participant_id' => $participantIds->contains($number('home')) ? $number('home') : null,
            'away_participant_id' => $participantIds->contains($number('away')) ? $number('away') : null,
            'home_placeholder' => $text('home_placeholder'),
            'away_placeholder' => $text('away_placeholder'),
            'tournament_group_id' => $stage === TournamentMatch::STAGE_GROUP ? ($number('group') ?? $match->tournament_group_id ?? $this->category()->groups()->value('id')) : null,
            'phase_name' => $stage === TournamentMatch::STAGE_PLAYOFF ? $text('phase_name') : null,
            'bracket_round' => $stage === TournamentMatch::STAGE_PLAYOFF ? ($number('bracket_round') ?? $match->bracket_round ?? 1) : null,
            'bracket_slot' => $stage === TournamentMatch::STAGE_PLAYOFF ? $number('bracket_slot') : null,
            'decides_place' => $stage === TournamentMatch::STAGE_PLAYOFF ? $number('decides_place') : null,
            'placement' => match ($stage) {
                TournamentMatch::STAGE_PLAYOFF => ($number('bracket_from') ?? 1) > 1 ? $number('bracket_from') : null,
                TournamentMatch::STAGE_PLACEMENT => $number('placement') ?? $match->placement ?? 1,
                default => null,
            },
        ]);
        $this->syncBracket();

        if ($close) {
            $this->editingMatchId = null;
        }
        $this->toast('Zapisano mecz: '.$match->fresh()->title().'.');
    }

    /**
     * Zamiana gospodarza z gościem: drużyny (albo ich miejsca w drabince) i wynik setów przechodzą na drugą stronę.
     */
    public function swapMatchSides(int $matchId): void
    {
        $match = $this->category()->matches()->findOrFail($matchId);
        $sets = collect($match->sets_score ?? [])->map(fn ($set) => array_reverse(array_values((array) $set)))->all();

        $match->update([
            'home_participant_id' => $match->away_participant_id,
            'away_participant_id' => $match->home_participant_id,
            'home_placeholder' => $match->away_placeholder,
            'away_placeholder' => $match->home_placeholder,
            'home_source' => $match->away_source,
            'away_source' => $match->home_source,
            'home_sets' => $match->away_sets,
            'away_sets' => $match->home_sets,
            'sets_score' => $sets ?: null,
        ]);
        $this->results[$match->id]['sets'] = collect($sets)->map(fn ($set) => implode(':', $set))->implode(', ');
        $this->syncBracket();

        $this->toast('Zamieniono strony: '.$match->fresh()->homeName().' – '.$match->awayName().'.');
    }

    public function setMatchTeams(int $matchId, string $side, string $participantId): void
    {
        $match = $this->category()->matches()->findOrFail($matchId);
        $column = $side === 'home' ? 'home_participant_id' : 'away_participant_id';
        $match->update([$column => $participantId !== '' && $this->category()->participants()->whereKey((int) $participantId)->exists() ? (int) $participantId : null]);
    }

    /**
     * Numery meczów 1..N według harmonogramu: termin, potem boisko; mecze bez terminu na końcu w dotychczasowej kolejności.
     * Reguły drabinki wskazują mecze po id, więc zmiana numerów ich nie psuje.
     */
    public function renumberMatchesBySchedule(): void
    {
        $matches = $this->category()->matches()->get()
            ->sortBy(fn (TournamentMatch $match) => [$match->starts_at === null ? 1 : 0, $match->starts_at?->timestamp ?? 0, (string) $match->court, $match->number ?? PHP_INT_MAX, $match->id])
            ->values();

        DB::transaction(fn () => $matches->each(fn (TournamentMatch $match, int $index) => $match->update(['number' => $index + 1])));

        $this->toast('Ponumerowano mecze według harmonogramu.');
    }

    /**
     * Przeciągnięcie meczu w obrębie fazy: mecze tej fazy dostają po kolei te same numery, które miały, w nowym porządku.
     * Numery meczów z innych faz zostają bez zmian.
     */
    public function moveMatch(int|string $matchId, int $position): void
    {
        $match = $this->category()->matches()->findOrFail((int) $matchId);
        $stageMatches = $this->category()->matches()->where('stage', $match->stage)->get()
            ->sortBy(fn (TournamentMatch $item) => [$item->number ?? PHP_INT_MAX, $item->id])->values();
        $numbers = $stageMatches->pluck('number')->map(fn ($number, int $index) => $number ?? $index + 1)->sort()->values();

        $ordered = $stageMatches->reject(fn (TournamentMatch $item) => $item->id === $match->id)->values();
        $ordered->splice(max(0, min($position, $ordered->count())), 0, [$match]);

        DB::transaction(fn () => $ordered->each(fn (TournamentMatch $item, int $index) => $item->number === $numbers[$index] || $item->update(['number' => $numbers[$index]])));
    }

    public function moveMatchBy(int $matchId, int $offset): void
    {
        $match = $this->category()->matches()->findOrFail($matchId);
        $position = $this->category()->matches()->where('stage', $match->stage)->get()
            ->sortBy(fn (TournamentMatch $item) => [$item->number ?? PHP_INT_MAX, $item->id])->values()
            ->search(fn (TournamentMatch $item) => $item->id === $match->id);

        $this->moveMatch($match->id, max(0, $position + $offset));
    }

    public function deleteMatch(int $id): void
    {
        $category = $this->category();
        $category->matches()->whereKey($id)->delete();

        foreach (['home_source', 'away_source'] as $column) {
            $category->matches()->whereIn($column, ["winner:{$id}", "loser:{$id}"])->update([$column => null]);
        }

        $this->syncBracket();
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

    /**
     * Reguła „skąd drużyna” musi być jedną z opcji tej kategorii (i nie wskazywać na ten sam mecz).
     */
    private function sourceRule(?int $matchId = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($matchId) {
            if (filled($value) && ! array_key_exists($value, TournamentBracket::sourceOptions($this->category()->load(['groups.participants', 'matches.group']), $matchId))) {
                $fail('Wybierz regułę z listy.');
            }
        };
    }

    /**
     * Po każdej zmianie meczów wpisuje drużyny wynikające z reguł i odświeża formularz wyników.
     */
    private function syncBracket(): void
    {
        TournamentBracket::sync($this->category());
        $this->loadCategoryState();
    }

    private function category(): TournamentCategory
    {
        return $this->tournament->categories()->findOrFail($this->categoryId);
    }

    private function resetMatchForm(string $stage = 'group'): void
    {
        $this->match = ['stage' => $stage, 'group' => '', 'label' => '', 'phase_name' => $this->match['phase_name'] ?? '', 'decides_place' => '', 'placement' => '', 'bracket_round' => '', 'bracket_slot' => '', 'home' => '', 'away' => '', 'home_placeholder' => '', 'away_placeholder' => '', 'home_source' => '', 'away_source' => '', 'starts_at' => $this->match['starts_at'] ?? '', 'court' => $this->match['court'] ?? ''];
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

        $this->categoryNames = $this->tournament->categories()->pluck('name', 'id')->all();
        $groups = $category?->groups()->get() ?? collect();
        $this->groupNames = $groups->pluck('name', 'id')->all();
        $this->groupSlots = $groups->mapWithKeys(fn ($group) => [$group->id => $group->slots ?? []])->all();
        $this->scoring = self::scoringState(TournamentTable::scoring(tournament: $this->tournament->fresh()));
        $this->groupScoring = $groups->mapWithKeys(fn ($group) => [$group->id => ['custom' => $group->scoring !== null] + self::scoringState(TournamentTable::scoring($group, $this->tournament))])->all();
        $this->groupPlaces = $groups->mapWithKeys(fn ($group) => [$group->id => collect($group->final_places ?? [])->map(fn ($place) => (string) $place)->all()])->all();

        $this->results = $category?->matches->mapWithKeys(fn (TournamentMatch $match) => [$match->id => [
            'sets' => collect($match->sets_score ?? [])->map(fn ($set) => implode(':', (array) $set))->implode(', '),
            'starts_at' => (string) $match->starts_at?->format('Y-m-d\TH:i'),
            'court' => (string) $match->court,
            'live' => $match->is_live,
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

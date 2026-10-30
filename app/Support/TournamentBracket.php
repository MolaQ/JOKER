<?php

namespace App\Support;

use App\Models\TournamentCategory;
use App\Models\TournamentGroup;
use App\Models\TournamentMatch;
use App\Models\TournamentParticipant;
use Illuminate\Support\Collection;

/**
 * Drabinka kategorii liczona z reguł meczów: „1. miejsce grupy A”, „Zwycięzca #7”, „Przegrany #7”.
 *
 * Dopóki grupa trwa, drużyna z reguły grupowej jest prognozą (aktualna kolejność tabeli).
 * Gdy grupa jest rozegrana albo mecz źródłowy ma wynik, drużyna jest pewna i sync() wpisuje ją do meczu.
 */
class TournamentBracket
{
    public const CONFIRMED = 'confirmed';

    public const PROJECTED = 'projected';

    public const PENDING = 'pending';

    /** @var Collection<int, TournamentMatch> */
    private Collection $matches;

    /** @var Collection<int, TournamentGroup> */
    private Collection $groups;

    /** @var array<int, Collection<int, array<string, mixed>>> */
    private array $tables = [];

    /** @var array<int, Collection<int, array<string, mixed>>> */
    private array $members = [];

    /** Zabezpieczenie przed regułami w kółko (grupa A z miejsc grupy B, która bierze z A). */
    private int $depth = 0;

    public function __construct(private TournamentCategory $category)
    {
        $this->matches = $category->matches->keyBy('id');
        $this->groups = $category->groups->keyBy('id');
    }

    public static function for(TournamentCategory $category): self
    {
        return new self($category);
    }

    /**
     * Wpisuje do meczów drużyny wynikające z reguł (tylko pewne; prognoza zostaje pustym miejscem).
     * Powtarza, bo zwycięzca meczu #9 może zależeć od drużyny wpisanej chwilę wcześniej do #9.
     */
    public static function sync(TournamentCategory $category): void
    {
        for ($pass = 0; $pass < 10; $pass++) {
            $category->load(['groups.participants', 'participants', 'matches.home', 'matches.away']);
            $bracket = new self($category);
            $changed = false;

            foreach ($category->matches as $match) {
                foreach (['home', 'away'] as $side) {
                    if ($match->{$side.'_source'} === null) {
                        continue;
                    }

                    $resolved = $bracket->resolveSource($match->{$side.'_source'});
                    $id = $resolved['state'] === self::CONFIRMED ? $resolved['participant']?->id : null;

                    if ($match->{$side.'_participant_id'} !== $id) {
                        $match->update([$side.'_participant_id' => $id]);
                        $changed = true;
                    }
                }
            }

            if (! $changed) {
                return;
            }
        }
    }

    public function groupFinished(TournamentGroup $group): bool
    {
        $matches = $this->matches->where('tournament_group_id', $group->id);

        return $matches->isNotEmpty() && $matches->every(fn (TournamentMatch $match) => $match->isFinished());
    }

    /**
     * @return array{played: int, total: int, finished: bool}
     */
    public function groupProgress(TournamentGroup $group): array
    {
        $matches = $this->matches->where('tournament_group_id', $group->id);
        $played = $matches->filter(fn (TournamentMatch $match) => $match->isFinished())->count();

        return ['played' => $played, 'total' => $matches->count(), 'finished' => $matches->isNotEmpty() && $played === $matches->count()];
    }

    /**
     * Drużyna na danej stronie meczu do pokazania: pewna, prognozowana albo opis, na kogo czekamy.
     *
     * @return array{participant: ?TournamentParticipant, state: string, chip: ?string, text: string}
     */
    public function side(TournamentMatch $match, string $side): array
    {
        $source = $match->{$side.'_source'};
        $participant = $match->{$side};

        if ($source === null) {
            return [
                'participant' => $participant,
                'state' => $participant ? self::CONFIRMED : self::PENDING,
                'chip' => null,
                'text' => $participant?->name() ?? ($match->{$side.'_placeholder'} ?: __('Do ustalenia')),
            ];
        }

        $resolved = $this->resolveSource($source);

        // Drużyna wpisana w meczu jest ważniejsza od prognozy (np. tuż po zapisaniu wyniku grupy).
        if ($participant !== null) {
            $resolved = ['participant' => $participant, 'state' => self::CONFIRMED] + $resolved;
        }

        return $resolved + ['text' => $resolved['participant']?->name() ?? $resolved['description']];
    }

    /**
     * Dokąd trafiają zwycięzca i przegrany meczu: inne mecze albo miejsce w klasyfikacji.
     *
     * @return array{winner: ?string, loser: ?string}
     */
    public function destinations(TournamentMatch $match): array
    {
        $target = function (string $kind) use ($match): ?string {
            $next = $this->matches->first(fn (TournamentMatch $other) => in_array("{$kind}:{$match->id}", [$other->home_source, $other->away_source], true));

            return $next ? '#'.$next->number : null;
        };

        $place = $match->placeAtStake();

        return [
            'winner' => $target('winner') ?? ($place ? __(':place. miejsce', ['place' => $place]) : null),
            'loser' => $target('loser') ?? ($place ? __(':place. miejsce', ['place' => $place + 1]) : null),
        ];
    }

    /**
     * Miejsca 1, 2, 3… w klasyfikacji: drużyna (gdy już wiadomo) albo „Zwycięzca #11”.
     *
     * @return Collection<int, array{position: int, participant: ?TournamentParticipant, text: string}>
     */
    public function standings(): Collection
    {
        $known = TournamentTable::finalStandings($this->category)->keyBy('position');
        $places = [];

        foreach ($this->matches as $match) {
            $place = $match->placeAtStake();

            if ($place !== null) {
                $places[$place] = __('Zwycięzca meczu #:number', ['number' => $match->number]);
                $places[$place + 1] = __('Przegrany meczu #:number', ['number' => $match->number]);
            }
        }

        // Reguły grup: miejsce w tabeli grupy → miejsce w klasyfikacji (pewne dopiero po rozegraniu grupy).
        foreach ($this->groups as $group) {
            $finished = $this->groupProgress($group)['finished'];

            foreach ($group->final_places ?? [] as $position => $place) {
                if (filled($place)) {
                    $row = $finished ? $this->groupTable($group)->firstWhere('position', (int) $position) : null;
                    $places[(int) $place] = $row['participant'] ?? __(':place. miejsce – :group', ['place' => $position, 'group' => $group->name]);
                }
            }
        }

        foreach ($known as $position => $row) {
            $places[$position] = $row['participant'];
        }

        ksort($places);

        return collect($places)->map(fn ($entry, int $position) => [
            'position' => $position,
            'participant' => $entry instanceof TournamentParticipant ? $entry : null,
            'text' => $entry instanceof TournamentParticipant ? $entry->name() : $entry,
        ])->values();
    }

    /**
     * Opcje reguł do wyboru w panelu: miejsca w grupach i zwycięzcy/przegrani innych meczów.
     *
     * @return array<string, string>
     */
    public static function sourceOptions(TournamentCategory $category, ?int $exceptMatchId = null): array
    {
        $options = [];
        $bracket = new self($category);

        foreach ($category->groups as $group) {
            $size = max($bracket->members($group)->count(), 1);

            for ($place = 1; $place <= $size; $place++) {
                $options["group:{$group->id}:{$place}"] = "{$place}. miejsce – {$group->name}";
            }
        }

        foreach ($category->matches->where('stage', '!=', TournamentMatch::STAGE_GROUP)->sortBy('number') as $match) {
            if ($match->id === $exceptMatchId) {
                continue;
            }

            $options["winner:{$match->id}"] = "Zwycięzca #{$match->number} ({$match->title()})";
            $options["loser:{$match->id}"] = "Przegrany #{$match->number} ({$match->title()})";
        }

        return $options;
    }

    /**
     * @return array{participant: ?TournamentParticipant, state: string, chip: ?string, description: string}
     */
    public function resolveSource(string $source): array
    {
        $parts = explode(':', $source);

        if ($parts[0] === 'group' && count($parts) === 3) {
            $group = $this->groups->get((int) $parts[1]);
            $place = (int) $parts[2];
            $letter = $group ? self::groupLetter($group) : '?';
            $description = __(':place. miejsce – :group', ['place' => $place, 'group' => $group?->name ?? '?']);

            if ($group === null || $this->depth > 5) {
                return ['participant' => null, 'state' => self::PENDING, 'chip' => "{$place}{$letter}", 'description' => $description];
            }

            $this->depth++;
            $progress = $this->groupProgress($group);
            $row = $progress['played'] > 0 ? $this->table($group)->firstWhere('position', $place) : null;
            $this->depth--;

            return [
                'participant' => $row['participant'] ?? null,
                'state' => $row === null ? self::PENDING : ($progress['finished'] ? self::CONFIRMED : self::PROJECTED),
                'chip' => "{$place}{$letter}",
                'description' => $description,
            ];
        }

        // Pozycja w grupie z szablonu, zanim przypiszesz drużyny („slot:{grupa}:{nr}”).
        if ($parts[0] === 'slot') {
            $group = $this->groups->get((int) ($parts[1] ?? 0));
            $letter = $group ? self::groupLetter($group) : '?';

            return ['participant' => null, 'state' => self::PENDING, 'chip' => $letter.($parts[2] ?? '?'), 'description' => __('Drużyna :number – :group', ['number' => $parts[2] ?? '?', 'group' => $group?->name ?? '?'])];
        }

        $from = $this->matches->get((int) ($parts[1] ?? 0));
        $winner = $parts[0] === 'winner';
        $number = $from?->number ?? '?';
        $description = $winner ? __('Zwycięzca meczu #:number', ['number' => $number]) : __('Przegrany meczu #:number', ['number' => $number]);
        $participant = $from?->isFinished() ? ($winner ? $from->winner() : $from->loser()) : null;

        return ['participant' => $participant, 'state' => $participant ? self::CONFIRMED : self::PENDING, 'chip' => "#{$number}", 'description' => $description];
    }

    /**
     * Litera grupy do plakietki: „Grupa A” → „A”, inna nazwa → pierwsza litera.
     */
    public static function groupLetter(TournamentGroup $group): string
    {
        $name = trim(preg_replace('/^grupa\s+/iu', '', $group->name));

        return mb_strlen($name) <= 2 ? mb_strtoupper($name) : mb_strtoupper(mb_substr($name, 0, 1));
    }

    /**
     * Skład grupy: drużyny przypisane w zakładce „Drużyny” i miejsca z reguł (drużyna albo „1. miejsce – Grupa A”).
     *
     * @return Collection<int, array{key: string, participant: ?TournamentParticipant, state: string, chip: ?string, text: string}>
     */
    public function members(TournamentGroup $group): Collection
    {
        return $this->members[$group->id] ??= $this->category->participants
            ->where('tournament_group_id', $group->id)
            ->map(fn (TournamentParticipant $participant) => ['key' => "participant:{$participant->id}", 'participant' => $participant, 'state' => self::CONFIRMED, 'chip' => null, 'text' => $participant->name()])
            ->concat(collect($group->slots ?? [])->filter()->map(fn (string $slot) => $this->slot($slot)))
            ->values();
    }

    /**
     * Tabela grupy z jej meczów (drużyny z reguł liczą się, gdy już wiadomo, kto to jest).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function groupTable(TournamentGroup $group): Collection
    {
        return $this->tables[$group->id] ??= TournamentTable::for(
            $this->members($group)->pluck('participant')->filter()->unique('id')->values(),
            $this->matches->where('tournament_group_id', $group->id),
            TournamentTable::scoring($group, $this->category->tournament),
            $group->manual_order ?? [],
        );
    }

    /**
     * Opcje składu grupy w panelu: konkretne drużyny i miejsca z innych grup / wyniki meczów.
     *
     * @return array<string, string>
     */
    public static function slotOptions(TournamentCategory $category, TournamentGroup $group): array
    {
        $options = $category->participants->mapWithKeys(fn (TournamentParticipant $participant) => ["participant:{$participant->id}" => $participant->name()])->all();

        foreach (self::sourceOptions($category) as $key => $name) {
            if (! str_starts_with($key, "group:{$group->id}:")) {
                $options[$key] = $name;
            }
        }

        return $options;
    }

    /**
     * @return array{key: string, participant: ?TournamentParticipant, state: string, chip: ?string, text: string}
     */
    private function slot(string $slot): array
    {
        if (str_starts_with($slot, 'participant:')) {
            $participant = $this->category->participants->firstWhere('id', (int) substr($slot, 12));

            return ['key' => $slot, 'participant' => $participant, 'state' => $participant ? self::CONFIRMED : self::PENDING, 'chip' => null, 'text' => $participant?->name() ?? '?'];
        }

        $resolved = $this->resolveSource($slot);

        return ['key' => $slot, 'participant' => $resolved['participant'], 'state' => $resolved['state'], 'chip' => $resolved['chip'], 'text' => $resolved['participant']?->name() ?? $resolved['description']];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function table(TournamentGroup $group): Collection
    {
        return $this->groupTable($group);
    }
}

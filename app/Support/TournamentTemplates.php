<?php

namespace App\Support;

use App\Models\TournamentCategory;
use App\Models\TournamentGroup;
use App\Models\TournamentMatch;
use App\Models\TournamentParticipant;
use App\Models\TournamentTemplate;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Szablony kategorii: zapis struktury (grupy, mecze, drabinka, dni i godziny) bez konkretnych drużyn
 * i wstawienie jej do nowej kategorii.
 *
 * W szablonie drużyna z grupy to jej pozycja na liście grupy („slot:{grupa}:{nr}”). Po wstawieniu
 * mecze czekają na drużyny; gdy przypiszesz drużyny do grupy, fill() wpisuje je w mecze
 * (pierwsza drużyna grupy na liście = pozycja 1 itd.).
 */
class TournamentTemplates
{
    public static function capture(TournamentCategory $category, string $name, ?string $description, bool $withTeams = false): TournamentTemplate
    {
        $category->loadMissing(['groups', 'participants', 'matches', 'tournament']);
        $groups = $category->groups->sortBy([['display_order', 'asc'], ['id', 'asc']])->values();
        $groupIndex = $groups->pluck('id')->flip();
        $positions = [];

        foreach ($groups as $g => $group) {
            foreach (self::groupParticipants($category, $group) as $p => $participant) {
                $positions[$participant->id] = [$g, $p + 1];
            }
        }

        $matches = $category->matches->sortBy([['number', 'asc'], ['id', 'asc']])->values();
        $matchIndex = $matches->pluck('id')->flip();
        $start = $category->tournament->start_date->copy()->startOfDay();

        $encode = function (?string $source) use ($groupIndex, $matchIndex): ?string {
            if ($source === null) {
                return null;
            }

            $parts = explode(':', $source);

            return match ($parts[0]) {
                'group' => isset($groupIndex[(int) ($parts[1] ?? 0)]) ? 'group:'.$groupIndex[(int) $parts[1]].':'.($parts[2] ?? 1) : null,
                'winner', 'loser' => isset($matchIndex[(int) ($parts[1] ?? 0)]) ? $parts[0].':'.$matchIndex[(int) $parts[1]] : null,
                default => null,
            };
        };

        $side = function (TournamentMatch $match, string $side) use ($encode, $positions): ?string {
            if ($match->{$side.'_source'}) {
                return $encode($match->{$side.'_source'});
            }

            $id = $match->{$side.'_participant_id'};

            return $id && isset($positions[$id]) ? 'slot:'.$positions[$id][0].':'.$positions[$id][1] : null;
        };

        $structure = [
            'version' => 1,
            'match_interval' => $category->match_interval,
            'color' => $category->color,
            'days' => collect(TournamentSchedule::days($category))->map(fn (array $day) => [
                'offset' => (int) $start->diffInDays(Carbon::parse($day['date'])->startOfDay()),
                'time' => $day['time'] ?? '09:00',
                'from' => $day['from'] ?? '',
            ])->all(),
            'groups' => $groups->map(fn (TournamentGroup $group) => [
                'name' => $group->name,
                'court' => $group->court,
                'scoring' => $group->scoring,
                'final_places' => $group->final_places,
                'size' => self::groupParticipants($category, $group)->count(),
                'slots' => collect($group->slots ?? [])->map(fn (?string $slot) => $slot ? $encode($slot) : null)->filter()->values()->all(),
            ])->all(),
            'matches' => $matches->map(fn (TournamentMatch $match) => [
                'group' => $match->tournament_group_id !== null ? ($groupIndex[$match->tournament_group_id] ?? null) : null,
                'number' => $match->number,
                'stage' => $match->stage,
                'label' => $match->label,
                'phase_name' => $match->phase_name,
                'placement' => $match->placement,
                'decides_place' => $match->decides_place,
                'bracket_round' => $match->bracket_round,
                'bracket_slot' => $match->bracket_slot,
                'home' => $side($match, 'home'),
                'away' => $side($match, 'away'),
                'home_placeholder' => $match->home_placeholder,
                'away_placeholder' => $match->away_placeholder,
                'court' => $match->court,
                'day' => ($match->planned_starts_at ?? $match->starts_at) ? (int) $start->diffInDays(($match->planned_starts_at ?? $match->starts_at)->copy()->startOfDay()) : null,
                'time' => ($match->planned_starts_at ?? $match->starts_at)?->format('H:i'),
            ])->all(),
        ];

        if ($withTeams) {
            // Drużyny w kolejności grup i pozycji, żeby po wstawieniu zajęły te same miejsca w meczach.
            $structure['teams'] = $category->participants
                ->sortBy(fn (TournamentParticipant $participant) => [$positions[$participant->id] ?? [PHP_INT_MAX, 0], $participant->id])
                ->map(fn (TournamentParticipant $participant) => [
                    'group' => $positions[$participant->id][0] ?? null,
                    'type' => $participant->competitor_type,
                    'id' => $participant->competitor_id,
                    'display_name' => $participant->display_name,
                    'roster' => $participant->roster,
                ])->values()->all();
        }

        return TournamentTemplate::create(['name' => $name, 'description' => $description, 'structure' => $structure]);
    }

    /**
     * Wstawia szablon do kategorii: grupy, mecze z godzinami (od pierwszego dnia turnieju) i ustawienia harmonogramu.
     */
    public static function apply(TournamentTemplate $template, TournamentCategory $category): void
    {
        $category->refresh();
        $structure = $template->structure ?? [];
        $start = $category->tournament->start_date->copy()->startOfDay();
        $order = (int) $category->groups()->max('display_order');

        $groups = collect($structure['groups'] ?? [])->values()->map(fn (array $group, int $i) => $category->groups()->create([
            'name' => $group['name'],
            'court' => $group['court'] ?? null,
            'scoring' => $group['scoring'] ?? null,
            'final_places' => $group['final_places'] ?? null,
            'display_order' => $order + $i + 1,
        ]));

        $category->update([
            'match_interval' => $structure['match_interval'] ?? $category->match_interval,
            'color' => $category->color ?? ($structure['color'] ?? null),
            'schedule_days' => collect($structure['days'] ?? [])->map(fn (array $day) => [
                'date' => $start->copy()->addDays((int) $day['offset'])->toDateString(),
                'time' => $day['time'] ?? '09:00',
                'from' => (string) ($day['from'] ?? ''),
            ])->values()->all() ?: null,
        ]);

        foreach ($structure['teams'] ?? [] as $team) {
            $model = Relation::getMorphedModel((string) $team['type']);

            if (! $model || ! class_exists($model) || ! $model::whereKey($team['id'])->exists()
                || $category->participants()->where('competitor_type', $team['type'])->where('competitor_id', $team['id'])->exists()) {
                continue;
            }

            $category->participants()->create([
                'tournament_group_id' => $team['group'] !== null ? $groups->get($team['group'])?->id : null,
                'competitor_type' => $team['type'],
                'competitor_id' => $team['id'],
                'display_name' => $team['display_name'] ?? null,
                'roster' => $team['roster'] ?? null,
            ]);
        }

        // Najpierw mecze, potem odwołania „zwycięzca/przegrany meczu” (mogą wskazywać mecz z dalszej części listy).
        $created = collect($structure['matches'] ?? [])->values()->map(fn (array $match) => $category->matches()->create([
            'tournament_group_id' => $match['group'] !== null ? $groups->get($match['group'])?->id : null,
            'number' => $match['number'] ?? null,
            'stage' => $match['stage'],
            'label' => $match['label'] ?? null,
            'phase_name' => $match['phase_name'] ?? null,
            'placement' => $match['placement'] ?? null,
            'decides_place' => $match['decides_place'] ?? null,
            'bracket_round' => $match['bracket_round'] ?? null,
            'bracket_slot' => $match['bracket_slot'] ?? null,
            'home_placeholder' => $match['home_placeholder'] ?? null,
            'away_placeholder' => $match['away_placeholder'] ?? null,
            'court' => $match['court'] ?? null,
            'starts_at' => $match['day'] !== null && $match['time'] ? $start->copy()->addDays((int) $match['day'])->setTimeFromTimeString($match['time']) : null,
        ]));

        $decode = function (?string $value) use ($groups, $created): ?string {
            if ($value === null) {
                return null;
            }

            $parts = explode(':', $value);

            return match ($parts[0]) {
                'group' => ($group = $groups->get((int) $parts[1])) ? "group:{$group->id}:{$parts[2]}" : null,
                'slot' => ($group = $groups->get((int) $parts[1])) ? "slot:{$group->id}:{$parts[2]}" : null,
                'winner', 'loser' => ($match = $created->get((int) $parts[1])) ? "{$parts[0]}:{$match->id}" : null,
                default => null,
            };
        };

        foreach ($created as $i => $match) {
            $spec = $structure['matches'][$i];
            $match->update(['home_source' => $decode($spec['home'] ?? null), 'away_source' => $decode($spec['away'] ?? null)]);
        }

        foreach ($groups as $i => $group) {
            $group->update(['slots' => collect($structure['groups'][$i]['slots'] ?? [])->map($decode)->filter()->values()->all() ?: null]);
        }

        self::fill($category->fresh());
    }

    /**
     * Wpisuje drużyny w mecze z szablonu („slot:{grupa}:{nr}”), gdy grupa ma już tyle drużyn.
     */
    public static function fill(TournamentCategory $category): int
    {
        $category->loadMissing(['groups', 'participants']);
        $filled = 0;

        foreach ($category->matches()->where(fn ($query) => $query->where('home_source', 'like', 'slot:%')->orWhere('away_source', 'like', 'slot:%'))->get() as $match) {
            $changes = [];

            foreach (['home', 'away'] as $side) {
                $source = $match->{$side.'_source'};

                if (! $source || ! str_starts_with($source, 'slot:')) {
                    continue;
                }

                [, $groupId, $position] = explode(':', $source) + [null, 0, 0];
                $group = $category->groups->firstWhere('id', (int) $groupId);
                $participant = $group ? self::groupParticipants($category, $group)->get((int) $position - 1) : null;

                if ($participant) {
                    $changes[$side.'_participant_id'] = $participant->id;
                    $changes[$side.'_source'] = null;
                }
            }

            if ($changes !== []) {
                $match->update($changes);
                $filled++;
            }
        }

        return $filled;
    }

    /**
     * Drużyny grupy w stałej kolejności (kolejność dodania do turnieju).
     *
     * @return Collection<int, TournamentParticipant>
     */
    public static function groupParticipants(TournamentCategory $category, TournamentGroup $group): Collection
    {
        return $category->participants->where('tournament_group_id', $group->id)->sortBy('id')->values();
    }
}

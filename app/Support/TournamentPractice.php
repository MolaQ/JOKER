<?php

namespace App\Support;

use App\Models\Tournament;
use App\Models\TournamentCategory;
use App\Models\TournamentMatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Turniej ćwiczebny dla wpisujących wyniki: kopia kategorii, grup, drużyn, meczów, hal i wpisujących
 * prawdziwego turnieju, przesunięta na dziś, bez wyników. Nie ma publicznej strony (has_page = false),
 * więc kibice go nie widzą i nie dostają z niego powiadomień; widzą go organizatorzy i wpisujący.
 */
class TournamentPractice
{
    public static function create(Tournament $source): Tournament
    {
        return DB::transaction(function () use ($source) {
            $source->load(['categories.groups', 'categories.participants', 'categories.matches', 'scorers']);
            $shift = (int) $source->start_date->copy()->startOfDay()->diffInDays(today(), false);
            $move = fn (?Carbon $date) => $date?->copy()->addDays($shift);

            $practice = Tournament::create([
                'name' => 'Ćwiczenie: '.$source->name,
                'edition' => null,
                'start_date' => today(),
                'end_date' => $move($source->end_date) ?? today(),
                'venue' => $source->venue,
                'city' => $source->city,
                'is_organizer' => $source->is_organizer,
                'scoring' => $source->scoring,
                'halls' => $source->halls,
                'main_off' => $source->main_off,
                'has_page' => false,
                'practice_of_id' => $source->id,
            ]);
            $practice->scorers()->sync($source->scorers->modelKeys());

            foreach ($source->categories as $category) {
                self::copyCategory($category, $practice, $move);
            }

            return $practice;
        });
    }

    /**
     * Ćwiczenie od nowa: bez wyników i „trwa”, godziny z harmonogramu (sprzed wpisania godzin rozpoczęcia).
     */
    public static function reset(Tournament $practice): void
    {
        abort_unless($practice->isPractice(), 404);

        DB::transaction(function () use ($practice) {
            foreach ($practice->categories as $category) {
                $category->matches()->whereNotNull('planned_starts_at')->update(['starts_at' => DB::raw('planned_starts_at')]);
                $category->matches()->update(['sets_score' => null, 'home_sets' => null, 'away_sets' => null, 'is_live' => false, 'planned_starts_at' => null, 'mvp' => null]);
                $category->matches()->where(fn ($query) => $query->whereNotNull('home_source'))->update(['home_participant_id' => null]);
                $category->matches()->where(fn ($query) => $query->whereNotNull('away_source'))->update(['away_participant_id' => null]);
                $category->participants()->update(['final_position' => null]);
                $category->groups()->update(['manual_order' => null]);
                TournamentBracket::sync($category->fresh());
            }
        });
    }

    /**
     * @param  callable(?Carbon): ?Carbon  $move
     */
    private static function copyCategory(TournamentCategory $category, Tournament $practice, callable $move): void
    {
        $copy = $practice->categories()->create([
            'name' => $category->name,
            'display_order' => $category->display_order,
            'match_interval' => $category->match_interval,
            'color' => $category->color,
            'hall' => $category->hall,
            'schedule_days' => collect($category->schedule_days ?? [])->map(fn (array $day) => ['date' => $move(isset($day['date']) ? Carbon::parse($day['date']) : null)?->toDateString()] + $day)->all() ?: null,
        ]);

        $groups = [];
        foreach ($category->groups as $group) {
            $groups[$group->id] = $copy->groups()->create($group->only(['name', 'display_order', 'final_places', 'scoring', 'court']))->id;
        }

        $participants = [];
        foreach ($category->participants as $participant) {
            $participants[$participant->id] = $copy->participants()->create([
                'tournament_group_id' => $groups[$participant->tournament_group_id] ?? null,
            ] + $participant->only(['competitor_type', 'competitor_id', 'display_name', 'roster']))->id;
        }

        $matches = [];
        foreach ($category->matches as $match) {
            $starts = $match->planned_starts_at ?? $match->starts_at;
            $matches[$match->id] = $copy->matches()->create([
                'tournament_group_id' => $groups[$match->tournament_group_id] ?? null,
                'home_participant_id' => $match->home_source ? null : ($participants[$match->home_participant_id] ?? null),
                'away_participant_id' => $match->away_source ? null : ($participants[$match->away_participant_id] ?? null),
                'starts_at' => $move($starts),
            ] + $match->only(['stage', 'label', 'placement', 'bracket_round', 'bracket_slot', 'home_placeholder', 'away_placeholder', 'court', 'phase_name', 'decides_place', 'number']))->id;
        }

        // Odwołania „1. miejsce grupy”, „zwycięzca meczu”, „drużyna” wskazują teraz grupy, mecze i drużyny kopii.
        $remap = function (?string $value) use ($groups, $matches, $participants): ?string {
            if ($value === null) {
                return null;
            }

            $parts = explode(':', $value);

            return match ($parts[0]) {
                'group' => isset($groups[(int) ($parts[1] ?? 0)]) ? 'group:'.$groups[(int) $parts[1]].':'.($parts[2] ?? 1) : null,
                'slot' => isset($groups[(int) ($parts[1] ?? 0)]) ? 'slot:'.$groups[(int) $parts[1]].':'.($parts[2] ?? 1) : null,
                'winner', 'loser' => isset($matches[(int) ($parts[1] ?? 0)]) ? $parts[0].':'.$matches[(int) $parts[1]] : null,
                'participant' => isset($participants[(int) ($parts[1] ?? 0)]) ? 'participant:'.$participants[(int) $parts[1]] : null,
                default => null,
            };
        };

        foreach ($category->matches as $match) {
            if ($match->home_source || $match->away_source) {
                TournamentMatch::whereKey($matches[$match->id])->update(['home_source' => $remap($match->home_source), 'away_source' => $remap($match->away_source)]);
            }
        }

        foreach ($category->groups as $group) {
            if ($group->slots) {
                $copy->groups()->whereKey($groups[$group->id])->update(['slots' => json_encode(collect($group->slots)->map($remap)->filter()->values()->all())]);
            }
        }

        TournamentBracket::sync($copy->fresh());
    }
}

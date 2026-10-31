<?php

namespace App\Support;

use App\Models\Tournament;
use App\Models\TournamentCategory;
use App\Models\TournamentMatch;
use App\Models\TrainingSession;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Godziny meczów turnieju na sektorach: układanie według numerów co ustalony odstęp
 * i przesuwanie kolejnych meczów na sektorze, gdy jeden zacznie się później albo wcześniej.
 */
class TournamentSchedule
{
    /**
     * Sektor meczu: court1–court3 i main dla nazw z ustawień, inaczej sam wpisany tekst.
     */
    public static function sector(?string $court): ?string
    {
        $court = trim((string) $court);

        return $court === '' ? null : (TrainingSession::venueForCourt($court) ?? mb_strtolower($court));
    }

    /**
     * Główne boisko zajmuje wszystkie trzy sektory, więc dzieli kolejkę z każdym z nich.
     */
    public static function sameSector(?string $a, ?string $b): bool
    {
        $sectors = ['court1', 'court2', 'court3', 'main'];

        return $a !== null && $b !== null && ($a === $b || (in_array($a, $sectors, true) && in_array($b, $sectors, true) && ($a === 'main' || $b === 'main')));
    }

    /**
     * Mecz, który trwa albo ma już wynik, zostaje o swojej godzinie.
     */
    public static function locked(TournamentMatch $match): bool
    {
        return $match->is_live || $match->home_sets !== null || $match->away_sets !== null;
    }

    /**
     * Godzina z planu po zmianie terminu: w dniu meczu zmiana to opóźnienie (plan zostaje),
     * wcześniej to po prostu nowy plan.
     */
    public static function plannedAfterChange(TournamentMatch $match, ?CarbonInterface $old, ?CarbonInterface $new): ?CarbonInterface
    {
        if ($new === null) {
            return null;
        }

        return $old !== null && $old->isSameDay(now()) ? ($match->planned_starts_at ?? $old) : $new;
    }

    /**
     * Przesuwa kolejne mecze (według godziny) na tym samym sektorze i tego samego dnia o tyle,
     * o ile przesunął się podany mecz. Obejmuje wszystkie kategorie turnieju, bo dzielą halę.
     *
     * @return array<int, array{starts_at: ?string, planned_starts_at: ?string}> poprzednie godziny przesuniętych meczów
     */
    public static function shiftFollowing(TournamentMatch $match, CarbonInterface $old): array
    {
        $sector = self::sector($match->court);

        if ($sector === null || $match->starts_at === null) {
            return [];
        }

        $minutes = (int) $old->diffInMinutes($match->starts_at, false);

        if ($minutes === 0) {
            return [];
        }

        $following = TournamentMatch::query()
            ->whereIn('tournament_category_id', $match->category->tournament->categories()->select('id'))
            ->whereKeyNot($match->id)
            ->where('starts_at', '>=', $old)
            ->where('starts_at', '<', $old->copy()->startOfDay()->addDay())
            ->get()
            ->filter(fn (TournamentMatch $other) => self::sameSector($sector, self::sector($other->court)) && ! self::locked($other));

        $before = [];

        foreach ($following as $other) {
            $before[$other->id] = self::snapshot($other);
            $starts = $other->starts_at->copy()->addMinutes($minutes);
            $other->update([
                'starts_at' => $starts,
                'planned_starts_at' => self::plannedAfterChange($other, $other->starts_at, $starts),
            ]);
        }

        return $before;
    }

    /**
     * Mecz przeszedł na inny sektor: na starym sektorze kolejne mecze tego dnia zajmują zwolnione miejsce (wcześniej o odstęp),
     * a na nowym kolejne mecze ustępują mu miejsca, jeśli się z nim nakładają.
     *
     * @return array<int, array{starts_at: ?string, planned_starts_at: ?string, court: ?string}> poprzednie godziny przesuniętych meczów
     */
    public static function shiftAfterSectorChange(TournamentMatch $match, ?string $oldCourt, int $interval): array
    {
        $old = self::sector($oldCourt);
        $new = self::sector($match->court);

        if ($match->starts_at === null || $old === $new) {
            return [];
        }

        $day = TournamentMatch::query()
            ->whereIn('tournament_category_id', $match->category->tournament->categories()->select('id'))
            ->whereKeyNot($match->id)
            ->whereBetween('starts_at', [$match->starts_at->copy()->startOfDay(), $match->starts_at->copy()->endOfDay()])
            ->orderBy('starts_at')
            ->get()
            ->reject(fn (TournamentMatch $other) => self::locked($other));
        $before = [];
        $shift = function (TournamentMatch $other, int $minutes) use (&$before) {
            $before[$other->id] ??= self::snapshot($other);
            $starts = $other->starts_at->copy()->addMinutes($minutes);
            $other->update(['starts_at' => $starts, 'planned_starts_at' => self::plannedAfterChange($other, $other->starts_at, $starts)]);
        };

        if ($old !== null && ! self::sameSector($old, $new)) {
            $day->filter(fn (TournamentMatch $other) => self::sameSector($old, self::sector($other->court)) && $other->starts_at->gt($match->starts_at))
                ->each(fn (TournamentMatch $other) => $shift($other, -$interval));
        }

        if ($new !== null) {
            $onNew = $day->filter(fn (TournamentMatch $other) => self::sameSector($new, self::sector($other->court)));
            $end = $match->starts_at->copy()->addMinutes($interval);
            $conflict = $onNew->first(fn (TournamentMatch $other) => $other->starts_at->gte($match->starts_at) && $other->starts_at->lt($end));

            if ($conflict) {
                $minutes = (int) $conflict->starts_at->diffInMinutes($end);
                $onNew->filter(fn (TournamentMatch $other) => $other->starts_at->gte($conflict->starts_at))
                    ->each(fn (TournamentMatch $other) => $shift($other, $minutes));
            }
        }

        return $before;
    }

    /**
     * Obłożenie sektorów hali w kolejnych dniach turnieju: mecze wszystkich kategorii z godziną i sektorem.
     * Mecz trwa tyle, ile odstęp w jego kategorii; mecze nakładające się na tej samej części hali są oznaczone.
     *
     * @return Collection<string, array{start: Carbon, end: Carbon, sectors: Collection<string, Collection<int, array{match: TournamentMatch, starts: Carbon, ends: Carbon, conflict: bool}>>}>
     */
    public static function occupancy(Tournament $tournament): Collection
    {
        $order = ['court3' => 0, 'court2' => 1, 'court1' => 2, 'main' => 3];
        $blocks = TournamentMatch::with(['category', 'group', 'home.competitor', 'away.competitor'])
            ->whereIn('tournament_category_id', $tournament->categories()->select('id'))
            ->whereNotNull('starts_at')
            ->get()
            ->filter(fn (TournamentMatch $match) => self::sector($match->court) !== null)
            ->map(fn (TournamentMatch $match) => [
                'match' => $match,
                'sector' => self::sector($match->court),
                'starts' => $match->starts_at,
                'ends' => $match->starts_at->copy()->addMinutes((int) ($match->category->match_interval ?? 90)),
            ])
            ->values();

        $blocks = $blocks->map(fn (array $block) => $block + [
            'conflict' => $blocks->contains(fn (array $other) => $other['match']->id !== $block['match']->id
                && array_intersect(SectorOccupancy::physical($block['sector']), SectorOccupancy::physical($other['sector'])) !== []
                && $other['starts']->lt($block['ends']) && $other['ends']->gt($block['starts'])),
        ]);

        return $blocks->groupBy(fn (array $block) => $block['starts']->toDateString())->sortKeys()
            ->map(fn (Collection $day) => [
                'start' => $day->min('starts')->copy()->startOfHour(),
                'end' => $day->max('ends'),
                // Trzy sektory hali zawsze widoczne (wolny sektor też jest informacją), inne miejsca tylko gdy są w nich mecze.
                'sectors' => collect(['court3', 'court2', 'court1'])->mapWithKeys(fn (string $hall) => [TrainingSession::venueNames()[$hall] => collect()])
                    ->merge($day->groupBy(fn (array $block) => TrainingSession::courtLabel($block['match']->court))->map(fn (Collection $sector) => $sector->sortBy('starts')->values()))
                    ->sortBy(fn (Collection $sector, string $name) => [$order[TrainingSession::venueForCourt($name) ?? ''] ?? 9, $name]),
            ]);
    }

    /**
     * Dni turnieju w kategorii: zapisane albo po jednym na każdy dzień turnieju, od 9:00.
     * Pierwszy dzień zaczyna się od pierwszego meczu, kolejne od najniższego numeru meczu, który już ma ten dzień.
     *
     * @return list<array{date: string, time: string, from: string}>
     */
    public static function days(TournamentCategory $category): array
    {
        if (filled($category->schedule_days)) {
            return array_values($category->schedule_days);
        }

        $tournament = $category->tournament;
        $start = $tournament->start_date->copy();
        $end = $tournament->end_date && $tournament->end_date->gte($start) ? $tournament->end_date : $start;
        $matches = $category->matches()->get();
        $days = [];

        for ($date = $start->copy(); $date->lte($end) && count($days) < 7; $date->addDay()) {
            $dayMatches = $matches->filter(fn (TournamentMatch $match) => $match->starts_at?->isSameDay($date));
            $days[] = [
                'date' => $date->toDateString(),
                'time' => $dayMatches->min('starts_at')?->format('H:i') ?? '09:00',
                'from' => $days === [] ? '1' : (string) ($dayMatches->min('number') ?? ''),
            ];
        }

        return $days;
    }

    /**
     * Układa godziny meczów kategorii według numerów. Każdy dzień zaczyna się na każdym sektorze o godzinie rozpoczęcia dnia,
     * kolejne mecze idą co odstęp. Mecz bez sektora dostaje sektor swojej grupy, a gdy grupa go nie ma, sektor hali,
     * który najwcześniej się zwalnia. Mecze innych kategorii na tych samych sektorach zajmują swoje miejsce,
     * więc nic się nie nakłada. Mecz, który trwa albo ma wynik, zostaje o swojej godzinie.
     *
     * @param  list<array{date: string, time: string, from: string|int}>  $days
     * @return array{before: array<int, array{starts_at: ?string, planned_starts_at: ?string, court: ?string}>, assigned: int}
     */
    public static function arrange(TournamentCategory $category, array $days, int $interval): array
    {
        $matches = $category->matches()->with('group')->get()->sortBy(fn (TournamentMatch $match) => [$match->number ?? PHP_INT_MAX, $match->id]);
        $days = collect($days)->filter(fn (array $day) => filled($day['from'] ?? null))->sortBy(fn (array $day) => (int) $day['from'])->values();
        $dayOf = fn (TournamentMatch $match) => $days->filter(fn (array $day) => (int) $day['from'] <= (int) $match->number)->keys()->last() ?? 0;
        $halls = ['court1', 'court2', 'court3'];
        $names = TrainingSession::venueNames();
        $busy = new SectorOccupancy;

        TournamentMatch::with('category')
            ->whereIn('tournament_category_id', $category->tournament->categories()->whereKeyNot($category->id)->select('id'))
            ->whereNotNull('starts_at')
            ->get()
            ->each(fn (TournamentMatch $other) => $busy->add(self::sector($other->court), $other->starts_at->timestamp, (int) ($other->category->match_interval ?? 90)));

        $before = [];
        $assigned = 0;
        $cursor = [];

        foreach ($matches as $match) {
            $day = $days[$dayOf($match)] ?? null;

            if ($day === null) {
                continue;
            }

            $dayStart = Carbon::parse($day['date'].' '.$day['time'])->timestamp;
            $key = self::sector($match->court) ?? self::sector($match->group?->court);
            $from = fn (string $sector) => max([$dayStart, ...array_map(fn ($hall) => $cursor[$day['date']][$hall] ?? $dayStart, SectorOccupancy::physical($sector))]);

            if (self::locked($match)) {
                if ($match->starts_at && $key) {
                    $busy->add($key, $match->starts_at->timestamp, $interval);
                }

                continue;
            }

            $court = $match->court;

            if ($key === null) {
                $key = collect($halls)->sortBy(fn (string $hall) => $busy->earliest($hall, $from($hall), $interval))->first();
                $court = $names[$key];
                $assigned++;
            } elseif (blank($court)) {
                $court = $match->group->court;
            }

            $starts = $busy->earliest($key, $from($key), $interval);
            $busy->add($key, $starts, $interval);

            foreach (SectorOccupancy::physical($key) as $hall) {
                $cursor[$day['date']][$hall] = $starts + $interval * 60;
            }

            $before[$match->id] = self::snapshot($match);
            $match->update(['starts_at' => Carbon::createFromTimestamp($starts, config('app.timezone')), 'planned_starts_at' => Carbon::createFromTimestamp($starts, config('app.timezone')), 'court' => $court]);
        }

        return ['before' => $before, 'assigned' => $assigned];
    }

    /**
     * @return array{starts_at: ?string, planned_starts_at: ?string, court: ?string}
     */
    public static function snapshot(TournamentMatch $match): array
    {
        return [
            'starts_at' => $match->starts_at?->format('Y-m-d H:i:s'),
            'planned_starts_at' => $match->planned_starts_at?->format('Y-m-d H:i:s'),
            'court' => $match->court,
        ];
    }

    /**
     * Odstęp „1:30” albo „90” w minutach.
     */
    public static function parseInterval(string $value): ?int
    {
        $value = trim($value);

        return match (true) {
            (bool) preg_match('/^(\d{1,2}):([0-5]\d)$/', $value, $parts) => (int) $parts[1] * 60 + (int) $parts[2],
            (bool) preg_match('/^\d{1,3}$/', $value) => (int) $value,
            default => null,
        };
    }

    public static function formatInterval(int $minutes): string
    {
        return intdiv($minutes, 60).':'.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT);
    }
}

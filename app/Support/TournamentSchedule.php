<?php

namespace App\Support;

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
     * Na każdym sektorze mecze kategorii po kolei według numerów: pierwszy o podanej godzinie, każdy następny co odstęp.
     * Mecz, który trwa albo ma wynik, zachowuje godzinę, ale zajmuje swoje miejsce w kolejce.
     *
     * @return array{before: array<int, array{starts_at: ?string, planned_starts_at: ?string}>, skipped: int}
     */
    public static function arrange(TournamentCategory $category, CarbonInterface $first, int $interval): array
    {
        $matches = $category->matches()->get()->sortBy(fn (TournamentMatch $match) => [$match->number ?? PHP_INT_MAX, $match->id]);
        $before = [];

        $matches->filter(fn (TournamentMatch $match) => self::sector($match->court) !== null)
            ->groupBy(fn (TournamentMatch $match) => self::sector($match->court))
            ->each(function (Collection $sectorMatches) use ($first, $interval, &$before) {
                $starts = Carbon::instance($first);

                foreach ($sectorMatches as $match) {
                    if (! self::locked($match)) {
                        $before[$match->id] = self::snapshot($match);
                        $match->update(['starts_at' => $starts, 'planned_starts_at' => $starts]);
                    }

                    $starts = $starts->copy()->addMinutes($interval);
                }
            });

        return ['before' => $before, 'skipped' => $matches->filter(fn (TournamentMatch $match) => self::sector($match->court) === null)->count()];
    }

    /**
     * @return array{starts_at: ?string, planned_starts_at: ?string}
     */
    public static function snapshot(TournamentMatch $match): array
    {
        return [
            'starts_at' => $match->starts_at?->format('Y-m-d H:i:s'),
            'planned_starts_at' => $match->planned_starts_at?->format('Y-m-d H:i:s'),
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

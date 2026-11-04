<?php

namespace App\Support;

use App\Models\Team;
use App\Models\TrainingSession;
use Illuminate\Support\Collection;

/**
 * Tydzień treningów na jednej tablicy: wiersz na dzień, w dniu pasy sektorów (III, II, I) i siłowni, czas biegnie w poziomie.
 * Godziny, w których w całym tygodniu nikt nie trenuje (np. 12–16), są ściśnięte do wąskiej przerwy, żeby oś mieściła się na telefonie.
 * Każda drużyna ma własny „klucz” do filtrowania i wygląd: kolor kategorii wiekowej, a druga drużyna tej samej kategorii (np. Kadeci II) kreskowanie.
 */
class TrainingWeek
{
    /** Pasy w dniu: sektory jak w hali, potem siłownia. */
    public const LANES = ['court3', 'court2', 'court1', 'gym'];

    /** Szerokość przerwy na osi w minutach („ściśnięte” puste godziny). */
    public const GAP = 24;

    /**
     * @param  Collection<int, TrainingSession>  $sessions
     * @return array{groups: Collection<string, array<string, mixed>>, days: Collection<int, array<string, mixed>>, axis: array<string, mixed>, hours: array<string, float>}
     */
    public static function build(Collection $sessions): array
    {
        $groups = self::groups($sessions);
        $axis = self::axis($sessions);

        $days = TrainingOccupancy::days($sessions)->map(function (array $day, int $weekday) use ($groups, $axis) {
            $block = function (array $block, int $lanes, int $offset) use ($groups, $axis) {
                /** @var TrainingSession $session */
                $session = $block['session'];
                $group = $groups[self::key($session)];

                return [
                    'session' => $session,
                    'key' => $group['key'],
                    'color' => $group['color'],
                    'hatch' => $group['hatch'],
                    'x' => self::position($axis, $block['from']),
                    'w' => self::position($axis, $block['to']) - self::position($axis, $block['from']),
                    // pionowo: procent wysokości pasów sektorów (3) albo siłowni (1); TrainingOccupancy liczy to jako „lewo/szerokość”
                    'y' => $block['left'],
                    'h' => $block['width'],
                    'lanes' => $lanes,
                    'offset' => $offset,
                    'conflict' => $block['conflict'],
                ];
            };

            return [
                'weekday' => $weekday,
                'courts' => array_map(fn ($b) => $block($b, 3, 0), $day['courts']),
                'gym' => array_map(fn ($b) => $block($b, 1, 3), $day['gym']),
                'keys' => collect($day['courts'])->merge($day['gym'])->map(fn ($b) => self::key($b['session']))->unique()->values()->all(),
            ];
        });

        return ['groups' => $groups, 'days' => $days, 'axis' => $axis, 'hours' => self::hours($sessions)];
    }

    /**
     * Klucz grupy do filtrów: drużyna albo cała minisiatkówka.
     */
    public static function key(TrainingSession $session): string
    {
        return $session->isMini() ? 'mini' : 'team-'.$session->team_id;
    }

    /**
     * Grupy w kolejności drużyn: nazwa, adres w filtrze (?team=slug), kolor i kreskowanie dla kolejnej drużyny tej samej kategorii.
     *
     * @param  Collection<int, TrainingSession>  $sessions
     * @return Collection<string, array{key: string, slug: string, name: string, color: string, hatch: bool, url: ?string}>
     */
    public static function groups(Collection $sessions): Collection
    {
        $teams = Team::whereIn('id', $sessions->pluck('team_id')->filter()->unique())->orderBy('display_order')->orderBy('id')->get();
        $seen = [];

        $groups = $teams->mapWithKeys(function (Team $team) use ($sessions, &$seen) {
            $session = $sessions->firstWhere('team_id', $team->id);
            $age = $session->ageGroups()[0];
            $hatch = isset($seen[$age]);
            $seen[$age] = true;

            return ['team-'.$team->id => [
                'key' => 'team-'.$team->id,
                'slug' => $team->slug,
                'name' => $team->name,
                'color' => AgeColors::color($age),
                'hatch' => $hatch,
                'url' => route('teams.show', $team),
            ]];
        });

        if ($mini = $sessions->first(fn (TrainingSession $session) => $session->isMini())) {
            $groups->put('mini', [
                'key' => 'mini',
                'slug' => 'minisiatkowka',
                'name' => __('Minisiatkówka'),
                'color' => $mini->colors()[0],
                'hatch' => false,
                'url' => route('mini.index'),
            ]);
        }

        return $groups;
    }

    /**
     * Oś czasu wspólna dla całego tygodnia: odcinki pełnych godzin, w których ktoś trenuje, i przerwy między nimi.
     *
     * @param  Collection<int, TrainingSession>  $sessions
     * @return array{segments: list<array{0: int, 1: int}>, total: int}
     */
    public static function axis(Collection $sessions): array
    {
        $hours = [];
        foreach ($sessions as $session) {
            $from = self::minutes($session->starts_at);
            $to = self::minutes($session->ends_at);
            for ($hour = intdiv($from, 60); $hour * 60 < $to; $hour++) {
                $hours[$hour] = true;
            }
        }
        ksort($hours);

        $segments = [];
        foreach (array_keys($hours) as $hour) {
            $last = array_key_last($segments);
            if ($last !== null && $segments[$last][1] === $hour * 60) {
                $segments[$last][1] += 60;
            } else {
                $segments[] = [$hour * 60, $hour * 60 + 60];
            }
        }

        $total = array_sum(array_map(fn ($segment) => $segment[1] - $segment[0], $segments)) + max(0, count($segments) - 1) * self::GAP;

        return ['segments' => $segments, 'total' => max(1, $total)];
    }

    /**
     * Minuta dnia → procent szerokości osi (z pominięciem ściśniętych przerw).
     *
     * @param  array{segments: list<array{0: int, 1: int}>, total: int}  $axis
     */
    public static function position(array $axis, int $minute): float
    {
        $offset = 0;
        foreach ($axis['segments'] as $segment) {
            if ($minute <= $segment[1]) {
                return round(($offset + max(0, $minute - $segment[0])) / $axis['total'] * 100, 3);
            }
            $offset += $segment[1] - $segment[0] + self::GAP;
        }

        return 100.0;
    }

    /**
     * Ile godzin w tygodniu każdy sektor i siłownia są zajęte (nakładające się zajęcia liczone raz, główne boisko zajmuje wszystkie trzy sektory).
     *
     * @param  Collection<int, TrainingSession>  $sessions
     * @return array<string, float>
     */
    public static function hours(Collection $sessions): array
    {
        $result = [];
        foreach (self::LANES as $lane) {
            $minutes = 0;
            foreach ($sessions->groupBy('weekday') as $day) {
                $intervals = $day
                    ->filter(fn (TrainingSession $session) => $session->venue === $lane || ($session->venue === 'main' && $lane !== 'gym'))
                    ->map(fn (TrainingSession $session) => [self::minutes($session->starts_at), self::minutes($session->ends_at)])
                    ->sortBy(0)->values();
                $end = -1;
                foreach ($intervals as [$from, $to]) {
                    $minutes += max(0, $to - max($from, $end));
                    $end = max($end, $to);
                }
            }
            $result[$lane] = $minutes / 60;
        }

        return $result;
    }

    public static function minutes(?string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr((string) $time, 0, 5)) + [0, 0]);

        return $hours * 60 + $minutes;
    }
}

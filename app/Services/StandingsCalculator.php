<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\LeagueStanding;
use Illuminate\Support\Collection;

/**
 * Automatyczne przeliczanie tabeli ligowej na podstawie zakończonych meczów
 * przypisanych do danych rozgrywek (Competition). Wpisy oznaczone jako
 * `is_manual_override` nie są nadpisywane (np. kary, walkowery).
 */
class StandingsCalculator
{
    public static function recalculate(Competition $competition): void
    {
        $games = $competition->games()
            ->where('status', 'finished')
            ->whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->get();

        /** @var array<string, array<string, mixed>> $stats */
        $stats = [];

        $ensure = function (string $type, int $id) use (&$stats): string {
            $key = "{$type}:{$id}";

            $stats[$key] ??= [
                'competitor_type' => $type,
                'competitor_id' => $id,
                'played' => 0,
                'won' => 0,
                'lost' => 0,
                'sets_won' => 0,
                'sets_lost' => 0,
                'points_for' => 0,
                'points_against' => 0,
                'points' => 0,
            ];

            return $key;
        };

        foreach ($games as $game) {
            $result = $game->ourSetsResult();

            if ($result === null) {
                continue;
            }

            [$ourSets, $oppSets] = $result;
            [$ourSmall, $oppSmall] = self::smallPoints($game);

            $ourKey = $ensure('team', $game->team_id);
            self::accumulate($stats[$ourKey], $ourSets, $oppSets, $ourSmall, $oppSmall, $competition);

            if ($game->opponent_team_id) {
                $oppKey = $ensure('rival_team', $game->opponent_team_id);
                self::accumulate($stats[$oppKey], $oppSets, $ourSets, $oppSmall, $ourSmall, $competition);
            }
        }

        $existing = LeagueStanding::where('competition_id', $competition->id)
            ->get()
            ->keyBy(fn (LeagueStanding $standing) => "{$standing->competitor_type}:{$standing->competitor_id}");

        // Drużyny dodane do rozgrywek, ale bez rozegranych meczów - wyzeruj (chyba że ręczna korekta).
        foreach ($existing as $key => $row) {
            if (! isset($stats[$key]) && ! $row->is_manual_override) {
                $stats[$key] = [
                    'competitor_type' => $row->competitor_type,
                    'competitor_id' => $row->competitor_id,
                    'played' => 0,
                    'won' => 0,
                    'lost' => 0,
                    'sets_won' => 0,
                    'sets_lost' => 0,
                    'points_for' => 0,
                    'points_against' => 0,
                    'points' => 0,
                ];
            }
        }

        $rows = collect($stats)->map(function (array $data, string $key) use ($existing, $competition) {
            $row = $existing->get($key);

            if ($row && $row->is_manual_override) {
                return $row;
            }

            $attributes = array_merge($data, [
                'competition_id' => $competition->id,
                'season_id' => $competition->season_id,
                'team_id' => $data['competitor_type'] === 'team' ? $data['competitor_id'] : null,
                'is_manual_override' => false,
            ]);

            if ($row) {
                $row->fill($attributes);
                $row->save();

                return $row;
            }

            return LeagueStanding::create($attributes + ['position' => 0]);
        })->values();

        foreach ($existing as $row) {
            if (! $rows->contains('id', $row->id)) {
                $rows->push($row);
            }
        }

        self::reorder($competition, $rows);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function smallPoints($game): array
    {
        if (is_null($game->home_points) || is_null($game->away_points)) {
            return [0, 0];
        }

        return $game->is_home
            ? [$game->home_points, $game->away_points]
            : [$game->away_points, $game->home_points];
    }

    /**
     * @param  array<string, mixed>  $bucket
     */
    private static function accumulate(array &$bucket, int $setsFor, int $setsAgainst, int $pointsFor, int $pointsAgainst, Competition $competition): void
    {
        $bucket['played']++;
        $bucket['sets_won'] += $setsFor;
        $bucket['sets_lost'] += $setsAgainst;
        $bucket['points_for'] += $pointsFor;
        $bucket['points_against'] += $pointsAgainst;
        $bucket['points'] += $competition->pointsForSetResult($setsFor, $setsAgainst);

        if ($setsFor > $setsAgainst) {
            $bucket['won']++;
        } else {
            $bucket['lost']++;
        }
    }

    private static function reorder(Competition $competition, Collection $rows): void
    {
        $criteria = $competition->sortCriteria();

        $sorted = $rows->sort(function (LeagueStanding $a, LeagueStanding $b) use ($criteria) {
            foreach ($criteria as $criterion) {
                $result = match ($criterion) {
                    'points' => $b->points <=> $a->points,
                    'sets_ratio' => self::ratio($b->sets_won, $b->sets_lost) <=> self::ratio($a->sets_won, $a->sets_lost),
                    'sets_diff' => ($b->sets_won - $b->sets_lost) <=> ($a->sets_won - $a->sets_lost),
                    'points_ratio' => self::ratio($b->points_for, $b->points_against) <=> self::ratio($a->points_for, $a->points_against),
                    'points_diff' => ($b->points_for - $b->points_against) <=> ($a->points_for - $a->points_against),
                    'wins' => $b->won <=> $a->won,
                    default => 0,
                };

                if ($result !== 0) {
                    return $result;
                }
            }

            return 0;
        })->values();

        foreach ($sorted as $index => $row) {
            $position = $index + 1;

            if ($row->position !== $position) {
                $row->position = $position;
                $row->save();
            }
        }
    }

    private static function ratio(int $for, int $against): float
    {
        if ($against > 0) {
            return $for / $against;
        }

        return $for > 0 ? INF : 0.0;
    }
}

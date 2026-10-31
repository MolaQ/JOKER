<?php

namespace App\Support;

use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Możliwe duble: ta sama nasza drużyna gra tego samego dnia dwa (lub więcej) mecze z tym samym
 * przeciwnikiem. Zwykle to import ze związku, który dodał mecz drugi raz pod innym numerem.
 * Grupę można oznaczyć „to nie dubel” (np. dwumecz w jeden dzień), wtedy ostrzeżenie znika.
 */
class DuplicateGames
{
    /**
     * @return Collection<string, Collection<int, Game>> klucz: posortowane id meczów, np. "12,40"
     */
    public static function groups(?User $user = null): Collection
    {
        $teamIds = $user?->managedTeamIds();

        $candidates = Game::query()
            ->select('team_id', DB::raw('DATE(game_date) as day'))
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('game_date')
            ->when($teamIds !== null, fn ($q) => $q->whereIn('team_id', $teamIds))
            ->groupBy('team_id', DB::raw('DATE(game_date)'))
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($candidates->isEmpty()) {
            return collect();
        }

        $games = Game::query()
            ->with(['team', 'opponentTeam', 'competition'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($candidates) {
                foreach ($candidates as $row) {
                    $q->orWhere(fn ($q) => $q->where('team_id', $row->team_id)->whereDate('game_date', $row->day));
                }
            })
            ->orderBy('game_date')
            ->orderBy('id')
            ->get();

        $dismissed = DB::table('duplicate_game_dismissals')->pluck('game_ids')->flip();

        return $games
            ->groupBy(fn (Game $game) => $game->team_id.'|'.$game->game_date->toDateString())
            ->flatMap(fn (Collection $sameDay) => self::sameOpponent($sameDay->values()))
            ->mapWithKeys(fn (Collection $group) => [self::key($group->pluck('id')->all()) => $group])
            ->reject(fn (Collection $group, string $key) => $dismissed->has($key));
    }

    /**
     * Łączy mecze jednego dnia w grupy z tym samym przeciwnikiem: ten sam rywal z bazy albo
     * ta sama nazwa (jeden mecz może mieć rywala z bazy, a drugi tylko wpisaną nazwę).
     *
     * @param  Collection<int, Game>  $games
     * @return array<int, Collection<int, Game>>
     */
    private static function sameOpponent(Collection $games): array
    {
        $parent = range(0, $games->count() - 1);
        $find = function (int $i) use (&$parent, &$find): int {
            return $parent[$i] === $i ? $i : ($parent[$i] = $find($parent[$i]));
        };

        foreach ($games as $i => $a) {
            foreach ($games as $j => $b) {
                if ($j > $i && self::sameRival($a, $b)) {
                    $parent[$find($j)] = $find($i);
                }
            }
        }

        $groups = [];
        foreach ($games as $i => $game) {
            $groups[$find($i)][] = $game;
        }

        return array_values(array_map(fn ($group) => collect($group), array_filter($groups, fn ($group) => count($group) > 1)));
    }

    private static function sameRival(Game $a, Game $b): bool
    {
        if ($a->opponent_team_id && $a->opponent_team_id === $b->opponent_team_id) {
            return true;
        }

        $namesA = array_filter([self::normalize($a->opponent), self::normalize($a->opponentTeam?->full_name), self::normalize($a->opponentTeam?->displayName())]);
        $namesB = array_filter([self::normalize($b->opponent), self::normalize($b->opponentTeam?->full_name), self::normalize($b->opponentTeam?->displayName())]);

        return array_intersect($namesA, $namesB) !== [];
    }

    public static function count(?User $user = null): int
    {
        return self::groups($user)->count();
    }

    /**
     * Id meczów, które mają możliwego dubla (do znaczka na liście meczów).
     *
     * @return array<int, int>
     */
    public static function gameIds(?User $user = null): array
    {
        return once(fn () => self::groups($user)->flatten()->pluck('id')->all());
    }

    /**
     * @param  array<int, int>  $ids
     */
    public static function key(array $ids): string
    {
        sort($ids);

        return implode(',', $ids);
    }

    public static function dismiss(array $ids, ?User $user = null): void
    {
        DB::table('duplicate_game_dismissals')->insertOrIgnore([
            'game_ids' => self::key($ids),
            'dismissed_by' => $user?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private static function normalize(?string $name): string
    {
        return Str::of((string) $name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->value();
    }
}

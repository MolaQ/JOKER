<?php

namespace App\Support;

use App\Models\MiniTrip;
use Illuminate\Support\Facades\DB;

/**
 * Wyniki turnieju minisiatkówki: lista drużyn (grupa, nazwa, miejsce, na ile drużyn, dzieci).
 * Trener mini zgłasza je do zatwierdzenia (pending_results), a osoba z uprawnieniem do minisiatkówki
 * wpisuje od razu albo zatwierdza zgłoszenie.
 */
class MiniResults
{
    /**
     * Wiersze do formularza: zgłoszone, a gdy ich nie ma, zatwierdzone drużyny turnieju.
     *
     * @return list<array{id: ?int, group: string, name: string, place: string, teams_count: string, players: list<int>}>
     */
    public static function rows(MiniTrip $trip): array
    {
        if ($trip->pending_results) {
            return array_map(fn (array $row) => self::normalize($row), $trip->pending_results['teams'] ?? []);
        }

        return $trip->teams()->with('players')->get()->map(fn ($team) => [
            'id' => $team->id,
            'group' => (string) $team->mini_group_id,
            'name' => $team->name,
            'place' => (string) $team->place,
            'teams_count' => (string) $team->teams_count,
            'players' => $team->players->pluck('id')->all(),
        ])->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public static function submit(MiniTrip $trip, array $rows, int $userId): void
    {
        $trip->update(['pending_results' => [
            'submitted_by' => $userId,
            'submitted_at' => now()->toIso8601String(),
            'teams' => array_map(fn (array $row) => self::normalize($row), $rows),
        ]]);
    }

    /**
     * Zapisuje wyniki na stronie: aktualizuje drużyny z id, dodaje nowe, usuwa pominięte.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public static function apply(MiniTrip $trip, array $rows): void
    {
        DB::transaction(function () use ($trip, $rows) {
            $kept = [];

            foreach (array_map(fn (array $row) => self::normalize($row), $rows) as $row) {
                $team = $row['id'] ? $trip->teams()->find($row['id']) : null;
                $data = [
                    'mini_group_id' => $row['group'] !== '' ? (int) $row['group'] : null,
                    'name' => $row['name'],
                    'place' => $row['place'] !== '' ? (int) $row['place'] : null,
                    'teams_count' => $row['teams_count'] !== '' ? (int) $row['teams_count'] : null,
                ];
                $team ? $team->update($data) : $team = $trip->teams()->create($data);
                $team->players()->sync($row['players']);
                $kept[] = $team->id;
            }

            $trip->teams()->whereNotIn('id', $kept)->get()->each->delete();
            $trip->update(['pending_results' => null]);
        });
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{id: ?int, group: string, name: string, place: string, teams_count: string, players: list<int>}
     */
    private static function normalize(array $row): array
    {
        return [
            'id' => filled($row['id'] ?? null) ? (int) $row['id'] : null,
            'group' => (string) ($row['group'] ?? ''),
            'name' => trim((string) ($row['name'] ?? '')),
            'place' => (string) ($row['place'] ?? ''),
            'teams_count' => (string) ($row['teams_count'] ?? ''),
            'players' => array_values(array_map('intval', (array) ($row['players'] ?? []))),
        ];
    }
}

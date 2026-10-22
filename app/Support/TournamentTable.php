<?php

namespace App\Support;

use App\Models\Tournament;
use App\Models\TournamentCategory;
use App\Models\TournamentGroup;
use App\Models\TournamentMatch;
use App\Models\TournamentParticipant;
use Illuminate\Support\Collection;

/**
 * Tabele grup i klasyfikacja końcowa turnieju.
 *
 * Punktacja i kryteria kolejności ustawiane w panelu (turniej, ewentualnie własne w grupie);
 * domyślnie jak w siatkówce: 3 pkt za wygraną, 2 w tie-breaku, 1 za porażkę w tie-breaku.
 */
class TournamentTable
{
    /**
     * Domyślna punktacja: wygrana 3, wygrana w tie-breaku 2, porażka w tie-breaku 1, porażka 0, bez punktów za sety.
     *
     * @var array{win: int, win_tiebreak: int, loss_tiebreak: int, loss: int, per_set: int}
     */
    public const DEFAULT_SCORING = ['win' => 3, 'win_tiebreak' => 2, 'loss_tiebreak' => 1, 'loss' => 0, 'per_set' => 0];

    /**
     * Kryteria kolejności w tabeli, do ustawienia w panelu (kolejność ma znaczenie).
     *
     * @var array<string, string>
     */
    public const CRITERIA = [
        'points' => 'Punkty',
        'wins' => 'Liczba zwycięstw',
        'sets' => 'Stosunek setów',
        'small' => 'Stosunek małych punktów',
        'h2h' => 'Mecze bezpośrednie (mała tabela)',
    ];

    /** @var list<string> */
    public const DEFAULT_CRITERIA = ['points', 'wins', 'sets', 'small'];

    /**
     * Punktacja i kryteria grupy: własne grupy, inaczej turnieju, inaczej domyślne.
     *
     * @return array{win: int, win_tiebreak: int, loss_tiebreak: int, loss: int, per_set: int, criteria: list<string>}
     */
    public static function scoring(?TournamentGroup $group = null, ?Tournament $tournament = null): array
    {
        $tournament ??= $group?->category?->tournament;
        $custom = $group?->scoring ?: ($tournament?->scoring ?: []);
        $criteria = array_values(array_intersect((array) ($custom['criteria'] ?? self::DEFAULT_CRITERIA), array_keys(self::CRITERIA)));

        return collect(self::DEFAULT_SCORING)->map(fn (int $default, string $key) => (int) ($custom[$key] ?? $default))->all()
            + ['criteria' => $criteria ?: self::DEFAULT_CRITERIA];
    }

    /**
     * Opis punktacji pod tabelą grupy.
     *
     * @param  array{win: int, win_tiebreak: int, loss_tiebreak: int, loss: int, per_set: int}  $scoring
     */
    public static function describe(array $scoring): string
    {
        return collect([
            __('wygrana :points pkt', ['points' => $scoring['win']]),
            $scoring['win_tiebreak'] !== $scoring['win'] ? __('wygrana w tie-breaku :points pkt', ['points' => $scoring['win_tiebreak']]) : null,
            $scoring['loss_tiebreak'] !== $scoring['loss'] ? __('porażka w tie-breaku :points pkt', ['points' => $scoring['loss_tiebreak']]) : null,
            $scoring['loss'] ? __('porażka :points pkt', ['points' => $scoring['loss']]) : null,
            $scoring['per_set'] ? __('plus :points pkt za każdy wygrany set', ['points' => $scoring['per_set']]) : null,
        ])->filter()->implode(', ');
    }

    /**
     * Kryteria rozstrzygające przy równych punktach, do opisu: „mecze bezpośrednie (mała tabela), stosunek setów”.
     *
     * @param  array{criteria?: list<string>}  $scoring
     */
    public static function describeCriteria(array $scoring): string
    {
        return collect($scoring['criteria'] ?? self::DEFAULT_CRITERIA)->reject(fn (string $criterion, int $index) => $index === 0 && $criterion === 'points')->map(fn (string $criterion) => mb_strtolower(__(self::CRITERIA[$criterion])))->implode(', ');
    }

    /**
     * Tabela: punkty i statystyki z rozegranych meczów, kolejność według kryteriów,
     * a na końcu ręczna kolejność organizatora (np. po rozpisaniu małej tabeli), jeśli ją ustawił.
     *
     * @param  Collection<int, TournamentParticipant>  $participants
     * @param  Collection<int, TournamentMatch>  $matches
     * @param  list<int>  $manualOrder
     * @return Collection<int, array{participant: TournamentParticipant, played: int, won: int, lost: int, points: int, sets_won: int, sets_lost: int, small_won: int, small_lost: int, position: int, manual: bool}>
     */
    public static function for(Collection $participants, Collection $matches, ?array $scoring = null, array $manualOrder = []): Collection
    {
        $scoring = ($scoring ?? []) + self::DEFAULT_SCORING + ['criteria' => self::DEFAULT_CRITERIA];
        $finished = $matches->filter(fn (TournamentMatch $match) => $match->isFinished());
        $rows = self::stats($participants, $finished, $scoring);

        // Klucz sortowania budowany kryterium po kryterium; mała tabela liczy się tylko w gronie drużyn,
        // które są równe na wszystkich wcześniejszych kryteriach.
        $keys = array_fill_keys(array_keys($rows), []);

        foreach ($scoring['criteria'] as $criterion) {
            if ($criterion !== 'h2h') {
                foreach ($rows as $id => $row) {
                    $keys[$id] = array_merge($keys[$id], self::criterionValue($criterion, $row));
                }

                continue;
            }

            foreach (collect($keys)->groupBy(fn (array $key) => json_encode($key), true) as $cluster) {
                $ids = $cluster->keys()->all();

                if (count($ids) < 2) {
                    $keys[$ids[0]] = array_merge($keys[$ids[0]], [0, 0, 0]);

                    continue;
                }

                $mini = self::stats(
                    $participants->whereIn('id', $ids),
                    $finished->filter(fn (TournamentMatch $match) => in_array($match->home_participant_id, $ids, true) && in_array($match->away_participant_id, $ids, true)),
                    $scoring,
                );

                foreach ($ids as $id) {
                    $keys[$id] = array_merge($keys[$id], [$mini[$id]['points'], ...self::criterionValue('sets', $mini[$id]), ...self::criterionValue('small', $mini[$id])]);
                }
            }
        }

        $sorted = collect($rows)->sortBy([fn (array $a, array $b) => $keys[$b['participant']->id] <=> $keys[$a['participant']->id]]);

        if ($manualOrder !== []) {
            $rank = array_flip(array_values($manualOrder));
            $sorted = $sorted->values()->sortBy(fn (array $row, int $index) => [$rank[$row['participant']->id] ?? PHP_INT_MAX, $index]);
        }

        return $sorted->values()->map(fn (array $row, int $index) => $row + ['position' => $index + 1, 'manual' => $manualOrder !== []]);
    }

    /**
     * @param  Collection<int, TournamentParticipant>  $participants
     * @param  Collection<int, TournamentMatch>  $matches
     * @param  array<string, mixed>  $scoring
     * @return array<int, array<string, mixed>>
     */
    private static function stats(Collection $participants, Collection $matches, array $scoring): array
    {
        $rows = $participants->mapWithKeys(fn (TournamentParticipant $participant) => [$participant->id => [
            'participant' => $participant,
            'played' => 0, 'won' => 0, 'lost' => 0, 'points' => 0,
            'sets_won' => 0, 'sets_lost' => 0, 'small_won' => 0, 'small_lost' => 0,
        ]])->all();

        foreach ($matches as $match) {
            foreach ([[$match->home_participant_id, $match->home_sets, $match->away_sets, 0], [$match->away_participant_id, $match->away_sets, $match->home_sets, 1]] as [$id, $won, $lost, $side]) {
                if (! isset($rows[$id])) {
                    continue;
                }

                $tieBreak = min($won, $lost) === max($won, $lost) - 1 && min($won, $lost) > 0;
                $rows[$id]['played']++;
                $rows[$id][$won > $lost ? 'won' : 'lost']++;
                $rows[$id]['points'] += ($won > $lost ? ($tieBreak ? $scoring['win_tiebreak'] : $scoring['win']) : ($tieBreak ? $scoring['loss_tiebreak'] : $scoring['loss'])) + $won * $scoring['per_set'];
                $rows[$id]['sets_won'] += $won;
                $rows[$id]['sets_lost'] += $lost;

                foreach ((array) $match->sets_score as $set) {
                    $set = array_values((array) $set);
                    $rows[$id]['small_won'] += (int) ($set[$side] ?? 0);
                    $rows[$id]['small_lost'] += (int) ($set[1 - $side] ?? 0);
                }
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<int|float>
     */
    private static function criterionValue(string $criterion, array $row): array
    {
        $ratio = fn (int $won, int $lost): float => $lost === 0 ? ($won > 0 ? PHP_INT_MAX : 0) : $won / $lost;

        return match ($criterion) {
            'points' => [$row['points']],
            'wins' => [$row['won']],
            'sets' => [$ratio($row['sets_won'], $row['sets_lost'])],
            'small' => [$ratio($row['small_won'], $row['small_lost'])],
            default => [0],
        };
    }

    /**
     * Klasyfikacja końcowa kategorii: miejsca z meczów o miejsca (zwycięzca meczu o 7. miejsce = 7.,
     * przegrany = 8.) i z finałów drabinek, a ręcznie wpisane miejsce w panelu ma pierwszeństwo.
     *
     * @return Collection<int, array{position: int, participant: TournamentParticipant}>
     */
    public static function finalStandings(TournamentCategory $category): Collection
    {
        $positions = [];

        foreach ($category->matches as $match) {
            if ($match->placeAtStake() !== null && $match->isFinished()) {
                $positions[$match->winner()->id] = $match->placeAtStake();
                $positions[$match->loser()->id] = $match->placeAtStake() + 1;
            }
        }

        // Ostatnia runda drabinki z jednym meczem to finał tej drabinki (o 1. miejsce, o 5. miejsce...).
        $brackets = $category->matches->where('stage', TournamentMatch::STAGE_PLAYOFF)->groupBy(fn (TournamentMatch $match) => $match->bracketFrom());

        foreach ($brackets as $from => $bracketMatches) {
            $lastRound = $bracketMatches->where('bracket_round', $bracketMatches->max('bracket_round'));
            $final = $lastRound->first();

            if ($lastRound->count() === 1 && $final->decides_place === null && $final->isFinished() && ! in_array($from, $positions, true)) {
                $positions[$final->winner()->id] = $from;
                $positions[$final->loser()->id] = $from + 1;
            }
        }

        return $category->participants
            ->map(fn (TournamentParticipant $participant) => [
                'position' => $participant->final_position ?? ($positions[$participant->id] ?? null),
                'participant' => $participant,
            ])
            ->filter(fn (array $row) => $row['position'] !== null)
            ->sortBy('position')
            ->values();
    }
}

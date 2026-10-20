<?php

namespace App\Support;

use App\Models\TournamentCategory;
use App\Models\TournamentMatch;
use App\Models\TournamentParticipant;
use Illuminate\Support\Collection;

/**
 * Tabele grup i klasyfikacja końcowa turnieju.
 *
 * Punkty jak w siatkówce: wygrana bez tie-breaka 3 pkt, wygrana w tie-breaku 2 pkt,
 * porażka w tie-breaku 1 pkt (działa i dla meczów do 2 wygranych setów, i do 3).
 */
class TournamentTable
{
    /**
     * @param  Collection<int, TournamentParticipant>  $participants
     * @param  Collection<int, TournamentMatch>  $matches
     * @return Collection<int, array{participant: TournamentParticipant, played: int, won: int, lost: int, points: int, sets_won: int, sets_lost: int, small_won: int, small_lost: int, position: int}>
     */
    public static function for(Collection $participants, Collection $matches): Collection
    {
        $rows = $participants->mapWithKeys(fn (TournamentParticipant $participant) => [$participant->id => [
            'participant' => $participant,
            'played' => 0, 'won' => 0, 'lost' => 0, 'points' => 0,
            'sets_won' => 0, 'sets_lost' => 0, 'small_won' => 0, 'small_lost' => 0,
        ]])->all();

        foreach ($matches->filter(fn (TournamentMatch $match) => $match->isFinished()) as $match) {
            foreach ([[$match->home_participant_id, $match->home_sets, $match->away_sets, 0], [$match->away_participant_id, $match->away_sets, $match->home_sets, 1]] as [$id, $won, $lost, $side]) {
                if (! isset($rows[$id])) {
                    continue;
                }

                $tieBreak = min($won, $lost) === max($won, $lost) - 1 && min($won, $lost) > 0;
                $rows[$id]['played']++;
                $rows[$id][$won > $lost ? 'won' : 'lost']++;
                $rows[$id]['points'] += $won > $lost ? ($tieBreak ? 2 : 3) : ($tieBreak ? 1 : 0);
                $rows[$id]['sets_won'] += $won;
                $rows[$id]['sets_lost'] += $lost;

                foreach ((array) $match->sets_score as $set) {
                    $set = array_values((array) $set);
                    $rows[$id]['small_won'] += (int) ($set[$side] ?? 0);
                    $rows[$id]['small_lost'] += (int) ($set[1 - $side] ?? 0);
                }
            }
        }

        $ratio = fn (int $won, int $lost): float => $lost === 0 ? ($won > 0 ? PHP_INT_MAX : 0) : $won / $lost;

        return collect($rows)
            ->sort(fn (array $a, array $b) => [$b['points'], $b['won'], $ratio($b['sets_won'], $b['sets_lost']), $ratio($b['small_won'], $b['small_lost'])]
                <=> [$a['points'], $a['won'], $ratio($a['sets_won'], $a['sets_lost']), $ratio($a['small_won'], $a['small_lost'])])
            ->values()
            ->map(fn (array $row, int $index) => $row + ['position' => $index + 1]);
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

<?php

namespace App\Support;

use App\Models\TournamentCategory;
use App\Models\TournamentResultBackup;
use Illuminate\Support\Facades\DB;

/**
 * Czyszczenie wyników kategorii (np. po próbnym wpisywaniu) z kopią, którą można przywrócić.
 * Mecze, godziny, sektory, drużyny i grupy zostają; znikają wyniki, „trwa”, miejsca końcowe i ręczna kolejność tabel.
 */
class TournamentResults
{
    public static function clear(TournamentCategory $category): TournamentResultBackup
    {
        return DB::transaction(function () use ($category) {
            $category->load(['matches', 'participants', 'groups']);

            $backup = $category->resultBackups()->create([
                'results_count' => $category->matches->whereNotNull('sets_score')->count(),
                'data' => [
                    'matches' => $category->matches->mapWithKeys(fn ($match) => [$match->id => [
                        'sets_score' => $match->sets_score,
                        'home_sets' => $match->home_sets,
                        'away_sets' => $match->away_sets,
                        'is_live' => $match->is_live,
                    ]])->all(),
                    'participants' => $category->participants->pluck('final_position', 'id')->all(),
                    'groups' => $category->groups->pluck('manual_order', 'id')->all(),
                ],
            ]);

            $category->matches()->update(['sets_score' => null, 'home_sets' => null, 'away_sets' => null, 'is_live' => false]);
            $category->participants()->update(['final_position' => null]);
            $category->groups()->update(['manual_order' => null]);
            TournamentBracket::sync($category->fresh());

            return $backup;
        });
    }

    public static function restore(TournamentResultBackup $backup): void
    {
        DB::transaction(function () use ($backup) {
            $category = $backup->category;
            $data = $backup->data ?? [];

            foreach ($category->matches as $match) {
                if (isset($data['matches'][$match->id])) {
                    $match->update($data['matches'][$match->id]);
                }
            }

            foreach ($category->participants as $participant) {
                if (array_key_exists($participant->id, $data['participants'] ?? [])) {
                    $participant->update(['final_position' => $data['participants'][$participant->id]]);
                }
            }

            foreach ($category->groups as $group) {
                if (array_key_exists($group->id, $data['groups'] ?? [])) {
                    $group->update(['manual_order' => $data['groups'][$group->id]]);
                }
            }

            TournamentBracket::sync($category->fresh());
        });
    }
}

<?php

namespace App\Support;

use App\Models\DailyLineup;
use App\Models\Player;
use App\Models\Season;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Scalanie zdublowanego zawodnika (ta sama osoba wpisana dwa razy, np. osobno w seniorach i w juniorach II).
 * Wszystko z duplikatu przechodzi do zawodnika, który zostaje: drużyny w sezonach (inna drużyna = dodatkowa drużyna),
 * nagrody, osiągnięcia, wyróżnienia z turniejów, oznaczenia na zdjęciach, serduszka, rodzice, wyjazdy mini, składy dnia.
 * Puste dane zawodnika uzupełniają się danymi duplikatu. Duplikat trafia do kosza, a jego stary adres prowadzi do nowego profilu.
 */
class PlayerMerge
{
    public const REDIRECTS_KEY = 'merged_players';

    public const DISMISSED_KEY = 'player_duplicates_dismissed';

    /**
     * Inni zawodnicy o tym samym imieniu i nazwisku (podpowiedź do scalenia), bez par oznaczonych „to różne osoby”.
     *
     * @return Collection<int, Player>
     */
    public static function candidates(Player $player): Collection
    {
        return self::groups()->first(fn (Collection $group) => $group->contains('id', $player->id))?->reject(fn (Player $other) => $other->is($player))->values() ?? collect();
    }

    /**
     * Grupy możliwych duplikatów: to samo imię i nazwisko (bez wielkości liter i spacji).
     *
     * @return Collection<string, Collection<int, Player>> klucz: posortowane id, np. "12,40"
     */
    public static function groups(): Collection
    {
        $dismissed = array_flip((array) app(SiteSettings::class)->get(self::DISMISSED_KEY, []));

        return Player::with('team')->orderBy('id')->get()
            ->groupBy(fn (Player $player) => mb_strtolower(preg_replace('/\s+/u', ' ', trim($player->first_name).' '.trim($player->last_name))))
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->mapWithKeys(fn (Collection $group) => [$group->pluck('id')->sort()->implode(',') => $group->values()])
            ->reject(fn (Collection $group, string $key) => isset($dismissed[$key]));
    }

    /**
     * „To różne osoby” (np. dwóch Janów Kowalskich): ta grupa przestaje być podpowiadana.
     */
    public static function dismiss(string $key): void
    {
        $dismissed = (array) app(SiteSettings::class)->get(self::DISMISSED_KEY, []);
        app(SiteSettings::class)->set([self::DISMISSED_KEY => array_values(array_unique([...$dismissed, $key]))]);
    }

    public static function merge(Player $keep, Player $duplicate): void
    {
        if ($keep->is($duplicate)) {
            return;
        }

        DB::transaction(function () use ($keep, $duplicate) {
            $keepId = $keep->id;
            $oldId = $duplicate->id;

            // Drużyny w sezonach: drużyna duplikatu zostaje jako dodatkowa (z jego numerem), powtórki znikają.
            foreach (DB::table('team_player_season')->where('player_id', $oldId)->get() as $row) {
                $exists = DB::table('team_player_season')->where(['player_id' => $keepId, 'team_id' => $row->team_id, 'season_id' => $row->season_id])->exists();
                $exists
                    ? DB::table('team_player_season')->where('id', $row->id)->delete()
                    : DB::table('team_player_season')->where('id', $row->id)->update(['player_id' => $keepId]);
            }

            // Duplikat bez wpisu sezonu w swojej drużynie: drużyna zostaje jako dodatkowa w bieżącym sezonie.
            $season = Season::current()->value('id');
            if ($season && $duplicate->team_id && $duplicate->team_id !== $keep->team_id
                && ! DB::table('team_player_season')->where(['player_id' => $keepId, 'team_id' => $duplicate->team_id, 'season_id' => $season])->exists()) {
                DB::table('team_player_season')->insert(['player_id' => $keepId, 'team_id' => $duplicate->team_id, 'season_id' => $season, 'jersey_number' => $duplicate->jersey_number, 'is_captain' => false, 'created_at' => now(), 'updated_at' => now()]);
            }

            // Tabele z jednym zawodnikiem na wiersz: po prostu nowy właściciel.
            foreach (['player_awards', 'player_achievements', 'tournament_awards', 'player_change_requests', 'alumni'] as $table) {
                DB::table($table)->where('player_id', $oldId)->update(['player_id' => $keepId]);
            }

            // Tabele łączące: bez powtórek.
            foreach (['player_guardian' => 'user_id', 'mini_group_player' => 'mini_group_id', 'mini_trip_team_player' => 'mini_trip_team_id'] as $table => $other) {
                self::movePivot($table, 'player_id', $other, $oldId, $keepId);
            }

            self::movePivot('likes', 'likeable_id', 'user_id', $oldId, $keepId, ['likeable_type' => Player::class]);
            self::movePivot('photo_tags', 'taggable_id', 'gallery_photo_id', $oldId, $keepId, ['taggable_type' => Player::class]);

            // Składy dnia trzymają numery zawodników w JSON.
            foreach (DailyLineup::all() as $lineup) {
                $players = collect($lineup->players)->map(fn ($slot) => ($slot['player_id'] ?? null) === $oldId ? ['player_id' => $keepId] + $slot : $slot)->all();
                $changed = $players !== $lineup->players || $lineup->captain_id === $oldId;
                if ($changed) {
                    $lineup->update(['players' => $players, 'captain_id' => $lineup->captain_id === $oldId ? $keepId : $lineup->captain_id]);
                }
            }

            // Puste pola zawodnika uzupełniamy danymi duplikatu; wyświetlenia się sumują.
            $fill = [];
            foreach (['jersey_number', 'position', 'extra_positions', 'birth_date', 'birth_year', 'height', 'weight', 'reach', 'spike_reach', 'block_reach', 'bio', 'interests', 'social_links', 'photo_path', 'user_id', 'additional_info', 'instagram', 'mini_group_id'] as $field) {
                if (blank($keep->getAttribute($field)) && filled($duplicate->getAttribute($field))) {
                    $fill[$field] = $duplicate->getAttribute($field);
                }
            }
            $fill['views_count'] = (int) $keep->views_count + (int) $duplicate->views_count;
            $fill['is_active'] = $keep->is_active || $duplicate->is_active;
            // Konto i zdjęcie przechodzą na zawodnika, więc duplikat w koszu już ich nie trzyma (kosz nie usunie zdjęcia).
            $duplicate->forceFill(array_fill_keys(array_intersect(['user_id', 'photo_path'], array_keys($fill)), null))->saveQuietly();
            $keep->forceFill($fill)->save();

            $redirects = (array) app(SiteSettings::class)->get(self::REDIRECTS_KEY, []);
            foreach ($redirects as $from => $to) {
                if ((int) $to === $oldId) {
                    $redirects[$from] = $keepId;
                }
            }
            $redirects[$oldId] = $keepId;
            app(SiteSettings::class)->set([self::REDIRECTS_KEY => $redirects]);

            Trash::delete($duplicate->fresh(), $duplicate->full_name.' (scalony z #'.$keepId.')');
        });

        SearchIndex::forget();
    }

    /**
     * Zawodnik, do którego prowadzi adres scalonego duplikatu.
     */
    public static function redirectTarget(int $id): ?int
    {
        $target = ((array) app(SiteSettings::class)->get(self::REDIRECTS_KEY, []))[$id] ?? null;

        return $target ? (int) $target : null;
    }

    /**
     * @param  array<string, mixed>  $where
     */
    private static function movePivot(string $table, string $column, string $other, int $oldId, int $keepId, array $where = []): void
    {
        $taken = DB::table($table)->where($where)->where($column, $keepId)->pluck($other)->all();
        DB::table($table)->where($where)->where($column, $oldId)->whereIn($other, $taken)->delete();
        DB::table($table)->where($where)->where($column, $oldId)->update([$column => $keepId]);
    }
}

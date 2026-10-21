<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jednorazowo przepina istniejącą bazę na scalone migracje (bez utraty danych):
 * gdy baza ma już wszystkie stare migracje, w tabeli migrations zamienia ich nazwy na nowe pliki.
 * Użycie po aktualizacji: php artisan joker:adopt-squashed-migrations, potem php artisan migrate.
 */
class AdoptSquashedMigrations extends Command
{
    protected $signature = 'joker:adopt-squashed-migrations';

    protected $description = 'Przepnij istniejącą bazę na scalone migracje bez utraty danych';

    /** Ostatni commit, który ma jeszcze stare, osobne migracje. */
    private const LAST_OLD_COMMIT = '0609727';

    /** @var list<string> */
    private const OLD = [
        '0001_01_01_000000_create_users_table',
        '0001_01_01_000001_create_cache_table',
        '0001_01_01_000002_create_jobs_table',
        '2026_08_25_193001_add_role_and_avatar_to_users_table',
        '2026_08_25_193501_create_seasons_table',
        '2026_08_25_193507_create_teams_table',
        '2026_08_25_193514_create_players_table',
        '2026_08_25_193905_create_games_table',
        '2026_08_25_193918_create_game_comments_table',
        '2026_08_25_193926_create_game_photos_table',
        '2026_08_25_193934_create_league_standings_table',
        '2026_08_25_194338_create_articles_table',
        '2026_08_25_194502_create_comments_table',
        '2026_08_25_194516_create_documents_table',
        '2026_08_25_194542_create_sponsors_table',
        '2026_08_26_000001_add_physical_fields_to_players_table',
        '2026_08_26_000002_add_birth_year_to_teams_table',
        '2026_08_26_000003_create_competitions_table',
        '2026_08_26_000004_create_competition_team_table',
        '2026_08_26_000005_create_team_player_season_table',
        '2026_08_26_000006_add_competition_id_to_games_table',
        '2026_08_26_000007_add_competition_id_to_league_standings_table',
        '2026_08_26_000008_create_likes_table',
        '2026_08_26_000009_add_additional_info_to_players_table',
        '2026_08_26_000010_add_profile_fields_to_users_table',
        '2026_08_26_000011_create_profile_change_requests_table',
        '2026_08_26_000012_create_rival_teams_table',
        '2026_08_26_000013_create_competition_levels_table',
        '2026_08_26_000014_add_level_and_scoring_to_competitions_table',
        '2026_08_26_000015_create_team_trainers_table',
        '2026_08_26_000016_add_opponent_team_and_points_to_games_table',
        '2026_08_26_000017_add_competitor_to_league_standings_table',
        '2026_08_26_000018_fix_league_standings_unique_constraint',
        '2026_08_28_120000_create_hero_slides_table',
        '2026_08_28_130000_add_publish_window_to_hero_slides_table',
        '2026_10_16_000001_add_photo_and_staff_roles',
        '2026_10_16_000002_create_settings_and_pages_tables',
        '2026_10_19_094657_add_schedule_import_fields',
        '2026_10_19_124459_add_wzps_standings_import_fields',
        '2026_10_19_145143_add_results_source_fields',
        '2026_10_20_093650_add_player_profiles_birthdays_and_article_games',
        '2026_10_20_093703_reorder_main_menu_items',
        '2026_10_20_102627_add_positions_views_birthday_modes_and_tournaments',
        '2026_10_20_122925_create_page_views_table',
        '2026_10_20_123314_add_english_columns_to_articles_and_pages_table',
        '2026_10_20_123319_add_memorial_tournament_pages',
        '2026_10_20_123945_add_memorial_to_main_menu',
        '2026_10_20_140617_create_rival_clubs_and_group_rival_teams',
        '2026_10_20_152148_add_phase_name_and_decided_place_to_tournament_matches',
        '2026_10_21_084401_add_streams_to_games_and_tournament_matches',
        '2026_10_21_084401_create_galleries_table',
        '2026_10_21_101740_replace_calendar_home_gradient_settings',
        '2026_10_21_103541_add_numbers_and_sources_to_tournament_matches',
    ];

    /** @var list<string> */
    private const NEW = [
        '0001_01_01_000000_create_users_table',
        '0001_01_01_000001_create_cache_table',
        '0001_01_01_000002_create_jobs_table',
        '2026_10_21_200001_create_club_tables',
        '2026_10_21_200002_create_tournament_tables',
        '2026_10_21_200003_create_competition_tables',
        '2026_10_21_200004_create_content_tables',
    ];

    public function handle(): int
    {
        if (! Schema::hasTable('migrations') || DB::table('migrations')->doesntExist()) {
            $this->info('Pusta baza: wystarczy php artisan migrate.');

            return self::SUCCESS;
        }

        $ran = DB::table('migrations')->pluck('migration');
        $squashed = array_values(array_diff(self::NEW, self::OLD));

        if ($ran->intersect($squashed)->count() === count($squashed)) {
            $this->info('Baza już korzysta ze scalonych migracji.');

            return self::SUCCESS;
        }

        $missing = array_values(array_diff(self::OLD, $ran->all()));

        if ($missing !== []) {
            $this->error('Baza nie ma jeszcze wszystkich starych migracji: '.implode(', ', $missing));
            $this->line('Najpierw: git checkout '.self::LAST_OLD_COMMIT.' && php artisan migrate && git checkout przebudowa-strony, potem uruchom to polecenie ponownie.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($ran, $squashed) {
            $batch = (int) DB::table('migrations')->max('batch') + 1;

            DB::table('migrations')->whereIn('migration', array_diff(self::OLD, self::NEW))->delete();
            DB::table('migrations')->insert(collect($squashed)->diff($ran)->map(fn (string $migration) => ['migration' => $migration, 'batch' => $batch])->values()->all());
        });

        $this->info('Gotowe: baza korzysta teraz ze scalonych migracji, dane zostały bez zmian.');

        return self::SUCCESS;
    }
}

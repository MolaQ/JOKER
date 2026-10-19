<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\CompetitionLevel;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Database\Seeder;

class CompetitionSeeder extends Seeder
{
    /** Strony Wielkopolskiego Związku Piłki Siatkowej, z których pobieramy mecze i tabele. */
    private const WZPS = 'https://wzps.poznan.pl/obsady/index.php?';

    /** Wyniki i tabele (mają pierwszeństwo przed WZPS), bez godzin i hal. */
    private const SITE = 'https://wielkopolskasiatkowka.com.pl/';

    /**
     * Rozgrywki naszych drużyn. Terminarz, wyniki i tabele pobiera `php artisan joker:sync-schedules`.
     * Przy drużynie zapisane są nazwy (oddzielone średnikiem), pod którymi występuje w danych rozgrywkach.
     */
    public function run(): void
    {
        $season = Season::current()->first();

        if (! $season) {
            return;
        }

        $teams = Team::orderBy('display_order')->get()->keyBy('slug');
        $levels = CompetitionLevel::get()->keyBy('name');

        $competitions = [
            [
                'name' => 'II Liga Mężczyzn',
                'level' => '2 liga',
                'teams' => ['seniorzy' => 'LOMS Joker Piła'],
                'sources' => [self::SITE.'ii-liga-mezczyzn-5/', self::WZPS.'mode=8&club_id=7&team_id=701'],
            ],
            [
                'name' => 'III Liga Mężczyzn',
                'level' => '3 liga',
                'teams' => ['juniorzy-ii' => 'LOMS Joker Piła; LOMS Joker Piła 1', 'kadeci' => 'LOMS Joker Piła II; LOMS Joker Piła 2'],
                'sources' => [self::WZPS.'mode=12&type=11', self::SITE.'iii-liga-mezczyzn-26-27/', self::WZPS.'mode=8&club_id=7&team_id=701', self::WZPS.'mode=8&club_id=7&team_id=828'],
            ],
            [
                'name' => 'Liga Juniorów',
                'level' => 'wojewódzka',
                'teams' => ['juniorzy' => 'LOMS Joker Piła; LOMS Joker Piła 1', 'juniorzy-ii' => 'LOMS Joker Piła II; LOMS Joker Piła 2'],
                'sources' => [self::WZPS.'mode=12&type=7', self::SITE.'liga-juniorow-26-27/', self::WZPS.'mode=8&club_id=7&team_id=673', self::WZPS.'mode=8&club_id=7&team_id=972'],
            ],
            [
                'name' => 'Liga Kadetów',
                'level' => 'wojewódzka',
                'teams' => ['kadeci' => 'LOMS Joker Piła', 'kadeci-ii' => 'ASBL; KS ASBL Piła'],
                'sources' => [self::WZPS.'mode=12&type=5', self::SITE.'liga-kadetow-26-27/', self::WZPS.'mode=8&club_id=7&team_id=638', self::WZPS.'mode=8&club_id=7&team_id=644'],
            ],
            [
                'name' => 'Liga Młodzików',
                'level' => 'wojewódzka',
                'teams' => ['mlodzicy' => 'LOMS Joker Piła; LOMS Joker Piła 1', 'mlodzicy-ii' => 'LOMS Joker Piła II; LOMS Joker Piła 2'],
                'sources' => [self::WZPS.'mode=12&type=3', self::SITE.'liga-mlodzikow-26-27/', self::WZPS.'mode=4&type=3', self::WZPS.'mode=8&club_id=7&team_id=761', self::WZPS.'mode=8&club_id=7&team_id=834'],
            ],
        ];

        foreach ($competitions as $index => $data) {
            $competition = Competition::create([
                'name' => $data['name'],
                'level' => $data['level'],
                'level_id' => $levels->get($data['level'])?->id,
                'season_id' => $season->id,
                'description' => "Rozgrywki WZPS w sezonie {$season->name}.",
                'display_order' => $index + 1,
                'source_url' => implode("\n", $data['sources']),
            ]);

            foreach ($data['teams'] as $slug => $externalName) {
                if ($team = $teams->get($slug)) {
                    $competition->teams()->attach($team->id, ['external_name' => $externalName]);
                }
            }
        }
    }
}

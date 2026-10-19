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

    /**
     * Rozgrywki naszych drużyn w WZPS. Terminarz, wyniki i tabele pobiera `php artisan joker:sync-schedules`.
     * Przy drużynie zapisana jest nazwa, pod którą występuje w danych rozgrywkach.
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
                'name' => 'III Liga Mężczyzn',
                'level' => '3 liga',
                'teams' => ['seniorzy' => 'LOMS Joker Piła', 'kadeci' => 'LOMS Joker Piła II'],
                'sources' => ['mode=12&type=11', 'mode=8&club_id=7&team_id=701', 'mode=8&club_id=7&team_id=828'],
            ],
            [
                // Mecze tej ligi WZPS pokazuje tylko na stronie drużyny (bez tabeli).
                'name' => 'Liga Seniorów WZPS',
                'level' => 'wojewódzka',
                'teams' => ['seniorzy' => 'LOMS Joker Piła'],
                'sources' => ['mode=8&club_id=7&team_id=701'],
            ],
            [
                'name' => 'Juniorzy',
                'level' => 'wojewódzka',
                'teams' => ['juniorzy' => 'LOMS Joker Piła', 'juniorzy-ii' => 'LOMS Joker Piła II'],
                'sources' => ['mode=12&type=7', 'mode=8&club_id=7&team_id=673', 'mode=8&club_id=7&team_id=972'],
            ],
            [
                'name' => 'Kadeci',
                'level' => 'wojewódzka',
                'teams' => ['kadeci' => 'LOMS Joker Piła'],
                'sources' => ['mode=12&type=5', 'mode=8&club_id=7&team_id=638'],
            ],
            [
                'name' => 'Kadeci II i III',
                'level' => 'wojewódzka',
                'teams' => ['kadeci-ii' => 'LOMS Joker Piła II', 'kadeci-iii' => 'LOMS Joker Piła III'],
                'sources' => ['mode=8&club_id=7&team_id=644', 'mode=8&club_id=7&team_id=645'],
            ],
            [
                'name' => 'IKO Liga Młodzików',
                'level' => 'wojewódzka',
                'teams' => ['mlodzicy' => 'LOMS Joker Piła', 'mlodzicy-ii' => 'LOMS Joker Piła II'],
                'sources' => ['mode=12&type=3', 'mode=4&type=3', 'mode=8&club_id=7&team_id=761', 'mode=8&club_id=7&team_id=834'],
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
                'source_url' => implode("\n", array_map(fn (string $query) => self::WZPS.$query, $data['sources'])),
            ]);

            foreach ($data['teams'] as $slug => $externalName) {
                if ($team = $teams->get($slug)) {
                    $competition->teams()->attach($team->id, ['external_name' => $externalName]);
                }
            }
        }
    }
}

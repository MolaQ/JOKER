<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\CompetitionLevel;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Database\Seeder;

class CompetitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Każda drużyna gra we własnych rozgrywkach dla danego sezonu; niektóre drużyny
     * biorą udział w kilku rozgrywkach jednocześnie (np. liga wojewódzka + 3 liga).
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
            ['name' => '3 Liga Mężczyzn', 'level' => '3 liga', 'teams' => ['seniorzy']],
            ['name' => 'Liga Międzywojewódzka Juniorów', 'level' => 'międzywojewódzka', 'teams' => ['junior-2008']],
            ['name' => 'Liga Wojewódzka Juniorów', 'level' => 'wojewódzka', 'teams' => ['junior-2008', 'junior-2009']],
            ['name' => 'Liga Wojewódzka Kadetów', 'level' => 'wojewódzka', 'teams' => ['junior-mlodszy-2010', 'junior-mlodszy-2011']],
            ['name' => '3 Liga Kadetów', 'level' => '3 liga', 'teams' => ['junior-mlodszy-2010']],
            ['name' => 'Liga Wojewódzka Młodzików', 'level' => 'wojewódzka', 'teams' => ['mlodzik-2012', 'mlodzik-2013']],
        ];

        foreach ($competitions as $index => $data) {
            $competition = Competition::create([
                'name' => $data['name'],
                'level' => $data['level'],
                'level_id' => $levels->get($data['level'])?->id,
                'season_id' => $season->id,
                'description' => "Rozgrywki {$data['level']} w sezonie {$season->name}.",
                'display_order' => $index + 1,
            ]);

            foreach ($data['teams'] as $slug) {
                if ($team = $teams->get($slug)) {
                    $competition->teams()->attach($team->id);
                }
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\Game;
use App\Models\GameComment;
use App\Models\RivalTeam;
use App\Models\Season;
use App\Models\User;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Mecze są tworzone dla każdej pary drużyna-rozgrywki (drużyna może grać
     * w kilku rozgrywkach jednocześnie, więc dostanie mecze w każdych z nich).
     * Przeciwnicy są losowani z bazy drużyn rywali (RivalTeam), aby zademonstrować
     * powiązanie meczów z tabelą ligową.
     */
    public function run(): void
    {
        $currentSeason = Season::current()->first();
        $competitions = Competition::with('teams')->where('season_id', $currentSeason?->id)->get();
        $users = User::whereIn('role', ['player', 'fan', 'parent'])->get();
        $rivalTeams = RivalTeam::all();

        foreach ($competitions as $competition) {
            foreach ($competition->teams as $team) {
                $categoryRivals = $rivalTeams->where('category', $team->category)->values();
                $pool = $categoryRivals->isNotEmpty() ? $categoryRivals : $rivalTeams;

                Game::factory()
                    ->count(10)
                    ->create([
                        'team_id' => $team->id,
                        'season_id' => $currentSeason->id,
                        'competition_id' => $competition->id,
                        'league' => $competition->name,
                        'opponent_team_id' => fn () => $pool->isNotEmpty() ? $pool->random()->id : null,
                    ])
                    ->each(function ($game) use ($users) {
                        if ($game->status === 'finished') {
                            GameComment::factory()
                                ->count(fake()->numberBetween(2, 5))
                                ->create([
                                    'game_id' => $game->id,
                                    'user_id' => $users->random()->id,
                                ]);
                        }
                    });
            }
        }
    }
}

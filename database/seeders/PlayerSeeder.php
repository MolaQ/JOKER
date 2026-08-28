<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\PlayerPosition;
use App\UserRole;
use Illuminate\Database\Seeder;

class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Każda drużyna dostaje własny skład (players.team_id = drużyna macierzysta).
     * Dodatkowo, dla aktualnego sezonu, kilku najstarszych zawodników z każdej
     * młodszej drużyny dogrywa też w drużynie starszego rocznika (team_player_season),
     * zgodnie z zasadą, że młodsi zawodnicy mogą grać ze starszymi.
     */
    public function run(): void
    {
        $currentSeason = Season::current()->first();
        $playerUsers = User::where('role', UserRole::Player)->get();

        $teams = Team::orderBy('display_order')->get()->keyBy('slug');

        foreach ($teams as $team) {
            $usedNumbers = [];

            for ($i = 0; $i < 14; $i++) {
                do {
                    $jerseyNumber = fake()->numberBetween(1, 99);
                } while (in_array($jerseyNumber, $usedNumbers));

                $usedNumbers[] = $jerseyNumber;

                $birthDate = $team->birth_year
                    ? fake()->dateTimeBetween("{$team->birth_year}-01-01", "{$team->birth_year}-12-31")
                    : fake()->dateTimeBetween('-32 years', '-19 years');

                $player = Player::create([
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'jersey_number' => $jerseyNumber,
                    'position' => fake()->randomElement(PlayerPosition::cases()),
                    'birth_date' => $birthDate,
                    'height' => fake()->numberBetween(170, 210),
                    'weight' => fake()->numberBetween(60, 100),
                    'reach' => fake()->numberBetween(220, 250),
                    'spike_reach' => fake()->numberBetween(250, 340),
                    'bio' => fake()->paragraph(),
                    'team_id' => $team->id,
                    'user_id' => $playerUsers->random()?->id,
                    'is_active' => true,
                ]);

                if ($currentSeason) {
                    $team->seasonRoster($currentSeason->id)->attach($player->id, [
                        'jersey_number' => $jerseyNumber,
                        'is_captain' => $i === 0,
                    ]);
                }
            }
        }

        if (! $currentSeason) {
            return;
        }

        // Łańcuch "gry w górę" - kilku zawodników z młodszej drużyny dogrywa też w starszej.
        $promotionChain = [
            'mlodzik-2013' => 'mlodzik-2012',
            'mlodzik-2012' => 'junior-mlodszy-2011',
            'junior-mlodszy-2011' => 'junior-mlodszy-2010',
            'junior-mlodszy-2010' => 'junior-2009',
            'junior-2009' => 'junior-2008',
            'junior-2008' => 'seniorzy',
        ];

        foreach ($promotionChain as $youngerSlug => $olderSlug) {
            $younger = $teams->get($youngerSlug);
            $older = $teams->get($olderSlug);

            if (! $younger || ! $older) {
                continue;
            }

            $promoted = $younger->activePlayers()->inRandomOrder()->limit(3)->get();

            foreach ($promoted as $player) {
                if ($older->seasonRoster($currentSeason->id)->where('players.id', $player->id)->exists()) {
                    continue;
                }

                $older->seasonRoster($currentSeason->id)->attach($player->id, [
                    'jersey_number' => null,
                    'is_captain' => false,
                ]);
            }
        }
    }
}

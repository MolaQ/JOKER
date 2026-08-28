<?php

namespace Database\Factories;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isHome = fake()->boolean();
        $status = fake()->randomElement(['scheduled', 'finished', 'cancelled']);
        $gameDate = fake()->dateTimeBetween('-2 months', '+3 months');

        return [
            'opponent' => fake()->randomElement([
                'AZS Politechnika Warszawska',
                'MKS Bedłów',
                'Exact Systems Norwid Częstochowa',
                'BKS Visla Bydgoszcz',
                'Ślepsk Malow Słupsk',
                'AZS AGH Kraków',
            ]),
            'is_home' => $isHome,
            'game_date' => $gameDate,
            'venue' => $isHome ? 'Hala Sportowa Joker Piła' : fake()->city(),
            'league' => 'Tauron 1 Liga',
            'status' => $status,
            'home_score' => $status === 'finished' ? fake()->numberBetween(0, 3) : null,
            'away_score' => $status === 'finished' ? fake()->numberBetween(0, 3) : null,
            'sets_score' => $status === 'finished' ? [
                [25, fake()->numberBetween(15, 27)],
                [fake()->numberBetween(15, 27), 25],
                [25, fake()->numberBetween(20, 23)],
            ] : null,
        ];
    }
}

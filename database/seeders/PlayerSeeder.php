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
     * Przykładowe opisy zawodników.
     *
     * @var array<int, string>
     */
    private const BIOS = [
        'W klubie od pierwszych treningów. Najmocniejsza strona: zagrywka z wyskoku. Poza siatkówką - gitara i dobre książki.',
        'Przyszedł do Jokera z koszykówki i szybko pokochał siatkę. Pracowity, zawsze ostatni schodzi z treningu.',
        'Lider szatni i głośny doping na ławce. Marzy o grze w PlusLidze.',
        'Świetnie czyta grę i potrafi uspokoić zespół w trudnych momentach seta.',
        'Wychowanek klubu, powołany na konsultację kadry wojewódzkiej. Kibicuje Projektowi Warszawa.',
        'Najlepszy blokujący drużyny w poprzednim sezonie. Uczy się programowania.',
        'Szybki, zwinny i nieustępliwy w obronie. Po treningu zawsze z piłką do żonglerki.',
        'Debiutant w tym sezonie, ale już zdobył zaufanie trenera. Lubi wyzwania.',
    ];

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
                    'first_name' => fake('pl_PL')->firstNameMale(),
                    'last_name' => fake('pl_PL')->lastNameMale(),
                    'jersey_number' => $jerseyNumber,
                    'position' => fake()->randomElement(PlayerPosition::cases()),
                    'birth_date' => $birthDate,
                    'height' => fake()->numberBetween(170, 210),
                    'weight' => fake()->numberBetween(60, 100),
                    'reach' => fake()->numberBetween(220, 250),
                    'spike_reach' => fake()->numberBetween(250, 340),
                    'bio' => '<p>'.fake()->randomElement(self::BIOS).'</p>',
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
            'mlodzicy-ii' => 'mlodzicy',
            'mlodzicy' => 'kadeci-ii',
            'kadeci-ii' => 'kadeci',
            'kadeci' => 'juniorzy-ii',
            'juniorzy-ii' => 'juniorzy',
            'juniorzy' => 'seniorzy',
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

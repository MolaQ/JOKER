<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\CompetitionLevel;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\PlayerPosition;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Klub: konta, sezony, poziomy rozgrywek, drużyny ze sztabem, zawodnicy i rozgrywki.
 */
class ClubSeeder extends Seeder
{
    /**
     * Trenerzy i sztab (przykładowe osoby) - wykorzystywane przez TeamSeeder.
     *
     * @var array<int, array{name: string, email: string, role: UserRole, bio: string}>
     */
    public const STAFF = [
        ['name' => 'Tomasz Wiśniewski', 'email' => 'tomasz.wisniewski@jokerpila.pl', 'role' => UserRole::Trainer,
            'bio' => 'Trener II klasy, były zawodnik pierwszoligowy. W Jokerze od 2015 roku, prowadzi pierwszą drużynę. Stawia na szybką grę ze środka i mocną zagrywkę.'],
        ['name' => 'Katarzyna Zielińska', 'email' => 'katarzyna.zielinska@jokerpila.pl', 'role' => UserRole::Trainer,
            'bio' => 'Absolwentka AWF w Poznaniu, specjalizacja: siatkówka. Wychowała kilkunastu reprezentantów województwa. Uwielbia pracę z juniorami.'],
        ['name' => 'Paweł Lewandowski', 'email' => 'pawel.lewandowski@jokerpila.pl', 'role' => UserRole::Trainer,
            'bio' => 'Trener przygotowania motorycznego i siatkówki młodzieżowej. Wcześniej asystent w klubach ligi kadetów.'],
        ['name' => 'Magdalena Wójcik', 'email' => 'magdalena.wojcik@jokerpila.pl', 'role' => UserRole::Trainer,
            'bio' => 'Instruktorka siatkówki i nauczycielka WF. Prowadzi grupy młodzików i pierwsze kroki najmłodszych w klubie.'],
        ['name' => 'Krzysztof Kamiński', 'email' => 'krzysztof.kaminski@jokerpila.pl', 'role' => UserRole::Trainer,
            'bio' => 'Były libero, dziś trener specjalista od przyjęcia i obrony. Prowadzi treningi indywidualne dla wszystkich roczników.'],
        ['name' => 'Agnieszka Dąbrowska', 'email' => 'agnieszka.dabrowska@jokerpila.pl', 'role' => UserRole::Staff,
            'bio' => 'Fizjoterapeutka sportowa. Dba o regenerację i profilaktykę urazów zawodników wszystkich drużyn.'],
        ['name' => 'Michał Kozłowski', 'email' => 'michal.kozlowski@jokerpila.pl', 'role' => UserRole::Staff,
            'bio' => 'Statystyk i analityk wideo. Przygotowuje raporty z meczów i analizę gry rywali.'],
        ['name' => 'Ewa Jankowska', 'email' => 'ewa.jankowska@jokerpila.pl', 'role' => UserRole::Staff,
            'bio' => 'Kierowniczka drużyn młodzieżowych. Ogarnia wyjazdy, licencje i kontakt z rodzicami.'],
        ['name' => 'Piotr Mazur', 'email' => 'piotr.mazur@jokerpila.pl', 'role' => UserRole::Staff,
            'bio' => 'Kierownik pierwszej drużyny. Odpowiada za organizację meczów domowych i logistykę.'],
    ];

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

    /** Strony Wielkopolskiego Związku Piłki Siatkowej, z których pobieramy mecze i tabele. */
    private const WZPS = 'https://wzps.poznan.pl/obsady/index.php?';

    /** Wyniki i tabele (mają pierwszeństwo przed WZPS), bez godzin i hal. */
    private const SITE = 'https://wielkopolskasiatkowka.com.pl/';

    public function run(): void
    {
        $this->seedUsers();
        $this->seedSeasons();
        $this->seedCompetitionLevels();
        $this->seedTeams();
        $this->seedPlayers();
        $this->seedCompetitions();
    }

    private function seedUsers(): void
    {
        // Administrator strony. Hasło: ADMIN_PASSWORD z .env albo losowe (wypisane w konsoli).
        $adminPassword = env('ADMIN_PASSWORD') ?: Str::password(14, symbols: false);

        User::create([
            'name' => 'Marcin Molak',
            'email' => 'marcin.molak@gmail.com',
            'password' => Hash::make($adminPassword),
            'role' => UserRole::Admin,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        if (! env('ADMIN_PASSWORD')) {
            $this->command?->warn("Administrator: marcin.molak@gmail.com / hasło: {$adminPassword}");
        }

        foreach (self::STAFF as $person) {
            User::create([
                'name' => $person['name'],
                'email' => $person['email'],
                'password' => Hash::make(Str::random(32)),
                'role' => $person['role'],
                'additional_info' => '<p>'.$person['bio'].'</p>',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        User::factory()->count(10)->create(['role' => UserRole::Parent]);
        User::factory()->count(20)->create(['role' => UserRole::Player]);
        User::factory()->count(15)->create(['role' => UserRole::Fan]);
    }

    /**
     * Dwa sezony archiwalne i aktualny (sezon startuje 1 września).
     */
    public function seedSeasons(): void
    {
        $currentStartYear = now()->month >= 8 ? now()->year : now()->year - 1;

        foreach ([2, 1, 0] as $yearsAgo) {
            $startYear = $currentStartYear - $yearsAgo;

            Season::create([
                'name' => $startYear.'/'.($startYear + 1),
                'start_date' => "{$startYear}-09-01",
                'end_date' => ($startYear + 1).'-06-30',
                'is_current' => $yearsAgo === 0,
            ]);
        }
    }

    /**
     * Poziomy rozgrywek od PlusLigi do ligi okręgowej.
     */
    public function seedCompetitionLevels(): void
    {
        $levels = [
            ['name' => 'PlusLiga', 'display_order' => 1],
            ['name' => '1 liga', 'display_order' => 2],
            ['name' => '2 liga', 'display_order' => 3],
            ['name' => '3 liga', 'display_order' => 4],
            ['name' => 'międzywojewódzka', 'display_order' => 5],
            ['name' => 'wojewódzka', 'display_order' => 6],
            ['name' => 'okręgowa', 'display_order' => 7],
        ];

        foreach ($levels as $level) {
            CompetitionLevel::create($level);
        }
    }

    /**
     * Drużyny klubu zgłoszone w WZPS (lista: wzps.poznan.pl/obsady/index.php?mode=8&club_id=7).
     * Każda drużyna ma trenera głównego i sztab z opisanymi funkcjami.
     */
    private function seedTeams(): void
    {
        $people = User::whereIn('email', array_column(self::STAFF, 'email'))->get()->keyBy('name');
        $id = fn (string $name) => $people->get($name)?->id;

        $teams = [
            ['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior', 'birth_year' => null,
                'description' => 'Pierwsza drużyna Joker Piła, występująca w II lidze mężczyzn. Grać tu może, poza stałymi zawodnikami, każdy wyróżniający się zawodnik klubu.',
                'coach' => 'Tomasz Wiśniewski',
                'staff' => ['Krzysztof Kamiński' => 'Asystent trenera', 'Agnieszka Dąbrowska' => 'Fizjoterapeutka', 'Michał Kozłowski' => 'Statystyk', 'Piotr Mazur' => 'Kierownik drużyny']],
            ['name' => 'Juniorzy', 'slug' => 'juniorzy', 'category' => 'junior', 'birth_year' => 2008,
                'description' => 'Pierwsza drużyna juniorów. Ostatni krok przed seniorską siatkówką.',
                'coach' => 'Katarzyna Zielińska',
                'staff' => ['Paweł Lewandowski' => 'Trener przygotowania motorycznego', 'Agnieszka Dąbrowska' => 'Fizjoterapeutka', 'Ewa Jankowska' => 'Kierowniczka drużyny']],
            ['name' => 'Juniorzy II', 'slug' => 'juniorzy-ii', 'category' => 'junior', 'birth_year' => 2009,
                'description' => 'Druga drużyna juniorów. Gra też w III lidze mężczyzn jako „LOMS Joker Piła 1”.',
                'coach' => 'Katarzyna Zielińska',
                'staff' => ['Krzysztof Kamiński' => 'Asystent trenera', 'Ewa Jankowska' => 'Kierowniczka drużyny']],
            ['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'junior_mlodszy', 'birth_year' => 2010,
                'description' => 'Pierwsza drużyna kadetów. W III lidze mężczyzn występuje jako „LOMS Joker Piła 2”.',
                'coach' => 'Paweł Lewandowski',
                'staff' => ['Magdalena Wójcik' => 'Asystentka trenera', 'Ewa Jankowska' => 'Kierowniczka drużyny']],
            ['name' => 'Kadeci II', 'slug' => 'kadeci-ii', 'category' => 'junior_mlodszy', 'birth_year' => 2011,
                'description' => 'Druga drużyna kadetów. Zawodnicy są wypożyczeni do klubu KS ASBL Piła i w Lidze Kadetów grają jako ASBL.',
                'coach' => 'Paweł Lewandowski',
                'staff' => ['Krzysztof Kamiński' => 'Trener przyjęcia i obrony']],
            ['name' => 'Młodzicy', 'slug' => 'mlodzicy', 'category' => 'mlodzik', 'birth_year' => 2012,
                'description' => 'Pierwsza drużyna młodzików. Siatkówka, która cieszy - dużo gry i dobrej zabawy.',
                'coach' => 'Magdalena Wójcik',
                'staff' => ['Ewa Jankowska' => 'Kierowniczka drużyny']],
            ['name' => 'Młodzicy II', 'slug' => 'mlodzicy-ii', 'category' => 'mlodzik', 'birth_year' => 2013,
                'description' => 'Druga drużyna młodzików. Tu zaczyna się przygoda z Jokerem!',
                'coach' => 'Magdalena Wójcik',
                'staff' => ['Paweł Lewandowski' => 'Asystent trenera']],
        ];

        foreach ($teams as $order => $data) {
            $team = Team::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'],
                'category' => $data['category'],
                'birth_year' => $data['birth_year'],
                'trainer_id' => $id($data['coach']),
                'display_order' => $order + 1,
            ]);

            foreach ($data['staff'] as $name => $role) {
                if ($memberId = $id($name)) {
                    $team->assistantTrainers()->attach($memberId, ['role' => $role]);
                }
            }
        }
    }

    /**
     * Run the database seeds.
     *
     * Każda drużyna dostaje własny skład (players.team_id = drużyna macierzysta).
     * Dodatkowo, dla aktualnego sezonu, kilku najstarszych zawodników z każdej
     * młodszej drużyny dogrywa też w drużynie starszego rocznika (team_player_season),
     * zgodnie z zasadą, że młodsi zawodnicy mogą grać ze starszymi.
     */
    private function seedPlayers(): void
    {
        $currentSeason = Season::current()->first();
        $playerUsers = User::withRole(UserRole::Player)->get();

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

    /**
     * Rozgrywki naszych drużyn w bieżącym sezonie. Terminarz, wyniki i tabele pobiera `php artisan joker:sync-schedules`.
     * Przy drużynie zapisane są nazwy (oddzielone średnikiem), pod którymi występuje w danych rozgrywkach.
     * Można uruchomić ponownie: istniejących rozgrywek (ta sama nazwa i sezon) nie dubluje; uzupełnia tylko
     * brakujący adres stron związku, poziom i drużyny.
     */
    public function seedCompetitions(): void
    {
        $season = Season::current()->first();

        if (! $season) {
            $this->command?->warn('Brak bieżącego sezonu - rozgrywki pominięte. Ustaw sezon jako bieżący w panelu: Sezony.');

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
            $competition = Competition::firstOrCreate(['name' => $data['name'], 'season_id' => $season->id], [
                'level' => $data['level'],
                'level_id' => $levels->get($data['level'])?->id,
                'description' => "Rozgrywki WZPS w sezonie {$season->name}.",
                'display_order' => $index + 1,
                'source_url' => implode("\n", $data['sources']),
            ]);

            if (blank($competition->source_url) || blank($competition->level)) {
                $competition->update([
                    'source_url' => $competition->source_url ?: implode("\n", $data['sources']),
                    'level' => $competition->level ?: $data['level'],
                ]);
            }

            $attached = $competition->teams()->pluck('teams.id');

            foreach ($data['teams'] as $slug => $externalName) {
                if (($team = $teams->get($slug)) && ! $attached->contains($team->id)) {
                    $competition->teams()->attach($team->id, ['external_name' => $externalName] + Competition::defaultTeamLabel($team->name, $team->slug, $externalName));
                }
            }
        }

        $this->command?->info("Rozgrywki sezonu {$season->name}: ".count($competitions).' z adresami stron związku. Wyniki pobierze: php artisan joker:sync-schedules');
    }
}

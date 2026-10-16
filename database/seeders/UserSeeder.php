<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
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

    public function run(): void
    {
        // Administrator strony. Hasło: ADMIN_PASSWORD z .env albo losowe (wypisane w konsoli).
        $adminPassword = env('ADMIN_PASSWORD') ?: Str::password(14, symbols: false);

        User::create([
            'name' => 'Marcin Molak',
            'email' => 'marcin.molak@gmail.com',
            'password' => Hash::make($adminPassword),
            'role' => UserRole::Admin,
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
                'email_verified_at' => now(),
            ]);
        }

        User::factory()->count(10)->create(['role' => UserRole::Parent]);
        User::factory()->count(20)->create(['role' => UserRole::Player]);
        User::factory()->count(15)->create(['role' => UserRole::Fan]);
    }
}

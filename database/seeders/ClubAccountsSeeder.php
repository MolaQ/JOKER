<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\PlayerPosition;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Prawdziwe dane klubu na start: 7 drużyn, trenerzy, konta administratora, szefa
 * i zawodnika. Adresy i hasła kont są w .env (config/seed.php). Można uruchamiać
 * wielokrotnie: niczego nie dubluje i nie zmienia haseł istniejących kont.
 */
class ClubAccountsSeeder extends Seeder
{
    /** Drużyny w kolejności wyświetlania. */
    private const TEAMS = [
        ['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior'],
        ['name' => 'Juniorzy', 'slug' => 'juniorzy', 'category' => 'junior'],
        ['name' => 'Juniorzy II', 'slug' => 'juniorzy-ii', 'category' => 'junior'],
        ['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'junior_mlodszy'],
        ['name' => 'Kadeci II', 'slug' => 'kadeci-ii', 'category' => 'junior_mlodszy', 'description' => 'Zawodnicy są wypożyczeni do klubu KS ASBL Piła i w Lidze Kadetów grają jako ASBL.'],
        ['name' => 'Młodzicy', 'slug' => 'mlodzicy', 'category' => 'mlodzik'],
        ['name' => 'Młodzicy II', 'slug' => 'mlodzicy-ii', 'category' => 'mlodzik'],
    ];

    /**
     * Trenerzy bez adresu e-mail: profil w sztabie bez możliwości logowania,
     * dopóki administrator nie wpisze prawdziwego adresu i nie aktywuje konta.
     * Kolumny: drużyny prowadzone jako trener główny, drużyny jako drugi trener.
     */
    private const TRAINERS = [
        'Tomasz Aszemberg' => [['seniorzy', 'juniorzy'], []],
        'Roman Pilch' => [['juniorzy-ii'], []],
        'Artur Sierzchuła' => [['kadeci'], []],
        'Szymon Pietraszak' => [[], ['kadeci']],
        'Jarosław Kubiak' => [['kadeci-ii'], []],
    ];

    public function run(): void
    {
        $teams = $this->seedTeams();

        foreach (self::TRAINERS as $name => [$headOf, $assistantOf]) {
            $trainer = User::firstWhere('name', $name) ?? User::create([
                'name' => $name,
                'email' => Str::slug($name, '.').'@brak-emaila.jokerpila.pl',
                'password' => Str::password(32),
                'role' => UserRole::Trainer,
                'is_active' => false,
            ]);
            $this->coach($trainer, $teams, $headOf, $assistantOf);
        }

        $admin = $this->account('admin', [UserRole::Admin, UserRole::Parent]);
        $boss = $this->account('boss', [UserRole::Boss, UserRole::Trainer]);
        $playerAccount = $this->account('player', [UserRole::Player]);

        if ($boss) {
            $this->coach($boss, $teams, ['mlodzicy'], []);
        }

        $marcel = $this->seedMarcel($teams, $playerAccount);
        $admin?->children()->syncWithoutDetaching([$marcel->id]);
    }

    /**
     * @return array<string, Team>
     */
    private function seedTeams(): array
    {
        $teams = [];

        foreach (self::TEAMS as $order => $data) {
            $teams[$data['slug']] = Team::firstOrCreate(['slug' => $data['slug']], [
                'name' => $data['name'],
                'category' => $data['category'],
                'description' => $data['description'] ?? null,
                'display_order' => $order + 1,
            ]);
        }

        return $teams;
    }

    /**
     * @param  array<string, Team>  $teams
     * @param  list<string>  $headOf
     * @param  list<string>  $assistantOf
     */
    private function coach(User $user, array $teams, array $headOf, array $assistantOf): void
    {
        foreach ($headOf as $slug) {
            if ($teams[$slug]->trainer_id === null) {
                $teams[$slug]->update(['trainer_id' => $user->id]);
            }
        }

        foreach ($assistantOf as $slug) {
            $teams[$slug]->assistantTrainers()->syncWithoutDetaching([$user->id => ['role' => 'Trener']]);
        }
    }

    /**
     * Konto z adresem z .env. Bez adresu pomija konto; bez hasła losuje je i wypisuje w konsoli.
     *
     * @param  list<UserRole>  $roles
     */
    private function account(string $key, array $roles): ?User
    {
        $config = config("seed.{$key}");

        if (blank($config['email'])) {
            $this->command?->warn("Pominięto konto ({$key}): brak adresu w .env (SEED_".strtoupper($key === 'boss' ? 'szef' : $key).'_EMAIL).');

            return null;
        }

        $user = User::firstWhere('email', $config['email']);

        if (! $user) {
            $password = $config['password'] ?: Str::password(14, symbols: false);
            $user = User::create([
                'name' => $config['name'],
                'email' => $config['email'],
                'password' => $password,
                'is_active' => true,
                'activated_at' => now(),
                'email_verified_at' => now(),
            ]);

            if (blank($config['password'])) {
                $this->command?->warn("{$config['email']}: wylosowane hasło {$password} (zmień je po zalogowaniu w „Mój profil”).");
            }
        }

        foreach ($roles as $role) {
            $user->assignRole($role);
        }

        return $user;
    }

    /**
     * Marcel Molak: atakujący z numerem 10, drużyna Kadeci, w tym sezonie gra też w Juniorach II.
     *
     * @param  array<string, Team>  $teams
     */
    private function seedMarcel(array $teams, ?User $account): Player
    {
        $player = Player::firstOrCreate(['first_name' => 'Marcel', 'last_name' => 'Molak'], [
            'team_id' => $teams['kadeci']->id,
            'position' => PlayerPosition::Opposite,
            'jersey_number' => 10,
            'is_active' => true,
        ]);

        if ($account && $player->user_id === null) {
            $player->update(['user_id' => $account->id]);
        }

        if ($season = Season::current()->first()) {
            foreach (['kadeci', 'juniorzy-ii'] as $slug) {
                $player->teams()->wherePivot('season_id', $season->id)->syncWithoutDetaching([$teams[$slug]->id => ['season_id' => $season->id, 'jersey_number' => 10]]);
            }
        }

        return $player;
    }
}

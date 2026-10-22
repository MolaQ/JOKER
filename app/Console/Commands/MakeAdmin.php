<?php

namespace App\Console\Commands;

use App\Models\User;
use App\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Nadaje uprawnienia administratora (tworzy konto, jeśli go nie ma).
 * Użycie: php artisan joker:admin marcin.molak@gmail.com
 */
class MakeAdmin extends Command
{
    protected $signature = 'joker:admin {email} {--name=}';

    protected $description = 'Nadaj uprawnienia administratora wskazanemu adresowi e-mail';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::firstWhere('email', $email);

        if ($user) {
            $user->assignRole(UserRole::Admin);
            $user->forceFill(['is_active' => true, 'activated_at' => $user->activated_at ?? now()])->save();
            $this->info("{$email} jest teraz administratorem.");

            return self::SUCCESS;
        }

        $password = Str::password(14, symbols: false);

        User::create([
            'name' => $this->option('name') ?: Str::of($email)->before('@')->replace(['.', '_'], ' ')->title()->toString(),
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Admin,
            'is_active' => true,
            'activated_at' => now(),
            'email_verified_at' => now(),
        ]);

        $this->info("Utworzono administratora {$email}");
        $this->warn("Hasło: {$password} (zmienisz je w panelu: Użytkownicy)");

        return self::SUCCESS;
    }
}

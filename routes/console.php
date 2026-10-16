<?php

use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/*
 * Nadaje uprawnienia administratora (tworzy konto, jeśli go nie ma).
 * Użycie: php artisan joker:admin marcin.molak@gmail.com
 */
Artisan::command('joker:admin {email} {--name=}', function (string $email) {
    $user = User::firstWhere('email', $email);

    if ($user) {
        $user->update(['role' => UserRole::Admin]);
        $this->info("{$email} jest teraz administratorem.");

        return;
    }

    $password = Str::password(14, symbols: false);

    User::create([
        'name' => $this->option('name') ?: Str::of($email)->before('@')->replace(['.', '_'], ' ')->title()->toString(),
        'email' => $email,
        'password' => $password,
        'role' => UserRole::Admin,
        'email_verified_at' => now(),
    ]);

    $this->info("Utworzono administratora {$email}");
    $this->warn("Hasło: {$password} (zmienisz je w panelu: Użytkownicy)");
})->purpose('Nadaj uprawnienia administratora wskazanemu adresowi e-mail');

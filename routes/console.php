<?php

use App\Models\Competition;
use App\Models\User;
use App\Services\ScheduleImporter;
use App\UserRole;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
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

/*
 * Pobiera terminarze i wyniki ze stron związku dla rozgrywek z wpisanym adresem.
 * Działa samo co 3 godziny, jeśli na serwerze jest ustawiony cron z `php artisan schedule:run`.
 */
Artisan::command('joker:sync-schedules', function (ScheduleImporter $importer) {
    // Najpierw rozgrywki z tabelą wyników - to one „zabierają” swoje mecze ze stron drużyn.
    $competitions = Competition::whereNotNull('source_url')->where('source_url', '!=', '')->get()
        ->sortBy(fn (Competition $competition) => str_contains($competition->source_url, 'mode=12') ? 0 : 1);

    foreach ($competitions as $competition) {
        $importer->sync($competition);
        $this->line("{$competition->name}: {$competition->sync_message}");
    }

    if ($competitions->isEmpty()) {
        $this->info('Żadne rozgrywki nie mają adresu terminarza.');
    }
})->purpose('Pobierz terminarze i wyniki ze stron związku');

Schedule::command('joker:sync-schedules')->everyThreeHours()->withoutOverlapping();

<?php

use App\Console\Commands\ExpireRoles;
use App\Console\Commands\SendApprovalReminders;
use App\Console\Commands\SendMessageEmails;
use App\Console\Commands\SendUpcomingReminders;
use App\Console\Commands\SyncSchedules;
use App\Models\AuditLog;
use App\Models\PageView;
use Illuminate\Support\Facades\Schedule;

/*
 * Komendy klubu są w app/Console/Commands (joker:admin, joker:sync-schedules).
 * Terminarz i wyniki odświeżają się co 3 godziny, jeśli na serwerze działa `php artisan schedule:run` z crona.
 */
Schedule::command(SyncSchedules::class)->everyThreeHours()->withoutOverlapping();

// Usuwanie statystyk odwiedzin starszych niż ok. 13 miesięcy.
Schedule::command('model:prune', ['--model' => [PageView::class]])->daily();

// Role i uprawnienia z datą „ważne do” po jej minięciu znikają z kont (wpis w dzienniku zmian).
Schedule::command(ExpireRoles::class)->hourly();

// E-maile o sprawach czekających na zatwierdzenie (dla osób, które je włączyły).
Schedule::command(SendApprovalReminders::class)->hourly()->between('7:00', '21:00');

// Dziennik zmian trzyma wpisy z ostatniego roku.
Schedule::command('model:prune', ['--model' => [AuditLog::class]])->daily();

// Zbiorczy e-mail o wiadomościach nieprzeczytanych od kwadransa (cron na serwerze uruchamia harmonogram co 5 minut).
Schedule::command(SendMessageEmails::class)->everyFiveMinutes()->withoutOverlapping();

// Powiadomienia push „mecz za chwilę”: przy każdym uruchomieniu harmonogramu (cron co kilka minut).
Schedule::command(SendUpcomingReminders::class)->everyMinute()->withoutOverlapping();

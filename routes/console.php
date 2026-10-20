<?php

use App\Console\Commands\SyncSchedules;
use App\Models\PageView;
use Illuminate\Support\Facades\Schedule;

/*
 * Komendy klubu są w app/Console/Commands (joker:admin, joker:sync-schedules).
 * Terminarz i wyniki odświeżają się co 3 godziny, jeśli na serwerze działa `php artisan schedule:run` z crona.
 */
Schedule::command(SyncSchedules::class)->everyThreeHours()->withoutOverlapping();

// Usuwanie statystyk odwiedzin starszych niż ok. 13 miesięcy.
Schedule::command('model:prune', ['--model' => [PageView::class]])->daily();

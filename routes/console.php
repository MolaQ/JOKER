<?php

use App\Console\Commands\SyncSchedules;
use Illuminate\Support\Facades\Schedule;

/*
 * Komendy klubu są w app/Console/Commands (joker:admin, joker:sync-schedules).
 * Terminarz i wyniki odświeżają się co 3 godziny, jeśli na serwerze działa `php artisan schedule:run` z crona.
 */
Schedule::command(SyncSchedules::class)->everyThreeHours()->withoutOverlapping();

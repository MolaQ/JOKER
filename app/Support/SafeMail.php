<?php

namespace App\Support;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Wysyłka e-maila, która nie przerywa pracy w panelu: gdy poczta na serwerze nie działa (zły host, hasło,
 * brak połączenia), zapisujemy błąd w logu, a aktywacja konta czy odrzucenie prośby i tak się kończy.
 */
class SafeMail
{
    public static function notify(object $notifiable, Notification $notification): bool
    {
        try {
            $notifiable->notify($notification);

            return true;
        } catch (Throwable $exception) {
            Log::error('Nie udało się wysłać e-maila: '.class_basename($notification), [
                'to' => $notifiable->email ?? null,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public static function failureNote(): string
    {
        return ' Nie udało się wysłać e-maila (poczta na serwerze nie działa), szczegóły są w logu serwera.';
    }
}

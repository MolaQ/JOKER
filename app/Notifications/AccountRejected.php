<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Wiadomość o odrzuceniu prośby o aktywację: z powodem i drogą do poprawienia danych.
 */
class AccountRejected extends Notification
{
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Prośba o aktywację konta w serwisie '.config('app.name'))
            ->greeting('Cześć, '.$notifiable->name.'!')
            ->line('Klub nie mógł jeszcze aktywować Twojego konta.')
            ->line('Powód: „'.$notifiable->rejection_reason.'”')
            ->line('Możesz poprawić dane i wysłać prośbę jeszcze raz w zakładce „Mój profil”.')
            ->action('Popraw dane', route('profile.edit'))
            ->salutation('Pozdrawiamy, '.config('app.name'));
    }
}

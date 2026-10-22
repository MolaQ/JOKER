<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Wiadomość do osoby, której konto klub właśnie aktywował.
 */
class AccountActivated extends Notification
{
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Twoje konto w serwisie '.config('app.name').' jest aktywne')
            ->greeting('Cześć, '.$notifiable->name.'!')
            ->line('Klub aktywował Twoje konto. Role: '.$notifiable->roles()->map->label()->join(', ').'.')
            ->line('W zakładce „Mój profil” zobaczysz powiązane profile i status swoich propozycji.')
            ->action('Otwórz mój profil', route('profile.edit'))
            ->salutation('Pozdrawiamy, '.config('app.name'));
    }
}

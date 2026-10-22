<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Powitanie konta założonego przez klub: link do ustawienia własnego hasła.
 */
class ClubAccountCreated extends Notification
{
    public function __construct(public string $token) {}

    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Twoje konto w serwisie '.config('app.name'))
            ->greeting('Cześć, '.$notifiable->name.'!')
            ->line('Klub założył Ci konto na stronie. Możesz się logować, komentować i proponować zmiany w profilu zawodnika.')
            ->line('Na początek ustaw własne hasło:')
            ->action('Ustaw hasło', route('password.reset', ['token' => $this->token, 'email' => $notifiable->email]))
            ->line('Link działa przez '.config('auth.passwords.users.expire').' minut. Gdy wygaśnie, kliknij „Nie pamiętasz hasła?” na stronie logowania i wpisz ten adres e-mail.')
            ->line('Przy pierwszym logowaniu poprosimy o zgody RODO.')
            ->salutation('Pozdrawiamy, '.config('app.name'));
    }
}

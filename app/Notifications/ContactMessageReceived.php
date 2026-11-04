<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Kopia wiadomości z formularza na adres klubu; „Odpowiedz” w poczcie pisze od razu do nadawcy.
 */
class ContactMessageReceived extends Notification
{
    public function __construct(public ContactMessage $contact) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Formularz kontaktowy: '.$this->contact->subject)
            ->replyTo($this->contact->email, $this->contact->name)
            ->greeting('Nowa wiadomość ze strony')
            ->line('Od: '.$this->contact->name.' <'.$this->contact->email.'>')
            ->line('Temat: '.$this->contact->subject)
            ->line($this->contact->message)
            ->action('Otwórz w panelu', route('admin.contact-messages.index'))
            ->line('Odpowiedz na ten e-mail, a odpowiedź trafi prosto do nadawcy.')
            ->salutation(config('app.name'));
    }
}

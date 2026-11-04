<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use App\Support\SiteSettings;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Odpowiedź klubu na wiadomość z formularza kontaktowego, wysłana z panelu.
 * „Odpowiedz” u odbiorcy trafia na e-mail klubu z ustawień, a nie na adres serwera.
 */
class ContactReply extends Notification
{
    public function __construct(public ContactMessage $contact, public string $body, public string $signature) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $site = app(SiteSettings::class);
        $message = (new MailMessage)
            ->subject('Re: '.$this->contact->subject)
            ->greeting('Dzień dobry, '.$this->contact->name.'!');

        if (filter_var($site['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $message->replyTo($site['contact_email'], $site['club_name']);
        }

        foreach (preg_split('/\R{2,}/u', trim($this->body)) as $paragraph) {
            $message->line($paragraph);
        }

        return $message
            ->line('— Twoja wiadomość z '.$this->contact->created_at->format('d.m.Y').': „'.Str::limit($this->contact->message, 400).'”')
            ->salutation('Pozdrawiamy,'."\n".$this->signature.', '.$site['club_name']);
    }
}

<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Zbiorczy e-mail o nieprzeczytanych wiadomościach (jeden na kilka wiadomości, nie przy każdej).
 */
class MessagesWaiting extends Notification
{
    /**
     * @param  list<array{title: string, count: int, author: string, excerpt: string}>  $conversations
     */
    public function __construct(public array $conversations) {}

    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $total = array_sum(array_column($this->conversations, 'count'));
        $few = $total % 10 >= 2 && $total % 10 <= 4 && ($total % 100 < 10 || $total % 100 >= 20);
        $message = (new MailMessage)
            ->subject(($total === 1 ? 'Nowa wiadomość' : $total.($few ? ' nowe' : ' nowych').' wiadomości').' w serwisie '.config('app.name'))
            ->greeting('Cześć, '.$notifiable->name.'!')
            ->line($total === 1 ? 'Czeka na Ciebie nieprzeczytana wiadomość:' : 'Czekają na Ciebie nieprzeczytane wiadomości:');

        foreach (array_slice($this->conversations, 0, 5) as $conversation) {
            $message->line('• '.$conversation['title'].($conversation['count'] > 1 ? ' ('.$conversation['count'].')' : '').': '.$conversation['author'].' – „'.$conversation['excerpt'].'”');
        }

        if (count($this->conversations) > 5) {
            $message->line('…i jeszcze '.(count($this->conversations) - 5).' rozmowy.');
        }

        return $message
            ->action('Otwórz wiadomości', route('messages.index'))
            ->line('Te e-maile wyłączysz na stronie „Wiadomości”.')
            ->salutation('Pozdrawiamy, '.config('app.name'));
    }
}

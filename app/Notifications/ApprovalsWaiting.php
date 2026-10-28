<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Przypomnienie dla osoby, która zatwierdza: ile czeka w każdej zakładce „Do zatwierdzenia”.
 */
class ApprovalsWaiting extends Notification
{
    /**
     * @param  array<string, int>  $counts  np. ['Rodzice do przypisania' => 2]
     */
    public function __construct(public array $counts) {}

    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $total = array_sum($this->counts);
        $message = (new MailMessage)
            ->subject('Do zatwierdzenia: '.$total.' '.($total === 1 ? 'sprawa' : ($total % 10 >= 2 && $total % 10 <= 4 && ($total % 100 < 10 || $total % 100 >= 20) ? 'sprawy' : 'spraw')).' w serwisie '.config('app.name'))
            ->greeting('Cześć, '.$notifiable->name.'!')
            ->line('Na Twoją decyzję czeka:');

        foreach ($this->counts as $label => $count) {
            $message->line('• '.$label.': '.$count);
        }

        return $message
            ->action('Otwórz „Do zatwierdzenia”', route('admin.approvals.index'))
            ->line('Te e-maile wyłączysz na stronie „Do zatwierdzenia” w panelu.')
            ->salutation('Pozdrawiamy, '.config('app.name'));
    }
}

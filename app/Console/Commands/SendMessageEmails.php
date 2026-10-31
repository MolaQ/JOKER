<?php

namespace App\Console\Commands;

use App\AccountStatus;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\MessagesWaiting;
use App\Support\SiteSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Zbiorczy e-mail o wiadomościach, które od kwadransa leżą nieprzeczytane. Jedna osoba dostaje e-mail
 * tylko o wiadomościach nowszych niż poprzedni taki e-mail, więc rozmowa grupowa nie zasypie skrzynki.
 */
class SendMessageEmails extends Command
{
    protected $signature = 'joker:message-emails';

    protected $description = 'Wyślij e-maile o nieprzeczytanych wiadomościach';

    /** Po tylu minutach nieprzeczytana wiadomość trafia do e-maila (wcześniej wystarczy dzwonek i push). */
    public const DELAY_MINUTES = 15;

    public function handle(): int
    {
        if (! app(SiteSettings::class)->get('messages_enabled')) {
            return self::SUCCESS;
        }

        $cutoff = now()->subMinutes(self::DELAY_MINUTES);

        $userIds = DB::table('conversation_user as cu')
            ->join('messages as m', 'm.conversation_id', '=', 'cu.conversation_id')
            ->where('m.created_at', '<=', $cutoff)
            ->where('m.created_at', '>', now()->subDays(7))
            ->whereNull('m.hidden_at')
            ->where(fn ($q) => $q->whereNull('m.user_id')->orWhereColumn('m.user_id', '!=', 'cu.user_id'))
            ->whereColumn('m.id', '>', 'cu.last_read_message_id')
            ->distinct()
            ->pluck('cu.user_id');

        $sent = 0;

        User::whereKey($userIds)->where('notify_messages', true)->withAccountStatus(AccountStatus::Active)->whereNotNull('email')
            ->each(function (User $user) use ($cutoff, &$sent) {
                $messages = Message::unreadBy($user)
                    ->where('messages.created_at', '<=', $cutoff)
                    ->when($user->messages_emailed_at, fn ($q, $at) => $q->where('messages.created_at', '>', $at))
                    ->with(['author:id,name', 'conversation.participants:id,name', 'conversation.team:id,name'])
                    ->orderBy('messages.created_at')
                    ->get();

                if ($messages->isEmpty()) {
                    return;
                }

                $user->notify(new MessagesWaiting($messages->groupBy('conversation_id')->map(function ($items) use ($user) {
                    $last = $items->last();
                    /** @var Conversation $conversation */
                    $conversation = $last->conversation;

                    return [
                        'title' => $conversation->titleFor($user),
                        'count' => $items->count(),
                        'author' => $last->author?->name ?? 'Konto usunięte',
                        'excerpt' => Str::limit(preg_replace('/\s+/', ' ', $last->body), 80),
                    ];
                })->values()->all()));

                $user->forceFill(['messages_emailed_at' => now()])->saveQuietly();
                $sent++;
            });

        $this->info("Wysłane e-maile: {$sent}");

        return self::SUCCESS;
    }
}

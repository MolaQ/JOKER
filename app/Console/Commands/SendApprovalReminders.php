<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ApprovalsWaiting;
use App\Support\Approvals;
use Illuminate\Console\Command;

/**
 * E-mail do osób, które zatwierdzają i włączyły powiadomienia: gdy przybyło spraw albo czekają ponad dobę.
 */
class SendApprovalReminders extends Command
{
    protected $signature = 'joker:approval-reminders';

    protected $description = 'Wyślij e-maile o sprawach czekających na zatwierdzenie';

    public function handle(): int
    {
        User::where('notify_approvals', true)->where('is_active', true)->get()->each(function (User $user) {
            $sections = Approvals::sectionsFor($user)->filter(fn (array $section) => $section['count'] > 0);
            $total = $sections->sum('count');

            if ($total === 0) {
                if ($user->approvals_notified_count !== 0) {
                    $user->forceFill(['approvals_notified_count' => 0])->saveQuietly();
                }

                return;
            }

            $grew = $total > $user->approvals_notified_count;
            $stale = $user->approvals_notified_at === null || $user->approvals_notified_at->lt(now()->subDay());

            if (! $grew && ! $stale) {
                return;
            }

            $user->notify(new ApprovalsWaiting($sections->mapWithKeys(fn (array $section) => [$section['label'] => $section['count']])->all()));
            $user->forceFill(['approvals_notified_count' => $total, 'approvals_notified_at' => now()])->saveQuietly();
        });

        return self::SUCCESS;
    }
}

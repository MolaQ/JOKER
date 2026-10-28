<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Message;
use App\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Wiadomości zgłoszone przez uczestników rozmów: moderator ukrywa je u wszystkich albo zostawia.
 * Osobie, która łamie zasady, odbiera się uprawnienie „Pisanie wiadomości” w edycji konta.
 */
class ReportedMessages extends Component
{
    use ShowsToasts;

    public function boot(): void
    {
        abort_unless(Auth::user()?->hasPermission(Permission::Comments), 403);
    }

    public static function pendingCount(): int
    {
        return Message::reported()->count();
    }

    public function hide(int $messageId): void
    {
        Message::reported()->whereKey($messageId)->update(['hidden_at' => now(), 'hidden_by' => Auth::id()]);
        $this->toast('Wiadomość ukryta u wszystkich uczestników rozmowy.');
    }

    public function dismiss(int $messageId): void
    {
        Message::reported()->whereKey($messageId)->update(['reported_at' => null, 'reported_by' => null]);
        $this->toast('Zgłoszenie odrzucone, wiadomość zostaje.');
    }

    public function render(): View
    {
        return view('livewire.admin.reported-messages', [
            'messages' => Message::reported()
                ->with(['author:id,name', 'reporter:id,name', 'conversation.participants:id,name', 'conversation.team:id,name'])
                ->orderBy('reported_at')
                ->get(),
        ]);
    }
}

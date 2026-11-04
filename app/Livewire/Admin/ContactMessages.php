<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactReply;
use App\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/**
 * Skrzynka formularza kontaktowego w „Do zatwierdzenia”: czytają ją szef i administrator.
 */
class ContactMessages extends Component
{
    use ShowsToasts, WithPagination;

    /** Wiadomość, na którą właśnie odpowiadamy z panelu. */
    public ?int $replyingId = null;

    public string $replyBody = '';

    public static function canRead(?User $user): bool
    {
        return (bool) $user?->hasPermission(Permission::Users);
    }

    public static function unreadCount(): int
    {
        return ContactMessage::unread()->count();
    }

    public function boot(): void
    {
        abort_unless(self::canRead(Auth::user()), 403);
    }

    public function markRead(int $id): void
    {
        ContactMessage::unread()->whereKey($id)->update(['read_at' => now(), 'read_by' => Auth::id()]);
    }

    public function markUnread(int $id): void
    {
        ContactMessage::whereKey($id)->update(['read_at' => null, 'read_by' => null]);
    }

    public function startReply(int $id): void
    {
        $this->replyingId = ContactMessage::findOrFail($id)->id;
        $this->replyBody = '';
        $this->resetValidation();
    }

    public function cancelReply(): void
    {
        $this->replyingId = null;
        $this->replyBody = '';
    }

    /**
     * Wysyła odpowiedź e-mailem do nadawcy i zapisuje ją przy wiadomości. Gdy poczta zawiedzie, nic nie zapisujemy.
     */
    public function sendReply(): void
    {
        $contact = ContactMessage::findOrFail($this->replyingId);
        $this->validate(['replyBody' => 'required|string|min:2|max:5000'], ['replyBody.required' => 'Napisz treść odpowiedzi.']);
        $me = Auth::user();

        try {
            Notification::route('mail', [$contact->email => $contact->name])->notify(new ContactReply($contact, $this->replyBody, $me->name));
        } catch (Throwable $exception) {
            Log::error('Nie udało się wysłać e-maila: ContactReply', ['to' => $contact->email, 'error' => $exception->getMessage()]);
            $this->toast('Nie udało się wysłać odpowiedzi (poczta na serwerze). Sprawdź pocztę w Ustawieniach systemu albo odpowiedz ze swojej skrzynki.', 'error');

            return;
        }

        $contact->update([
            'reply_body' => trim($this->replyBody),
            'replied_at' => now(),
            'replied_by' => $me->id,
            'read_at' => $contact->read_at ?? now(),
            'read_by' => $contact->read_by ?? $me->id,
        ]);
        AuditLog::record('contact.replied', 'Odpowiedź na wiadomość z formularza #'.$contact->id, null);

        $this->cancelReply();
        $this->toast('Odpowiedź wysłana do '.$contact->email.'.');
    }

    public function delete(int $id): void
    {
        $contact = ContactMessage::findOrFail($id);
        AuditLog::record('contact.deleted', 'Usunięto wiadomość z formularza kontaktowego #'.$contact->id, null);
        $contact->delete();
        $this->toast('Wiadomość usunięta.');
    }

    public function render(): View
    {
        return view('livewire.admin.contact-messages', [
            'messages' => ContactMessage::with(['reader:id,name', 'replier:id,name'])
                ->orderByRaw('read_at is not null')
                ->latest()
                ->paginate(20),
        ]);
    }
}

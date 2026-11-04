<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\User;
use App\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Skrzynka formularza kontaktowego w „Do zatwierdzenia”: czytają ją szef i administrator.
 */
class ContactMessages extends Component
{
    use ShowsToasts, WithPagination;

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
            'messages' => ContactMessage::with('reader:id,name')
                ->orderByRaw('read_at is not null')
                ->latest()
                ->paginate(20),
        ]);
    }
}

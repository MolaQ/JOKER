<?php

namespace App\Livewire\Admin;

use App\Models\ProfileChangeRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ProfileChangeRequestManager extends Component
{
    use WithPagination;

    public string $statusFilter = 'pending';

    public string $reviewNote = '';

    public ?int $reviewingId = null;

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function selectForReview(int $id): void
    {
        $this->reviewingId = $id;
        $this->reviewNote = '';
    }

    public function approve(int $id): void
    {
        abort_unless(Auth::user()?->can('users.manage'), 403);

        $request = ProfileChangeRequest::with('user')->findOrFail($id);

        if ($request->status !== 'pending') {
            return;
        }

        $data = collect($request->proposed_data)
            ->only(['name', 'email', 'instagram', 'additional_info', 'social_links', 'avatar_path'])
            ->toArray();

        validator($data, [
            // Propozycja samego zdjęcia nie niesie imienia ani e-maila.
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user_id)],
            'avatar_path' => 'sometimes|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'additional_info' => 'nullable|string|max:2000',
            'social_links' => 'nullable|array',
            'social_links.*' => ['nullable', 'string', 'max:500', 'regex:#^https?://#i'],
        ])->validate();

        // Konto zatwierdzone przez klub: imię i nazwisko zmienia się tylko w Użytkownikach.
        if ($request->user->nameIsLocked() && array_key_exists('name', $data)) {
            $data['name'] = $request->user->name;
        }

        if (filled($data['avatar_path'] ?? null) && $request->user->avatar_path && $request->user->avatar_path !== $data['avatar_path']) {
            Storage::disk('public')->delete($request->user->avatar_path);
        }

        if (array_key_exists('social_links', $data)) {
            // Nowe propozycje: cała lista serwisów; stare pole Instagram idzie za nią.
            $data['social_links'] = array_filter((array) $data['social_links']) ?: null;
            $data['instagram'] = $data['social_links']['instagram'] ?? null;
        } elseif (array_key_exists('instagram', $data)) {
            // Propozycje sprzed listy serwisów: sam Instagram trafia na listę.
            $links = (array) ($request->user->social_links ?? []);
            $handle = trim((string) $data['instagram']);
            $links['instagram'] = $handle === '' ? null : (preg_match('#^https?://#i', $handle) ? $handle : 'https://www.instagram.com/'.ltrim($handle, '@/'));
            $data['social_links'] = array_filter($links) ?: null;
        }

        $request->user->update($data);

        $request->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'review_note' => $this->reviewNote,
            'reviewed_at' => now(),
        ]);

        $this->reviewingId = null;
        $this->reviewNote = '';
    }

    public function reject(int $id): void
    {
        abort_unless(Auth::user()?->can('users.manage'), 403);

        $request = ProfileChangeRequest::findOrFail($id);

        if ($request->status !== 'pending') {
            return;
        }

        // Odrzucone zdjęcie nie zostaje na serwerze.
        if (filled($request->proposed_data['avatar_path'] ?? null)) {
            Storage::disk('public')->delete($request->proposed_data['avatar_path']);
        }

        $request->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'review_note' => $this->reviewNote,
            'reviewed_at' => now(),
        ]);

        $this->reviewingId = null;
        $this->reviewNote = '';
    }

    public function render()
    {
        abort_unless(Auth::user()?->can('users.manage'), 403);

        $requests = ProfileChangeRequest::with(['user', 'reviewer'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest('id')
            ->paginate(15);

        return view('livewire.admin.profile-change-request-manager', [
            'requests' => $requests,
        ]);
    }
}

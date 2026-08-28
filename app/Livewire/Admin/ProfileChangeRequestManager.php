<?php

namespace App\Livewire\Admin;

use App\Models\ProfileChangeRequest;
use Illuminate\Support\Facades\Auth;
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
        $request = ProfileChangeRequest::with('user')->findOrFail($id);

        if ($request->status !== 'pending') {
            return;
        }

        $data = collect($request->proposed_data)
            ->only(['name', 'email', 'instagram', 'additional_info'])
            ->toArray();

        validator($data, [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user_id)],
            'instagram' => 'nullable|string|max:255',
            'additional_info' => 'nullable|string|max:2000',
        ])->validate();

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
        $request = ProfileChangeRequest::findOrFail($id);

        if ($request->status !== 'pending') {
            return;
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
        $requests = ProfileChangeRequest::with(['user', 'reviewer'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest('id')
            ->paginate(15);

        return view('livewire.admin.profile-change-request-manager', [
            'requests' => $requests,
        ]);
    }
}

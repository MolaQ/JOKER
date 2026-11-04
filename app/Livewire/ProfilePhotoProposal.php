<?php

namespace App\Livewire;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\ProfileChangeRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Trener albo członek sztabu proponuje w „Mój profil” nowe zdjęcie (kadr 4:5 jak u zawodników).
 * Zdjęcie trafia do „Do zatwierdzenia → Zmiany profili kont”; na profilu pojawia się po zatwierdzeniu.
 */
class ProfilePhotoProposal extends Component
{
    use ShowsToasts, WithFileUploads;

    public $photo = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->isStaffMember(), 403);
    }

    public function submit(): void
    {
        $this->validate(['photo' => 'required|image|max:4096|dimensions:ratio=4/5'], [], ['photo' => __('zdjęcie')]);

        ProfileChangeRequest::create([
            'user_id' => Auth::id(),
            'proposed_data' => ['avatar_path' => $this->photo->store('avatars/proposals', 'public')],
            'status' => 'pending',
        ]);

        $this->photo = null;
        $this->toast(__('Dziękujemy! Zdjęcie pojawi się na profilu po zatwierdzeniu.'));
    }

    public function render(): View
    {
        return view('livewire.profile-photo-proposal', [
            'pending' => ProfileChangeRequest::where('user_id', Auth::id())->where('status', 'pending')->get()
                ->first(fn (ProfileChangeRequest $request) => filled($request->proposed_data['avatar_path'] ?? null)),
        ]);
    }
}

<?php

namespace App\Livewire;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\GalleryPhoto;
use App\Models\PhotoTag;
use App\Models\Player;
use App\Models\User;
use App\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Rodzic lub zawodnik proponuje oznaczenie zawodnika na zdjęciu.
 * Oznaczenie pojawi się po zatwierdzeniu (redaktor, szef, admin albo trener drużyny).
 */
class PhotoTagProposal extends Component
{
    use ShowsToasts;

    public ?int $photoId = null;

    public string $search = '';

    public static function canPropose(?User $user): bool
    {
        return (bool) $user?->is_active && $user->hasRole(UserRole::Parent, UserRole::Player);
    }

    #[On('propose-tag')]
    public function open(int $photo): void
    {
        abort_unless(self::canPropose(Auth::user()), 403);

        $this->photoId = GalleryPhoto::whereHas('gallery', fn ($gallery) => $gallery->published())->findOrFail($photo)->id;
        $this->search = '';
    }

    public function close(): void
    {
        $this->photoId = null;
    }

    public function propose(int $playerId): void
    {
        abort_unless(self::canPropose(Auth::user()) && $this->photoId, 403);
        $player = Player::where('is_active', true)->findOrFail($playerId);

        $tag = PhotoTag::firstOrCreate(
            ['gallery_photo_id' => $this->photoId, 'taggable_type' => $player->getMorphClass(), 'taggable_id' => $player->id],
            ['status' => PhotoTag::STATUS_PENDING, 'proposed_by' => Auth::id()],
        );

        $this->photoId = null;
        $this->toast($tag->wasRecentlyCreated
            ? __('Dziękujemy! Oznaczenie pojawi się po zatwierdzeniu.')
            : __('To oznaczenie już jest albo czeka na zatwierdzenie.'));
    }

    public function render(): View
    {
        $user = Auth::user();
        $search = trim($this->search);

        // Najpierw własny profil i dzieci, potem wyniki wyszukiwania.
        $suggested = $this->photoId && $user
            ? Player::where('is_active', true)->where(fn ($query) => $query->where('user_id', $user->id)->orWhereHas('guardians', fn ($guardians) => $guardians->whereKey($user->id)))->get()
            : collect();

        $results = $this->photoId && mb_strlen($search) >= 2
            ? Player::with('team')->where('is_active', true)->where(fn ($query) => $query->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))->orderBy('last_name')->limit(10)->get()
            : collect();

        return view('livewire.photo-tag-proposal', [
            'photo' => $this->photoId ? GalleryPhoto::find($this->photoId) : null,
            'players' => $suggested->concat($results)->unique('id')->values(),
        ]);
    }
}

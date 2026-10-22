<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\AuditLog;
use App\Models\PhotoTag;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Oznaczenia zaproponowane przez rodziców i zawodników, czekające na zatwierdzenie.
 */
class PhotoTagReview extends Component
{
    use ShowsToasts;

    /** Na stronie „Do zatwierdzenia” pokazuje też informację, że nic nie czeka. */
    public bool $showEmpty = false;

    /**
     * @return Collection<int, PhotoTag>
     */
    public static function pendingFor(User $user): Collection
    {
        return PhotoTag::pending()
            ->with(['taggable', 'photo.gallery', 'proposer'])
            ->latest('id')
            ->get()
            ->filter(fn (PhotoTag $tag) => $tag->isReviewableBy($user))
            ->values();
    }

    public function approve(int $id): void
    {
        $tag = $this->reviewable($id);
        $tag->update(['status' => PhotoTag::STATUS_APPROVED, 'reviewed_by' => Auth::id()]);
        AuditLog::record('tag.approved', 'Zatwierdzono oznaczenie '.$tag->label().' (proponuje '.$tag->proposer?->name.')', $tag);

        $this->toast('Oznaczenie zatwierdzone.');
    }

    public function reject(int $id): void
    {
        $this->reviewable($id)->delete();

        $this->toast('Oznaczenie odrzucone.');
    }

    private function reviewable(int $id): PhotoTag
    {
        $tag = PhotoTag::pending()->with('taggable')->findOrFail($id);
        abort_unless($tag->isReviewableBy(Auth::user()), 403);

        return $tag;
    }

    public function render(): View
    {
        return view('livewire.admin.photo-tag-review', ['tags' => self::pendingFor(Auth::user())]);
    }
}

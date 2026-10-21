<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\PlayerChangeRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Propozycje zmian profili i zdjęć zawodników od nich samych i ich rodziców.
 * Administrator widzi wszystkie, trener - zawodników swoich drużyn.
 */
class PlayerChangeRequestManager extends Component
{
    use ShowsToasts, WithPagination;

    public string $statusFilter = PlayerChangeRequest::STATUS_PENDING;

    /** @var array<int, string> Notatki do decyzji, klucz = id zgłoszenia */
    public array $notes = [];

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function approve(int $id): void
    {
        $request = $this->reviewable($id);

        if ($request->isPhoto()) {
            $request->player->update(['photo_path' => $this->movePhoto($request->photo_path)]);
        } else {
            $data = collect($request->proposed_data)->only(array_keys(PlayerChangeRequest::EDITABLE_FIELDS));

            if ($data->has('bio')) {
                $data['bio'] = PlayerChangeRequest::bioAsHtml($data['bio']);
            }

            $request->player->update($data->all());
        }

        $this->decide($request, PlayerChangeRequest::STATUS_APPROVED);
        $this->toast('Zatwierdzono - profil zawodnika '.$request->player->full_name.' jest zaktualizowany.');
    }

    public function reject(int $id): void
    {
        $request = $this->reviewable($id);

        if ($request->isPhoto() && $request->photo_path) {
            Storage::disk('public')->delete($request->photo_path);
        }

        $this->decide($request, PlayerChangeRequest::STATUS_REJECTED);
        $this->toast('Odrzucono propozycję dla '.$request->player->full_name.'.');
    }

    private function reviewable(int $id): PlayerChangeRequest
    {
        $request = PlayerChangeRequest::with('player')->pending()->findOrFail($id);

        abort_unless($request->player->isReviewableBy(Auth::user()), 403);

        return $request;
    }

    private function decide(PlayerChangeRequest $request, string $status): void
    {
        $request->update([
            'status' => $status,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => trim($this->notes[$request->id] ?? '') ?: null,
        ]);

        unset($this->notes[$request->id]);
    }

    /**
     * Zatwierdzone zdjęcie przenosimy z poczekalni do zdjęć zawodników.
     */
    private function movePhoto(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return $path;
        }

        $target = 'players/'.basename($path);
        Storage::disk('public')->move($path, $target);

        return $target;
    }

    public function render(): View
    {
        $requests = PlayerChangeRequest::with(['player.team', 'submitter', 'reviewer'])
            ->reviewableBy(Auth::user())
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('id')
            ->paginate(15);

        return view('livewire.admin.player-change-request-manager', [
            'requests' => $requests,
            'labels' => PlayerChangeRequest::EDITABLE_FIELDS,
        ]);
    }
}

<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\ContentProposal;
use App\Models\GalleryPhoto;
use App\Support\ContentProposalPublisher;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Propozycje treści od rodziców, zawodników i współpracowników: artykuł staje się aktualnością,
 * zdjęcia trafiają do galerii meczu, transmisja do meczu, logo do drużyny rywali.
 */
class ContentProposalManager extends Component
{
    use ShowsToasts, WithPagination;

    public string $statusFilter = ContentProposal::STATUS_PENDING;

    /** @var array<int, string> Notatki do decyzji, klucz = id propozycji */
    public array $notes = [];

    /** @var array<int, list<int>> Zdjęcia odznaczone przy zatwierdzaniu: id propozycji => numery zdjęć, które nie trafią do galerii */
    public array $skipped = [];

    public function toggleSkip(int $id, int $index): void
    {
        $current = $this->skipped[$id] ?? [];
        $this->skipped[$id] = in_array($index, $current, true) ? array_values(array_diff($current, [$index])) : [...$current, $index];
    }

    public function setAllPhotos(int $id, bool $selected): void
    {
        $proposal = ContentProposal::pending()->findOrFail($id);
        $this->skipped[$id] = $selected ? [] : array_keys($proposal->files ?? []);
    }

    /**
     * Zostawia w propozycji zdjęć tylko zaznaczone pliki (odznaczone usuwa). False, gdy nie zaznaczono żadnego.
     */
    private function applySelection(ContentProposal $proposal): bool
    {
        $skipped = $this->skipped[$proposal->id] ?? [];

        if ($proposal->type !== ContentProposal::TYPE_PHOTOS || $skipped === []) {
            return true;
        }

        $files = collect($proposal->files ?? []);
        $kept = $files->reject(fn (string $path, int $index) => in_array($index, $skipped, true))->values();

        if ($kept->isEmpty()) {
            return false;
        }

        $files->diff($kept)->each(fn (string $path) => Storage::disk('public')->delete($path));
        $proposal->files = $kept->all();
        unset($this->skipped[$proposal->id]);

        return true;
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function approve(int $id): void
    {
        $proposal = ContentProposal::pending()->with(['match', 'user'])->findOrFail($id);

        if (! $this->applySelection($proposal)) {
            $this->toast('Zaznacz co najmniej jedno zdjęcie albo odrzuć propozycję.', 'error');

            return;
        }

        DB::transaction(function () use ($proposal) {
            $result = (new ContentProposalPublisher)->publish($proposal);

            // Pliki zostały przeniesione do artykułu lub galerii.
            $proposal->files = null;
            $this->decide($proposal, ContentProposal::STATUS_APPROVED);
            AuditLog::record('proposal.approved', 'Zatwierdzono propozycję ('.$proposal->typeLabel().') od '.$proposal->user->name.': '.$result, $proposal);
        });

        $this->toast('Propozycja zatwierdzona i opublikowana.');
    }

    /**
     * Zdjęcia z relacją (rodzic napisał tekst, ale wysłał propozycję jako „Zdjęcia”):
     * zdjęcia idą do galerii meczu, a tekst staje się aktualnością z pierwszym zdjęciem.
     */
    public function approveWithArticle(int $id): void
    {
        $proposal = ContentProposal::pending()->with(['match', 'user'])->findOrFail($id);
        abort_unless($this->canBecomeArticle($proposal), 422);

        if (! $this->applySelection($proposal)) {
            $this->toast('Zaznacz co najmniej jedno zdjęcie albo odrzuć propozycję.', 'error');

            return;
        }

        DB::transaction(function () use ($proposal) {
            $paths = [];
            $publisher = new ContentProposalPublisher;
            $photos = $publisher->addPhotos($proposal, $paths);
            $article = $publisher->createArticle($proposal, $paths[0] ?? null);

            $proposal->files = null;
            $this->decide($proposal, ContentProposal::STATUS_APPROVED);
            AuditLog::record('proposal.approved', 'Zatwierdzono propozycję (zdjęcia z relacją) od '.$proposal->user->name.': '.$photos.' i artykuł „'.$article->title.'”', $proposal);
        });

        $this->toast('Zdjęcia dodane do galerii, relacja opublikowana jako artykuł.');
    }

    /**
     * Relacja z już zatwierdzonej propozycji zdjęć, która przed poprawką trafiła tylko do galerii.
     */
    public function publishReport(int $id): void
    {
        $proposal = ContentProposal::where('status', ContentProposal::STATUS_APPROVED)->with(['match', 'user'])->findOrFail($id);
        abort_unless($this->canBecomeArticle($proposal) && ! $this->hasArticle($proposal), 422);

        // Zdjęcia z tej propozycji dodano do galerii w chwili zatwierdzenia.
        $photo = $proposal->reviewed_at && $proposal->match
            ? GalleryPhoto::whereIn('gallery_id', $proposal->match->galleries()->pluck('id'))
                ->whereBetween('created_at', [$proposal->reviewed_at->copy()->subMinutes(2), $proposal->reviewed_at->copy()->addMinutes(2)])
                ->orderBy('display_order')
                ->value('path')
            : null;

        $article = (new ContentProposalPublisher)->createArticle($proposal, $photo);
        AuditLog::record('proposal.approved', 'Opublikowano relację z propozycji zdjęć od '.$proposal->user->name.': artykuł „'.$article->title.'”', $proposal);

        $this->toast('Relacja opublikowana jako artykuł.');
    }

    /**
     * Propozycja zdjęć, do której autor dopisał tekst relacji.
     */
    public function canBecomeArticle(ContentProposal $proposal): bool
    {
        return $proposal->type === ContentProposal::TYPE_PHOTOS && filled(trim((string) $proposal->content));
    }

    public function hasArticle(ContentProposal $proposal): bool
    {
        return Article::where('author_id', $proposal->user_id)->where('title', ContentProposalPublisher::articleTitle($proposal))->exists();
    }

    public function reject(int $id): void
    {
        $proposal = ContentProposal::pending()->findOrFail($id);

        $proposal->deleteFiles();
        $proposal->files = null;
        $this->decide($proposal, ContentProposal::STATUS_REJECTED);

        $this->toast('Propozycja odrzucona.');
    }

    private function decide(ContentProposal $proposal, string $status): void
    {
        $proposal->fill([
            'status' => $status,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => trim($this->notes[$proposal->id] ?? '') ?: null,
        ])->save();

        unset($this->notes[$proposal->id]);
    }

    public function matchName(?Model $match): string
    {
        return ContentProposalPublisher::matchName($match);
    }

    public function render(): View
    {
        return view('livewire.admin.content-proposal-manager', [
            'proposals' => ContentProposal::with(['user', 'reviewer', 'match'])
                ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
                ->latest('id')
                ->paginate(15),
        ]);
    }
}

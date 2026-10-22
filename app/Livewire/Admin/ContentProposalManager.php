<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\ContentProposal;
use App\Models\Gallery;
use App\Models\Game;
use App\Models\TournamentMatch;
use App\Support\Slug;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Propozycje treści od rodziców i zawodników: artykuł staje się aktualnością,
 * zdjęcia trafiają do galerii meczu, transmisja do meczu.
 */
class ContentProposalManager extends Component
{
    use ShowsToasts, WithPagination;

    public string $statusFilter = ContentProposal::STATUS_PENDING;

    /** @var array<int, string> Notatki do decyzji, klucz = id propozycji */
    public array $notes = [];

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function approve(int $id): void
    {
        $proposal = ContentProposal::pending()->with(['match', 'user'])->findOrFail($id);

        DB::transaction(function () use ($proposal) {
            $result = match ($proposal->type) {
                ContentProposal::TYPE_ARTICLE => $this->publishArticle($proposal),
                ContentProposal::TYPE_PHOTOS => $this->addPhotos($proposal),
                ContentProposal::TYPE_STREAM => $this->addStream($proposal),
            };

            // Pliki zostały przeniesione do artykułu lub galerii.
            $proposal->files = null;
            $this->decide($proposal, ContentProposal::STATUS_APPROVED);
            AuditLog::record('proposal.approved', 'Zatwierdzono propozycję ('.$proposal->typeLabel().') od '.$proposal->user->name.': '.$result, $proposal);
        });

        $this->toast('Propozycja zatwierdzona i opublikowana.');
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

    private function publishArticle(ContentProposal $proposal): string
    {
        $paragraphs = collect(preg_split('/\R{2,}/', trim((string) $proposal->content)))
            ->map(fn (string $paragraph) => '<p>'.nl2br(e(trim($paragraph)), false).'</p>')
            ->implode('');
        $image = collect($proposal->files ?? [])->first();

        $article = Article::create([
            'title' => $proposal->title,
            'slug' => Slug::unique(Article::class, (string) $proposal->title),
            'excerpt' => Str::limit(trim((string) $proposal->content), 200),
            'content' => $paragraphs,
            'featured_image' => $image ? $this->move($image, 'articles') : null,
            'author_id' => $proposal->user_id,
            'status' => 'published',
            'published_at' => now(),
            'allow_comments' => true,
        ]);

        if ($proposal->match instanceof Game) {
            $article->games()->attach($proposal->match->id);
        }

        return 'artykuł „'.$article->title.'”';
    }

    private function addPhotos(ContentProposal $proposal): string
    {
        $match = $proposal->match ?? abort(422, 'Mecz tej propozycji już nie istnieje.');

        /** @var Gallery $gallery */
        $gallery = $match->galleries()->oldest('id')->first() ?? $match->galleries()->create([
            'title' => 'Zdjęcia: '.$this->matchName($match),
            'is_published' => true,
        ]);

        $order = (int) $gallery->photos()->max('display_order');

        foreach (array_values($proposal->files ?? []) as $index => $path) {
            $gallery->photos()->create([
                'path' => $this->move($path, 'galleries/'.$gallery->id),
                'display_order' => $order + $index + 1,
            ]);
        }

        return count($proposal->files ?? []).' zdj. w galerii „'.$gallery->title.'”';
    }

    private function addStream(ContentProposal $proposal): string
    {
        $match = $proposal->match ?? abort(422, 'Mecz tej propozycji już nie istnieje.');
        $streams = collect($match->streams ?? [])->push(['url' => $proposal->url, 'label' => null])->unique('url')->values()->all();
        $match->update(['streams' => $streams]);

        return 'transmisja '.$proposal->url;
    }

    private function move(string $path, string $directory): string
    {
        $target = $directory.'/'.basename($path);
        Storage::disk('public')->move($path, $target);

        return $target;
    }

    public function matchName(?Model $match): string
    {
        return match (true) {
            $match instanceof Game => implode(' – ', $match->sidesNames()),
            $match instanceof TournamentMatch => $match->homeName().' – '.$match->awayName(),
            default => '',
        };
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

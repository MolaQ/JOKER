<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\ContentProposal;
use App\Models\Gallery;
use App\Models\GalleryPhoto;
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

    /**
     * Zdjęcia z relacją (rodzic napisał tekst, ale wysłał propozycję jako „Zdjęcia”):
     * zdjęcia idą do galerii meczu, a tekst staje się aktualnością z pierwszym zdjęciem.
     */
    public function approveWithArticle(int $id): void
    {
        $proposal = ContentProposal::pending()->with(['match', 'user'])->findOrFail($id);
        abort_unless($this->canBecomeArticle($proposal), 422);

        DB::transaction(function () use ($proposal) {
            $paths = [];
            $photos = $this->addPhotos($proposal, $paths);
            $article = $this->createArticle($proposal, $paths[0] ?? null);

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

        $article = $this->createArticle($proposal, $photo);
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
        return Article::where('author_id', $proposal->user_id)->where('title', $this->articleTitle($proposal))->exists();
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
        $image = collect($proposal->files ?? [])->first();
        $article = $this->createArticle($proposal, $image ? $this->move($image, 'articles') : null, copy: false);

        return 'artykuł „'.$article->title.'”';
    }

    /**
     * @param  string|null  $image  zdjęcie na dysku public; przy $copy kopiowane do articles (oryginał zostaje w galerii)
     */
    private function createArticle(ContentProposal $proposal, ?string $image, bool $copy = true): Article
    {
        $paragraphs = collect(preg_split('/\R{2,}/u', trim((string) $proposal->content)))
            ->map(fn (string $paragraph) => '<p>'.nl2br(e(trim($paragraph)), false).'</p>')
            ->implode('');

        if ($image && $copy) {
            $target = 'articles/'.Str::random(8).'-'.basename($image);
            $image = Storage::disk('public')->copy($image, $target) ? $target : null;
        }

        $title = $this->articleTitle($proposal);
        $article = Article::create([
            'title' => $title,
            'slug' => Slug::unique(Article::class, $title),
            'excerpt' => Str::limit(trim((string) $proposal->content), 200),
            'content' => $paragraphs,
            'featured_image' => $image,
            'author_id' => $proposal->user_id,
            'status' => 'published',
            'published_at' => now(),
            'allow_comments' => true,
        ]);

        if ($proposal->match instanceof Game) {
            $article->games()->attach($proposal->match->id);
        }

        return $article;
    }

    /**
     * Tytuł z propozycji, a gdy go brak: pierwsze zdanie relacji.
     */
    private function articleTitle(ContentProposal $proposal): string
    {
        $firstLine = Str::of((string) $proposal->content)->trim()->explode("\n")->first();

        return trim((string) $proposal->title) ?: Str::limit(trim((string) $firstLine), 120);
    }

    /**
     * @param  array<int, string>  $paths  ścieżki dodanych zdjęć (do zdjęcia artykułu)
     */
    private function addPhotos(ContentProposal $proposal, array &$paths = []): string
    {
        $match = $proposal->match ?? abort(422, 'Mecz tej propozycji już nie istnieje.');

        /** @var Gallery $gallery */
        $gallery = $match->galleries()->oldest('id')->first() ?? $match->galleries()->create([
            'title' => 'Zdjęcia: '.$this->matchName($match),
            'is_published' => true,
        ]);

        $order = (int) $gallery->photos()->max('display_order');

        foreach (array_values($proposal->files ?? []) as $index => $path) {
            $paths[] = $gallery->photos()->create([
                'path' => $this->move($path, 'galleries/'.$gallery->id),
                'display_order' => $order + $index + 1,
            ])->path;
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

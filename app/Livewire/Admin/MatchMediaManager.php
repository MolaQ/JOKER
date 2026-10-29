<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Gallery;
use App\Models\GalleryPhoto;
use App\Models\Game;
use App\Models\TournamentMatch;
use App\Support\StreamLink;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Transmisje (YouTube, Facebook) i galerie zdjęć przypięte do konkretnego meczu ligowego albo turniejowego.
 */
class MatchMediaManager extends Component
{
    use ShowsToasts, WithFileUploads;

    /** @var array<string, class-string<Model>> */
    public const MATCH_TYPES = [
        'game' => Game::class,
        'tournament_match' => TournamentMatch::class,
    ];

    /** Ile meczów każdego rodzaju pokazać na liście wyboru. */
    private const PICKER_LIMIT = 150;

    public string $search = '';

    /** Wybrany mecz jako „rodzaj:id”, np. „game:12” albo „tournament_match:5”. */
    #[Url(as: 'match')]
    public string $selected = '';

    /** @var array<int, array{url: string, label: string}> */
    public array $streams = [];

    public string $galleryTitle = '';

    public string $galleryDescription = '';

    public bool $galleryPublished = true;

    /** @var array<int, TemporaryUploadedFile> */
    public array $galleryPhotos = [];

    public ?int $editingGalleryId = null;

    public string $editTitle = '';

    public string $editDescription = '';

    /** @var array<int, string> podpisy zdjęć edytowanej galerii (klucz: id zdjęcia) */
    public array $captions = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $morePhotos = [];

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('media.manage'), 403);

        if ($this->selectedMatch() === null) {
            $this->selected = '';
        }

        $this->loadStreams();
        $this->galleryTitle = $this->defaultGalleryTitle();
    }

    public function updatedSelected(): void
    {
        $this->loadStreams();
        $this->cancelEdit();
        $this->reset(['galleryTitle', 'galleryDescription', 'galleryPhotos']);
        $this->galleryPublished = true;
        $this->resetErrorBag();
        $this->galleryTitle = $this->defaultGalleryTitle();
    }

    /**
     * Mecze do wyboru, pogrupowane i od najnowszych, przefiltrowane wpisanym tekstem.
     *
     * @return array<string, array<string, string>>
     */
    #[Computed]
    public function matchOptions(): array
    {
        $words = collect(preg_split('/\s+/', Str::lower(trim($this->search))))->filter();
        $matches = fn (string $label) => $words->every(fn (string $word) => Str::contains(Str::lower($label), $word));

        $games = Game::with(['team', 'competition.teams', 'opponentTeam', 'tournament'])
            ->orderByDesc('game_date')
            ->get()
            ->mapWithKeys(fn (Game $game) => ['game:'.$game->id => $game->mediaLabel()]);

        $tournamentMatches = TournamentMatch::with(['category.tournament', 'group', 'home.competitor', 'away.competitor'])
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(fn (TournamentMatch $match) => ['tournament_match:'.$match->id => $match->mediaLabel()]);

        $limit = fn (Collection $options) => $options
            ->filter(fn (string $label, string $key) => $key === $this->selected || $matches($label))
            ->take(self::PICKER_LIMIT)
            ->all();

        return array_filter([
            'Mecze ligowe' => $limit($games),
            'Turnieje' => $limit($tournamentMatches),
        ]);
    }

    public function selectedMatch(): ?Model
    {
        [$type, $id] = array_pad(explode(':', $this->selected, 2), 2, null);
        $class = self::MATCH_TYPES[$type] ?? null;

        return $class && ctype_digit((string) $id) ? $class::find((int) $id) : null;
    }

    public function addStream(): void
    {
        $this->streams[] = ['url' => '', 'label' => ''];
    }

    public function removeStream(int $index): void
    {
        unset($this->streams[$index]);
        $this->streams = array_values($this->streams);
    }

    public function saveStreams(): void
    {
        $match = $this->requireMatch();

        $this->streams = collect($this->streams)
            ->map(fn (array $stream) => ['url' => trim((string) ($stream['url'] ?? '')), 'label' => trim((string) ($stream['label'] ?? ''))])
            ->reject(fn (array $stream) => $stream['url'] === '' && $stream['label'] === '')
            ->values()
            ->all();

        $this->validate([
            'streams' => 'array|max:10',
            'streams.*.url' => 'required|url:http,https|max:500',
            'streams.*.label' => 'nullable|string|max:100',
        ], [
            'streams.*.url.required' => 'Wpisz adres transmisji.',
            'streams.*.url.url' => 'To nie jest poprawny adres (zacznij od https://).',
        ]);

        $match->update(['streams' => $this->streams === [] ? null : $this->streams]);

        $this->toast('Transmisje zostały zapisane.');
    }

    public function createGallery(): void
    {
        $match = $this->requireMatch();

        $this->validate([
            'galleryTitle' => 'required|string|max:255',
            'galleryDescription' => 'nullable|string|max:2000',
            'galleryPhotos' => 'array|max:100',
            'galleryPhotos.*' => 'image|max:10240',
        ], [
            'galleryTitle.required' => 'Podaj tytuł galerii.',
            'galleryPhotos.*.image' => 'Każdy plik musi być zdjęciem.',
            'galleryPhotos.*.max' => 'Zdjęcie może mieć najwyżej 10 MB.',
        ]);

        /** @var Gallery $gallery */
        $gallery = $match->galleries()->create([
            'title' => $this->galleryTitle,
            'description' => $this->galleryDescription ?: null,
            'is_published' => $this->galleryPublished,
        ]);

        $this->storePhotos($gallery, $this->galleryPhotos);

        $this->reset(['galleryDescription', 'galleryPhotos']);
        $this->galleryPublished = true;
        $this->galleryTitle = $this->defaultGalleryTitle();

        $this->toast('Galeria „'.$gallery->title.'” została utworzona.');
    }

    public function editGallery(int $galleryId): void
    {
        $gallery = $this->findGallery($galleryId);

        $this->editingGalleryId = $gallery->id;
        $this->editTitle = $gallery->title;
        $this->editDescription = (string) $gallery->description;
        $this->captions = $gallery->photos->mapWithKeys(fn (GalleryPhoto $photo) => [$photo->id => (string) $photo->caption])->all();
        $this->morePhotos = [];
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingGalleryId', 'editTitle', 'editDescription', 'captions', 'morePhotos']);
    }

    public function saveGallery(): void
    {
        $gallery = $this->findGallery((int) $this->editingGalleryId);

        $this->validate([
            'editTitle' => 'required|string|max:255',
            'editDescription' => 'nullable|string|max:2000',
            'captions.*' => 'nullable|string|max:255',
            'morePhotos' => 'array|max:100',
            'morePhotos.*' => 'image|max:10240',
        ], [
            'editTitle.required' => 'Podaj tytuł galerii.',
            'morePhotos.*.image' => 'Każdy plik musi być zdjęciem.',
            'morePhotos.*.max' => 'Zdjęcie może mieć najwyżej 10 MB.',
        ]);

        $gallery->update(['title' => $this->editTitle, 'description' => $this->editDescription ?: null]);

        foreach ($gallery->photos as $photo) {
            if (array_key_exists($photo->id, $this->captions)) {
                $photo->update(['caption' => trim($this->captions[$photo->id]) ?: null]);
            }
        }

        $this->storePhotos($gallery, $this->morePhotos);
        $this->editGallery($gallery->id);

        $this->toast('Galeria została zapisana.');
    }

    public function togglePublished(int $galleryId): void
    {
        $gallery = $this->findGallery($galleryId);
        $gallery->update(['is_published' => ! $gallery->is_published]);

        $this->toast($gallery->is_published ? 'Galeria jest widoczna na stronie.' : 'Galeria została ukryta.');
    }

    public function movePhoto(int $photoId, int $direction): void
    {
        $photo = GalleryPhoto::findOrFail($photoId);
        $photos = $this->findGallery($photo->gallery_id)->photos->values();
        $from = $photos->search(fn (GalleryPhoto $item) => $item->id === $photo->id);
        $to = $from + ($direction < 0 ? -1 : 1);

        if ($to < 0 || $to >= $photos->count()) {
            return;
        }

        $ordered = $photos->all();
        [$ordered[$from], $ordered[$to]] = [$ordered[$to], $ordered[$from]];

        foreach (array_values($ordered) as $position => $item) {
            $item->update(['display_order' => $position]);
        }
    }

    public function deletePhoto(int $photoId): void
    {
        $photo = GalleryPhoto::findOrFail($photoId);
        $this->findGallery($photo->gallery_id);

        Storage::disk('public')->delete($photo->path);
        $photo->delete();
        unset($this->captions[$photoId]);

        $this->toast('Zdjęcie zostało usunięte.');
    }

    public function deleteGallery(int $galleryId): void
    {
        $gallery = $this->findGallery($galleryId);
        $gallery->delete();

        if ($this->editingGalleryId === $galleryId) {
            $this->cancelEdit();
        }

        $this->toast('Galeria została usunięta.');
    }

    public function render(): View
    {
        $match = $this->selectedMatch();

        return view('livewire.admin.match-media-manager', [
            'match' => $match,
            'galleries' => $match ? $match->galleries()->with('photos')->get() : collect(),
            'publicUrl' => $match ? $this->publicUrl($match) : null,
        ]);
    }

    /**
     * Platforma wpisanego linku (podpowiedź przy polu adresu).
     */
    public function platformOf(string $url): ?string
    {
        return filter_var($url, FILTER_VALIDATE_URL) ? StreamLink::platformName(StreamLink::platform($url), $url) : null;
    }

    private function loadStreams(): void
    {
        $match = $this->selectedMatch();

        $this->streams = collect($match?->streams ?? [])
            ->map(fn ($stream) => ['url' => (string) ($stream['url'] ?? ''), 'label' => (string) ($stream['label'] ?? '')])
            ->values()
            ->all();
    }

    private function requireMatch(): Model
    {
        $match = $this->selectedMatch();
        abort_if($match === null, 404);

        return $match;
    }

    /**
     * Galeria wybranego meczu (nie pozwala edytować galerii innego meczu).
     */
    private function findGallery(int $galleryId): Gallery
    {
        $match = $this->requireMatch();

        return Gallery::with('photos')
            ->where('match_type', $match->getMorphClass())
            ->where('match_id', $match->getKey())
            ->findOrFail($galleryId);
    }

    /**
     * @param  array<int, TemporaryUploadedFile>  $files
     */
    private function storePhotos(Gallery $gallery, array $files): void
    {
        $order = (int) $gallery->photos()->max('display_order');
        $hasPhotos = $gallery->photos()->exists();

        foreach (array_values($files) as $index => $file) {
            $gallery->photos()->create([
                'path' => $file->store('galleries/'.$gallery->id, 'public'),
                'display_order' => $order + $index + ($hasPhotos ? 1 : 0),
            ]);
        }
    }

    private function defaultGalleryTitle(): string
    {
        $match = $this->selectedMatch();

        return match (true) {
            $match instanceof Game => 'Zdjęcia: '.implode(' – ', $match->sidesNames()),
            $match instanceof TournamentMatch => 'Zdjęcia: '.$match->homeName().' – '.$match->awayName(),
            default => '',
        };
    }

    private function publicUrl(Model $match): ?string
    {
        if ($match instanceof Game) {
            return route('games.show', $match);
        }

        $tournament = $match instanceof TournamentMatch ? $match->tournament() : null;

        return $tournament?->has_page ? route('tournaments.matches.show', [$tournament, $match]) : null;
    }
}

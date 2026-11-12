<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\FiltersList;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\Gallery;
use App\Models\GalleryPhoto;
use App\Models\PhotoTag;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Support\MatchPicker;
use App\Support\Trash;
use App\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Galerie: z meczu albo z wydarzenia. Zdjęcia mają opis i oznaczenia drużyn, zawodników
 * i osób ze sztabu (linki do ich profili).
 */
class GalleryManager extends Component
{
    use FiltersList, ShowsToasts, WithFileUploads, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'druzyna', except: '')]
    public string $teamFilter = '';

    #[Url(as: 'rodzaj', except: '')]
    public string $typeFilter = '';

    #[Url(as: 'widocznosc', except: '')]
    public string $visibilityFilter = '';

    #[Url(as: 'sort', except: 'new')]
    public string $order = 'new';

    public const ORDERS = ['new' => 'Najnowsze', 'old' => 'Najstarsze', 'photos' => 'Najwięcej zdjęć', 'title' => 'Tytuł A–Z'];

    public ?int $editingId = null;

    public bool $creating = false;

    public string $title = '';

    public string $description = '';

    public ?string $event_date = null;

    public ?int $team_id = null;

    public string $match = '';

    public bool $is_published = true;

    public array $uploads = [];

    /** @var array<int, string> Opisy zdjęć, klucz = id zdjęcia */
    public array $captions = [];

    /** Zdjęcie, do którego dodajemy oznaczenie, i wyszukiwana fraza. */
    public ?int $taggingPhotoId = null;

    public string $tagSearch = '';

    protected function filterDefaults(): array
    {
        return ['search' => '', 'teamFilter' => '', 'typeFilter' => '', 'visibilityFilter' => '', 'order' => 'new'];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->creating = true;
        $this->event_date = now()->toDateString();
    }

    public function edit(int $id): void
    {
        $gallery = Gallery::with('photos', 'match')->findOrFail($id);

        $this->resetForm();
        $this->editingId = $gallery->id;
        $this->title = $gallery->title;
        $this->description = (string) $gallery->description;
        $this->event_date = $gallery->event_date?->toDateString();
        $this->team_id = $gallery->team_id;
        $this->match = MatchPicker::keyFor($gallery->match);
        $this->is_published = $gallery->is_published;
        $this->captions = $gallery->photos->mapWithKeys(fn (GalleryPhoto $photo) => [$photo->id => (string) $photo->caption])->all();
    }

    public function close(): void
    {
        $this->resetForm();
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function matchOptions(): array
    {
        $options = MatchPicker::recent(26, 4);
        $current = MatchPicker::find($this->match);

        if ($current && ! in_array($this->match, MatchPicker::keys($options), true)) {
            $options = [__('Obecny mecz') => [$this->match => $current->mediaLabel()]] + $options;
        }

        return $options;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'event_date' => 'nullable|date',
            'team_id' => 'nullable|exists:teams,id',
            'match' => ['nullable', Rule::in(MatchPicker::keys($this->matchOptions()))],
            'is_published' => 'boolean',
            'uploads' => 'array|max:100',
            'uploads.*' => 'image|max:10240',
        ], [
            'title.required' => 'Podaj tytuł galerii.',
            'uploads.*.image' => 'Każdy plik musi być zdjęciem.',
            'uploads.*.max' => 'Zdjęcie może mieć najwyżej 10 MB.',
        ]);

        $match = MatchPicker::find($validated['match']);
        $attributes = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?: null,
            'event_date' => $validated['event_date'],
            'team_id' => $validated['team_id'] ?: ($match?->team_id ?? null),
            'match_type' => $match?->getMorphClass(),
            'match_id' => $match?->getKey(),
            'is_published' => $validated['is_published'],
        ];

        $gallery = $this->editingId ? Gallery::findOrFail($this->editingId) : new Gallery;
        $gallery->fill($attributes)->save();

        $order = (int) $gallery->photos()->max('display_order');
        foreach (array_values($this->uploads) as $index => $file) {
            $gallery->photos()->create(['path' => $file->store('galleries/'.$gallery->id, 'public'), 'display_order' => $order + $index + 1]);
        }

        $this->toast($this->creating ? 'Galeria utworzona. Możesz dodać opisy i oznaczenia.' : 'Galeria zapisana.');
        $this->edit($gallery->id);
    }

    /**
     * Opis zdjęcia zapisuje się po wyjściu z pola.
     */
    public function updatedCaptions(?string $value, string $photoId): void
    {
        $this->photo((int) $photoId)->update(['caption' => trim((string) $value) ?: null]);
    }

    public function movePhoto(int $photoId, int $direction): void
    {
        $photos = Gallery::findOrFail($this->editingId)->photos()->get()->values();
        $index = $photos->search(fn (GalleryPhoto $photo) => $photo->id === $photoId);
        $swap = $photos->get($index + $direction);

        if ($index === false || ! $swap) {
            return;
        }

        $photos->splice($index, 1);
        $photos->splice($index + $direction, 0, [GalleryPhoto::find($photoId)]);
        $photos->values()->each(fn (GalleryPhoto $photo, int $order) => $photo->update(['display_order' => $order]));
    }

    public function deletePhoto(int $photoId): void
    {
        $photo = $this->photo($photoId);
        Storage::disk('public')->delete($photo->path);
        $photo->delete();
        unset($this->captions[$photoId]);

        $this->toast('Zdjęcie usunięte.');
    }

    public function deleteGallery(int $id): void
    {
        Trash::delete(Gallery::findOrFail($id));
        $this->resetForm();

        $this->toast('Galeria trafiła do kosza razem ze zdjęciami. Przywrócisz ją w: System → Kosz.');
    }

    public function startTagging(int $photoId): void
    {
        $this->taggingPhotoId = $this->photo($photoId)->id;
        $this->tagSearch = '';
    }

    /**
     * Dodaje oznaczenie od razu zatwierdzone: "player:5", "team:2" albo "user:7" (sztab).
     */
    public function addTag(string $key): void
    {
        [$type, $id] = explode(':', $key, 2);
        $model = match ($type) {
            'player' => Player::findOrFail($id),
            'team' => Team::findOrFail($id),
            'user' => User::findOrFail($id),
        };

        PhotoTag::updateOrCreate(
            ['gallery_photo_id' => $this->photo($this->taggingPhotoId)->id, 'taggable_type' => $model->getMorphClass(), 'taggable_id' => $model->getKey()],
            ['status' => PhotoTag::STATUS_APPROVED, 'reviewed_by' => Auth::id()],
        );

        $this->tagSearch = '';
    }

    public function removeTag(int $tagId): void
    {
        PhotoTag::whereIn('gallery_photo_id', Gallery::findOrFail($this->editingId)->photos()->select('id'))->findOrFail($tagId)->delete();
    }

    private function photo(?int $photoId): GalleryPhoto
    {
        return GalleryPhoto::where('gallery_id', $this->editingId)->findOrFail($photoId);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'creating', 'title', 'description', 'event_date', 'team_id', 'match', 'uploads', 'captions', 'taggingPhotoId', 'tagSearch']);
        $this->is_published = true;
        $this->resetErrorBag();
    }

    /**
     * @return array<string, string> klucz "typ:id" => opis
     */
    private function tagOptions(): array
    {
        $search = trim($this->tagSearch);

        if (mb_strlen($search) < 2) {
            return [];
        }

        $like = "%{$search}%";

        return collect()
            ->merge(Team::where('name', 'like', $like)->limit(5)->get()->mapWithKeys(fn (Team $team) => ['team:'.$team->id => '👥 '.$team->name]))
            ->merge(Player::with('team')->where(fn ($query) => $query->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like))->orderBy('last_name')->limit(10)->get()
                ->mapWithKeys(fn (Player $player) => ['player:'.$player->id => '🏐 '.$player->full_name.($player->team ? ' ('.$player->team->name.')' : '').($player->image_consent ? '' : ' · brak zgody na wizerunek')]))
            ->merge(User::withRole(UserRole::Trainer, UserRole::Staff)->where('name', 'like', $like)->limit(5)->get()->mapWithKeys(fn (User $user) => ['user:'.$user->id => '📋 '.$user->name]))
            ->all();
    }

    public function render(): View
    {
        $gallery = $this->editingId ? Gallery::with(['photos.tags.taggable', 'match'])->find($this->editingId) : null;

        return view('livewire.admin.gallery-manager', [
            'galleries' => $gallery || $this->creating ? collect() : $this->galleryList(),
            'gallery' => $gallery,
            'teams' => Team::orderBy('display_order')->get(['id', 'name']),
            'tagOptions' => $this->taggingPhotoId ? $this->tagOptions() : [],
        ]);
    }

    private function galleryList()
    {
        $term = trim($this->search);

        return Gallery::with(['match', 'team', 'photos' => fn ($photos) => $photos->limit(1)])->withCount('photos')
            ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('title', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%")))
            ->when($this->teamFilter === 'club', fn (Builder $q) => $q->whereNull('team_id'))
            ->when(ctype_digit($this->teamFilter), fn (Builder $q) => $q->where('team_id', $this->teamFilter))
            ->when($this->typeFilter === 'match', fn (Builder $q) => $q->whereNotNull('match_id'))
            ->when($this->typeFilter === 'event', fn (Builder $q) => $q->whereNull('match_id'))
            ->when($this->visibilityFilter === 'published', fn (Builder $q) => $q->where('is_published', true))
            ->when($this->visibilityFilter === 'hidden', fn (Builder $q) => $q->where('is_published', false))
            ->tap(fn (Builder $q) => $this->applyDateFilter($q, DB::raw('coalesce(event_date, created_at)')))
            ->tap(fn (Builder $q) => match ($this->order) {
                'old' => $q->orderBy('event_date')->orderBy('id'),
                'photos' => $q->orderByDesc('photos_count')->orderByDesc('id'),
                'title' => $q->orderBy('title'),
                default => $q->orderByDesc('event_date')->orderByDesc('id'),
            })
            ->paginate(15);
    }
}

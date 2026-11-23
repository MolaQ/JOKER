<?php

namespace App\Support;

use App\Models\Article;
use App\Models\ContentProposal;
use App\Models\Gallery;
use App\Models\Game;
use App\Models\MiniTrip;
use App\Models\RivalTeam;
use App\Models\TournamentMatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Publikacja zatwierdzonej propozycji treści: artykuł, zdjęcia w galerii meczu, transmisja albo logo rywala.
 * Z $keepFiles pliki zostają na miejscu (podgląd strony przed zatwierdzeniem, w transakcji wycofywanej po wyrenderowaniu).
 */
class ContentProposalPublisher
{
    public function __construct(private bool $keepFiles = false) {}

    /**
     * @return string opis wyniku do dziennika zmian
     */
    public function publish(ContentProposal $proposal): string
    {
        return match ($proposal->type) {
            ContentProposal::TYPE_ARTICLE => $this->publishArticle($proposal),
            ContentProposal::TYPE_PHOTOS => $this->addPhotos($proposal),
            ContentProposal::TYPE_STREAM => $this->addStream($proposal),
            ContentProposal::TYPE_LOGO => $this->setLogo($proposal),
        };
    }

    public function publishArticle(ContentProposal $proposal): string
    {
        $image = collect($proposal->files ?? [])->first();
        $article = $this->createArticle($proposal, $image ? $this->move($image, 'articles') : null, copy: false);

        // Film z relacji (np. turnieju minisiatkówki) trafia do transmisji turnieju i pokazuje się pod artykułem.
        if (filled($proposal->url) && $proposal->match && method_exists($proposal->match, 'normalizedStreams')) {
            $this->addStream($proposal);
        }

        return 'artykuł „'.$article->title.'”';
    }

    /**
     * @param  string|null  $image  zdjęcie na dysku public; przy $copy kopiowane do articles (oryginał zostaje w galerii)
     */
    public function createArticle(ContentProposal $proposal, ?string $image, bool $copy = true): Article
    {
        // Relacja z edytora (HTML, np. z minisiatkówki) zostaje jak jest; zwykły tekst dzielimy na akapity.
        $paragraphs = MessageHtml::isHtml((string) $proposal->content)
            ? MessageHtml::sanitize($proposal->content)
            : collect(preg_split('/\R{2,}/u', trim((string) $proposal->content)))
                ->map(fn (string $paragraph) => '<p>'.nl2br(e(trim($paragraph)), false).'</p>')
                ->implode('');

        if ($image && $copy && ! $this->keepFiles) {
            $target = 'articles/'.Str::random(8).'-'.basename($image);
            $image = Storage::disk('public')->copy($image, $target) ? $target : null;
        }

        $title = self::articleTitle($proposal);
        $article = Article::create([
            'title' => $title,
            'slug' => Slug::unique(Article::class, $title),
            'excerpt' => filled($proposal->excerpt) ? MessageHtml::sanitize($proposal->excerpt) : Str::limit(trim(html_entity_decode(strip_tags(str_replace('</p>', '</p> ', (string) $proposal->content)))), 200),
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

        if ($proposal->match instanceof MiniTrip) {
            $article->update(['mini_trip_id' => $proposal->match->id]);
        }

        if ($proposal->match instanceof TournamentMatch && ($tournament = $proposal->match->tournament())) {
            $article->update(['tournament_id' => $tournament->id]);
        }

        return $article;
    }

    /**
     * Tytuł z propozycji, a gdy go brak: pierwsze zdanie relacji.
     */
    public static function articleTitle(ContentProposal $proposal): string
    {
        $firstLine = Str::of(MessageHtml::plain((string) $proposal->content))->trim()->explode("\n")->first();

        return trim((string) $proposal->title) ?: Str::limit(trim((string) $firstLine), 120);
    }

    /**
     * @param  array<int, string>  $paths  ścieżki dodanych zdjęć (do zdjęcia artykułu)
     */
    public function addPhotos(ContentProposal $proposal, array &$paths = []): string
    {
        $match = $proposal->match ?? abort(422, 'Mecz tej propozycji już nie istnieje.');

        /** @var Gallery $gallery */
        $gallery = $match->galleries()->oldest('id')->first() ?? $match->galleries()->create([
            'title' => $match instanceof MiniTrip ? 'Zdjęcia: '.$match->title : 'Zdjęcia: '.self::matchName($match),
            'event_date' => $match instanceof MiniTrip ? $match->starts_on : null,
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

    public function addStream(ContentProposal $proposal): string
    {
        $match = $proposal->match ?? abort(422, 'Mecz tej propozycji już nie istnieje.');
        $streams = collect($match->streams ?? [])->push(['url' => $proposal->url, 'label' => null])->unique('url')->values()->all();
        $match->update(['streams' => $streams]);

        return 'transmisja '.$proposal->url;
    }

    /**
     * Logo rywala: zwykłe (na jasne tło) i wariant na ciemne tło do miniatur transmisji.
     */
    public function setLogo(ContentProposal $proposal): string
    {
        $rival = $proposal->match instanceof RivalTeam ? $proposal->match : abort(422, 'Drużyna rywali tej propozycji już nie istnieje.');
        $files = $proposal->files ?? [];
        $data = array_filter([
            'logo_path' => isset($files['logo']) ? $this->move($files['logo'], 'rival-teams') : null,
            'logo_light_path' => isset($files['logo_light']) ? $this->move($files['logo_light'], 'rival-teams') : null,
        ]);

        $rival->update($data);

        return 'logo drużyny '.$rival->full_name.(isset($data['logo_light_path']) ? (isset($data['logo_path']) ? ' (oba warianty)' : ' (na ciemne tło)') : '');
    }

    public static function matchName(?Model $match): string
    {
        return match (true) {
            $match instanceof Game => implode(' – ', $match->sidesNames()),
            $match instanceof TournamentMatch => $match->homeName().' – '.$match->awayName(),
            $match instanceof MiniTrip => $match->mediaLabel(),
            $match instanceof RivalTeam => $match->full_name,
            default => '',
        };
    }

    private function move(string $path, string $directory): string
    {
        if ($this->keepFiles) {
            return $path;
        }

        $target = $directory.'/'.basename($path);
        Storage::disk('public')->move($path, $target);

        return $target;
    }
}

<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Gallery;
use App\Models\GalleryPhoto;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * SEO: adres kanoniczny i dane strukturalne schema.org (JSON-LD) dla Google.
 */
class Seo
{
    /**
     * Adres strony bez parametrów śledzących i filtrów; zostaje tylko numer strony listy.
     */
    public static function canonical(Request $request): string
    {
        $page = (int) $request->query('page', 1);

        return $request->url().($page > 1 ? '?page='.$page : '');
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    public static function jsonLd(?array $data): string
    {
        if (! $data) {
            return '';
        }

        return '<script type="application/ld+json">'.json_encode(['@context' => 'https://schema.org'] + $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG).'</script>';
    }

    /**
     * @return array<string, mixed>
     */
    public static function organization(SiteSettings $site, string $logoUrl): array
    {
        return array_filter([
            '@type' => 'SportsOrganization',
            '@id' => url('/').'#klub',
            'name' => $site['club_name'],
            'url' => url('/'),
            'logo' => $logoUrl,
            'sport' => 'Volleyball',
            'email' => $site['contact_email'] ?: null,
            'telephone' => $site['contact_phone'] ?: null,
            'address' => filled($site['contact_address']) ? ['@type' => 'PostalAddress', 'streetAddress' => $site['contact_address'], 'addressCountry' => 'PL'] : null,
            'sameAs' => array_values($site->socials()) ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            'url' => url('/'),
            'name' => config('app.name'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('search').'?q={q}',
                'query-input' => 'required name=q',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function article(Article $article): array
    {
        return array_filter([
            '@type' => 'NewsArticle',
            'headline' => Str::limit($article->localizedTitle(), 110, ''),
            'description' => Str::limit(strip_tags((string) $article->localizedExcerpt()), 300),
            'image' => [Media::articleImage($article)],
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->updated_at?->toIso8601String(),
            'author' => $article->author ? ['@type' => 'Person', 'name' => $article->author->name] : ['@type' => 'Organization', 'name' => config('app.name')],
            'publisher' => ['@id' => url('/').'#klub'],
            'mainEntityOfPage' => route('articles.show', $article),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function gallery(Gallery $gallery): array
    {
        return array_filter([
            '@type' => 'ImageGallery',
            'name' => $gallery->title,
            'description' => $gallery->description,
            'url' => route('galleries.show', $gallery),
            'datePublished' => $gallery->displayDate()?->toDateString(),
            'publisher' => ['@id' => url('/').'#klub'],
            'image' => $gallery->photos->take(30)->map(fn (GalleryPhoto $photo) => array_filter([
                '@type' => 'ImageObject',
                'contentUrl' => $photo->url(),
                'caption' => $photo->altText() ?: null,
            ]))->values()->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function team(Team $team): array
    {
        return array_filter([
            '@type' => 'SportsTeam',
            'name' => config('app.name').' - '.$team->name,
            'sport' => 'Volleyball',
            'url' => route('teams.show', $team),
            'image' => Media::teamPhoto($team),
            'memberOf' => ['@id' => url('/').'#klub'],
            'coach' => $team->trainer ? ['@type' => 'Person', 'name' => $team->trainer->name, 'url' => route('trainers.show', $team->trainer)] : null,
        ]);
    }

    /**
     * Zawodnik: bez daty urodzenia (to w większości dzieci).
     *
     * @return array<string, mixed>
     */
    public static function player(Player $player): array
    {
        return array_filter([
            '@type' => 'Person',
            'name' => $player->full_name,
            'url' => route('players.show', $player),
            'image' => $player->photo_path ? Media::url($player->photo_path) : null,
            'jobTitle' => $player->positionsLabel(),
            'memberOf' => $player->team ? ['@type' => 'SportsTeam', 'name' => config('app.name').' - '.$player->team->name, 'url' => route('teams.show', $player->team)] : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function game(Game $game): array
    {
        [$home, $away] = $game->sidesNames();

        return array_filter([
            '@type' => 'SportsEvent',
            'name' => $home.' - '.$away,
            'sport' => 'Volleyball',
            'startDate' => $game->game_date?->toIso8601String(),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'location' => filled($game->venue) ? ['@type' => 'Place', 'name' => $game->venue, 'address' => $game->venue] : null,
            'homeTeam' => ['@type' => 'SportsTeam', 'name' => $home],
            'awayTeam' => ['@type' => 'SportsTeam', 'name' => $away],
            'organizer' => $game->league ? ['@type' => 'Organization', 'name' => $game->league] : null,
            'url' => route('games.show', $game),
        ]);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $items  [nazwa, adres]
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $index) => [
                '@type' => 'ListItem', 'position' => $index + 1, 'name' => $item[0], 'item' => $item[1],
            ])->all(),
        ];
    }
}

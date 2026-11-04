<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Award;
use App\Models\Gallery;
use App\Models\GalleryPhoto;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            'address' => self::postalAddress((string) $site['contact_address']),
            'sameAs' => array_values($site->socials()) ?: null,
            'foundingDate' => $site['seo_founding_year'] ?: null,
            'award' => Award::visible()->get()->map->fullName()->values()->all() ?: null,
            'description' => $site['site_description'] ?: null,
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
            'author' => $article->authorName() ? ['@type' => 'Person', 'name' => $article->authorName()] : ['@type' => 'Organization', 'name' => config('app.name')],
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
            'name' => $player->displayName(),
            'url' => route('players.show', $player),
            'image' => $player->photoUrl(),
            'jobTitle' => $player->positionsLabel(),
            'memberOf' => $player->team ? ['@type' => 'SportsTeam', 'name' => config('app.name').' - '.$player->team->name, 'url' => route('teams.show', $player->team)] : null,
        ]);
    }

    /**
     * Trener albo osoba ze sztabu (wpis ze StaffDirectory): funkcja, drużyny, zdjęcie, opis i media społecznościowe.
     *
     * @param  array{user: User, function: string, teams: Collection<int, array{name: string, url: string, role: string}>}  $entry
     * @return array<string, mixed>
     */
    public static function staffMember(array $entry): array
    {
        $user = $entry['user'];
        $about = trim(html_entity_decode(strip_tags(str_replace('</p>', '</p> ', (string) $user->additional_info)), ENT_QUOTES));

        return array_filter([
            '@type' => 'Person',
            'name' => $user->name,
            'url' => route('trainers.show', $user),
            'image' => $user->avatar_path ? Media::url($user->avatar_path) : null,
            'jobTitle' => $entry['function'],
            'description' => $about !== '' ? Str::limit(preg_replace('/\s+/', ' ', $about), 300) : null,
            'worksFor' => ['@id' => url('/').'#klub'],
            'memberOf' => $entry['teams']->map(fn (array $team) => ['@type' => 'SportsTeam', 'name' => config('app.name').' - '.$team['name'], 'url' => $team['url']])->values()->all() ?: null,
            'sameAs' => collect($user->socialProfiles())->pluck('url')->values()->all() ?: null,
        ]);
    }

    /**
     * Zakładka „Sztab”: lista osób dla Google.
     *
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return array<string, mixed>
     */
    public static function staffList(Collection $entries): array
    {
        return [
            '@type' => 'ItemList',
            'name' => __('Sztab').' - '.config('app.name'),
            'itemListElement' => $entries->values()->map(fn (array $entry, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => self::staffMember($entry),
            ])->all(),
        ];
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

    /**
     * Adres w formacie schema.org; „ul. Wincentego Pola 11, 64-920 Piła” rozbija na ulicę, kod i miasto.
     *
     * @return array<string, string>|null
     */
    public static function postalAddress(string $address): ?array
    {
        if (blank($address)) {
            return null;
        }

        if (preg_match('/^(.+?),\s*(\d{2}-\d{3})\s+(.+)$/u', trim($address), $parts)) {
            return ['@type' => 'PostalAddress', 'streetAddress' => $parts[1], 'postalCode' => $parts[2], 'addressLocality' => $parts[3], 'addressCountry' => 'PL'];
        }

        return ['@type' => 'PostalAddress', 'streetAddress' => $address, 'addressCountry' => 'PL'];
    }
}

<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Game;
use App\Models\MiniTrip;
use App\Models\MiniTripTeam;
use App\Models\TournamentMatch;

/**
 * Dane do grafik „do udostępnienia” (wynik meczu, mecz turniejowy, wyniki minisiatkówki, relacja).
 * Grafikę rysuje przeglądarka (resources/js/share-card.js), więc serwer nie potrzebuje bibliotek graficznych.
 * Każdy obszar włącza się osobno w ustawieniach („Grafiki do mediów społecznościowych”).
 */
class ShareCards
{
    /**
     * @return array<string, mixed>|null
     */
    public static function game(Game $game): ?array
    {
        if (! self::enabled('share_games') || ! $game->isFinished() || $game->home_score === null) {
            return null;
        }

        [$homeName, $awayName] = $game->sidesNames();
        $ourLogo = Media::url($game->team?->logoPath());
        $opponentLogo = Media::url($game->opponentTeam?->logoPath());
        $outcome = $game->outcome();

        return [
            'kind' => 'match',
            'eyebrow' => collect([$game->team?->name, $game->competitionLabel(), $game->round_label])->filter()->implode(' · '),
            'date' => $game->kickoffLabel('d.m.Y'),
            'home' => ['name' => $homeName, 'logo' => $game->is_home ? $ourLogo : $opponentLogo],
            'away' => ['name' => $awayName, 'logo' => $game->is_home ? $opponentLogo : $ourLogo],
            'score' => $game->home_score.':'.$game->away_score,
            'sets' => is_array($game->sets_score) ? collect($game->sets_score)->map(fn ($set) => implode(':', (array) $set))->implode('  ') : '',
            'badge' => match ($outcome) {
                'win' => __('Wygrana'),
                'loss' => __('Porażka'),
                default => '',
            },
            'url' => route('games.show', $game),
            'file' => 'wynik-'.$game->id,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function tournamentMatch(TournamentMatch $match): ?array
    {
        if (! self::enabled('share_tournaments') || $match->home_sets === null || $match->away_sets === null) {
            return null;
        }

        $tournament = $match->category?->tournament;

        return [
            'kind' => 'match',
            'eyebrow' => collect([$tournament?->fullName(), $match->category?->name])->filter()->implode(' · '),
            'phase' => $match->title(),
            'date' => $match->starts_at?->format('d.m.Y') ?? '',
            'home' => ['name' => $match->homeName(), 'logo' => Media::url($match->home?->logoPath())],
            'away' => ['name' => $match->awayName(), 'logo' => Media::url($match->away?->logoPath())],
            'score' => $match->home_sets.':'.$match->away_sets,
            'sets' => (string) $match->setsLabel(),
            'badge' => '',
            'url' => $tournament ? route('tournaments.matches.show', [$tournament, $match]) : url('/'),
            'file' => 'turniej-mecz-'.$match->id,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function miniTrip(MiniTrip $trip): ?array
    {
        $teams = $trip->teams->filter(fn (MiniTripTeam $team) => $team->place)->sortBy([fn ($a, $b) => $a->place <=> $b->place]);

        if (! self::enabled('share_mini') || ! self::enabled('module_mini') || $teams->isEmpty()) {
            return null;
        }

        $names = self::enabled('share_mini_names');

        return [
            'kind' => 'mini',
            'eyebrow' => self::setting('mini_title') ?: __('Minisiatkówka'),
            'title' => $trip->title ?: (string) $trip->city,
            'date' => collect([$trip->dateLabel(), $trip->city])->filter()->implode(' · '),
            'rows' => $teams->take(8)->map(fn (MiniTripTeam $team) => [
                'place' => (int) $team->place,
                'group' => collect([$team->group?->name, $team->teams_count ? $team->fieldLabel() : null])->filter()->implode(' | '),
                'name' => $team->name,
                'icons' => (int) ($team->group?->players_on_court ?? 0),
                'color' => $team->group?->iconColor() ?? '#f5b301',
                'players' => $names ? $team->players->map->displayName()->implode(', ') : '',
            ])->values()->all(),
            'url' => route('mini.index'),
            'file' => 'minisiatkowka-'.$trip->id,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function article(Article $article): ?array
    {
        if (! self::enabled('share_articles')) {
            return null;
        }

        return [
            'kind' => 'article',
            'eyebrow' => $article->miniTrip ? (self::setting('mini_title') ?: __('Minisiatkówka')) : __('Aktualności'),
            'title' => $article->localizedTitle(),
            'date' => $article->published_at?->format('d.m.Y') ?? '',
            'image' => Media::articleImage($article),
            'url' => route('articles.show', $article),
            'file' => 'relacja-'.$article->id,
        ];
    }

    /**
     * Wygląd wspólny dla wszystkich grafik (kolory, format, podpis, herb).
     *
     * @return array<string, string>
     */
    public static function theme(): array
    {
        return once(fn () => self::buildTheme());
    }

    /**
     * @return array<string, string>
     */
    private static function buildTheme(): array
    {
        $site = app(SiteSettings::class);

        return [
            'format' => (string) ($site['share_format'] ?: 'square'),
            'bg' => (string) ($site['share_bg'] ?: '#0f2d6b'),
            'bgTo' => (string) ($site['share_bg_to'] ?: '#12141a'),
            'accent' => (string) ($site['share_accent'] ?: '#ffd600'),
            'footer' => (string) ($site['share_footer'] ?: preg_replace('#^https?://(www\.)?#', '', rtrim(url('/'), '/'))),
            'logo' => (string) $site->image('logo', asset('img/logo-256.webp')),
            'club' => (string) ($site['club_name'] ?? config('app.name')),
        ];
    }

    private static function enabled(string $key): bool
    {
        return (bool) app(SiteSettings::class)[$key];
    }

    private static function setting(string $key): mixed
    {
        return app(SiteSettings::class)[$key];
    }
}

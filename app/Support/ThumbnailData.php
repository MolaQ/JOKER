<?php

namespace App\Support;

use App\Models\Game;
use App\Models\RivalTeam;
use App\Models\Sponsor;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentParticipant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Dane miniatury transmisji: dwie drużyny (nazwa w dwóch liniach, logo, kolor połowy grafiki),
 * termin, miejsce, etap turnieju, wynik setami i wyróżnienie. Wypełniane z meczu w bazie albo ręcznie.
 */
class ThumbnailData
{
    public const RIVAL_COLOR = '#e11d2a';

    /**
     * @return array<string, mixed>
     */
    public static function blank(): array
    {
        return [
            'home' => self::ours(),
            'away' => self::side('', null, self::RIVAL_COLOR),
            'date' => now()->toDateString(),
            'time' => '',
            'place' => '',
            'place2' => '',
            'stage' => '',
            'score' => '',
            'sets' => '',
            'mvp' => '',
            'tournament_logo' => null,
            'tournament_id' => null,
            'competition_id' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromGame(Game $game): array
    {
        $game->loadMissing(['team', 'opponentTeam.club', 'competition']);
        $ours = self::ours($game->ourLabel()['name'], $game->team);
        $rival = $game->opponentTeam;
        $opponent = self::side($game->opponentDisplayName(), $rival?->logoPath(), self::rivalColor($rival), $rival ? 'rival:'.$rival->id : '');
        [$home, $away] = $game->is_home ? [$ours, $opponent] : [$opponent, $ours];

        return [
            'home' => $home,
            'away' => $away,
            'date' => $game->game_date?->toDateString() ?? '',
            // Godzina 00:00 to mecz bez ustalonej godziny.
            'time' => $game->game_date && $game->game_date->format('H:i') !== '00:00' ? $game->game_date->format('H:i') : '',
            'place' => (string) $game->venue,
            'place2' => '',
            'stage' => (string) $game->round_label,
            'score' => $game->home_score !== null && $game->away_score !== null && $game->isFinished() ? $game->home_score.':'.$game->away_score : '',
            'sets' => self::setsText($game->sets_score),
            'mvp' => (string) $game->mvp,
            'tournament_logo' => null,
            'tournament_id' => $game->tournament_id,
            'competition_id' => $game->competition_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromTournamentMatch(TournamentMatch $match): array
    {
        $match->loadMissing(['category.tournament', 'group', 'home.competitor', 'away.competitor']);
        $tournament = $match->category->tournament;
        $side = function (?TournamentParticipant $participant, string $placeholder): array {
            if (! $participant) {
                return self::side($placeholder, null, self::RIVAL_COLOR);
            }
            if ($participant->isOwnTeam()) {
                return self::ours($participant->name(), $participant->competitor instanceof Team ? $participant->competitor : null);
            }
            $rival = $participant->competitor instanceof RivalTeam ? $participant->competitor : null;

            return self::side($participant->name(), $participant->logoPath(), self::rivalColor($rival), $rival ? 'rival:'.$rival->id : '');
        };

        $stage = collect([$match->category->name, $match->group ? (Str::startsWith(mb_strtolower($match->group->name), mb_strtolower(__('Grupa'))) ? $match->group->name : __('Grupa').' '.$match->group->name) : $match->label, $match->number ? __('mecz').' #'.$match->number : null])
            ->filter()->implode(' · ');

        return [
            'home' => $side($match->home, (string) $match->home_placeholder),
            'away' => $side($match->away, (string) $match->away_placeholder),
            'date' => $match->starts_at?->toDateString() ?? $tournament->start_date?->toDateString() ?? '',
            'time' => $match->starts_at?->format('H:i') ?? '',
            'place' => (string) $tournament->venue,
            'place2' => (string) $tournament->city,
            'stage' => $stage,
            'score' => $match->home_sets !== null && $match->away_sets !== null ? $match->home_sets.':'.$match->away_sets : '',
            'sets' => self::setsText($match->sets_score),
            'mvp' => (string) $match->mvp,
            'tournament_logo' => $tournament->logo_path ? self::src($tournament->logo_path) : null,
            'tournament_id' => $tournament->id,
            'competition_id' => null,
        ];
    }

    /**
     * Nasza drużyna: nazwa z rozgrywek, logo klubu (albo drużyny) i kolor klubu z ustawień.
     *
     * @return array<string, string>
     */
    public static function ours(?string $name = null, ?Team $team = null): array
    {
        $site = app(SiteSettings::class);
        $logo = $team?->logo_path ?: ($site->get('logo') ?: '/img/logo-512.png');

        return self::side($name ?? (string) $site['competition_club_name'], $logo, $site->color('thumbnail_club_color'), 'ours');
    }

    /**
     * @return array<string, string>
     */
    public static function side(string $name, ?string $logo, string $color, string $key = ''): array
    {
        [$line1, $line2] = self::splitName($name);

        return ['name' => $line1, 'sub' => $line2, 'logo' => (string) self::src($logo), 'color' => $color, 'color2' => '', 'key' => $key];
    }

    /**
     * Nazwa w dwóch liniach: ostatnie słowo (zwykle miasto) w drugiej, np. „LOMS Joker” / „Piła”.
     *
     * @return array{0: string, 1: string}
     */
    public static function splitName(string $name): array
    {
        // „LOMS Joker Piła (seniorzy)” → „LOMS Joker Piła” / „seniorzy”.
        if (preg_match('/^(.+?)\s*\(([^)]+)\)\s*$/u', trim($name), $found)) {
            return [$found[1], $found[2]];
        }

        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);

        return count($words) < 2 ? [trim($name), ''] : [implode(' ', array_slice($words, 0, -1)), (string) end($words)];
    }

    public static function rivalColor(?RivalTeam $rival): string
    {
        return $rival?->brand_color ?: ($rival?->club?->brand_color ?: self::RIVAL_COLOR);
    }

    /**
     * Adres obrazka z tej samej domeny (eksport do PNG nie pobierze obrazka z innej domeny).
     */
    public static function src(?string $path): ?string
    {
        $url = Media::url($path);

        if ($url && Str::startsWith($url, ['http://', 'https://'])) {
            $host = parse_url($url, PHP_URL_HOST);
            if (in_array($host, [parse_url((string) config('app.url'), PHP_URL_HOST), request()->getHost()], true)) {
                return parse_url($url, PHP_URL_PATH) ?: $url;
            }
        }

        return $url;
    }

    public static function setsText(?array $sets): string
    {
        return collect($sets ?? [])->map(fn ($set) => implode(':', array_map('intval', (array) $set)))->implode(', ');
    }

    /**
     * Sety z tekstu „25:20, 23:25 15:12” (dowolne separatory).
     *
     * @return list<array{0: int, 1: int}>
     */
    public static function parseSets(string $text): array
    {
        preg_match_all('/(\d{1,2})\s*[:\-]\s*(\d{1,2})/', $text, $found, PREG_SET_ORDER);

        return array_map(fn (array $set) => [(int) $set[1], (int) $set[2]], $found);
    }

    /**
     * Wynik meczu: wpisany („3:1”) albo policzony z setów.
     *
     * @return array{0: int, 1: int}|null
     */
    public static function score(string $score, array $sets): ?array
    {
        if (preg_match('/^\s*(\d)\s*[:\-]\s*(\d)\s*$/', $score, $found)) {
            return [(int) $found[1], (int) $found[2]];
        }

        if ($sets === []) {
            return null;
        }

        return [
            count(array_filter($sets, fn (array $set) => $set[0] > $set[1])),
            count(array_filter($sets, fn (array $set) => $set[1] > $set[0])),
        ];
    }

    /**
     * Dzień tygodnia i data po polsku, np. ["Sobota", "14 listopada 2026"].
     *
     * @return array{0: string, 1: string}
     */
    public static function dateLabels(string $date): array
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return ['', ''];
        }
        $day = Carbon::parse($date)->locale(app()->getLocale());

        return [Str::ucfirst($day->translatedFormat('l')), $day->translatedFormat('j F Y')];
    }

    /**
     * Logotypy sponsorów do paska: najpierw partnerzy turnieju, potem sponsorzy klubu (najwyżej 8).
     *
     * @return list<string>
     */
    public static function sponsorLogos(?int $tournamentId = null): array
    {
        $partners = $tournamentId ? (Tournament::find($tournamentId)?->sponsors()->where('is_active', true)->whereNotNull('logo_path')->get() ?? collect()) : collect();
        $club = Sponsor::query()->where('is_active', true)->whereNotNull('logo_path')
            ->leftJoin('sponsor_groups', 'sponsor_groups.id', '=', 'sponsors.sponsor_group_id')
            ->orderByRaw('sponsor_groups.display_order is null, sponsor_groups.display_order')->orderBy('sponsors.display_order')
            ->get(['sponsors.*']);

        return $partners->concat($club)->unique('id')->take(8)->map(fn (Sponsor $sponsor) => self::src($sponsor->logo_path))->values()->all();
    }
}

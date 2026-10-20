<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Document;
use App\Models\Game;
use App\Models\Page;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Indeks wyszukiwarki: cała treść strony w jednej, lekkiej liście.
 * Przeglądarka pobiera go raz i szuka lokalnie (wyniki natychmiast, bez czekania na serwer).
 * Indeks odświeża się sam po każdej zmianie treści w panelu (patrz AppServiceProvider).
 */
class SearchIndex
{
    public const CACHE_KEY = 'search-index-v2';

    /**
     * @return array<int, array{type: string, title: string, subtitle: string, url: string, text: string}>
     */
    public static function entries(): array
    {
        // Odświeżany po zmianach treści, a dodatkowo co 10 minut (np. zmiany w sztabie drużyny).
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), fn () => self::build());
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Wyszukiwanie po stronie serwera (strona /szukaj, działa także bez JavaScriptu).
     *
     * @return array<int, array{type: string, title: string, subtitle: string, url: string, text: string}>
     */
    public static function search(string $query, int $limit = 60): array
    {
        $terms = array_filter(explode(' ', self::normalize($query)));

        if ($terms === []) {
            return [];
        }

        return collect(self::entries())
            ->map(function (array $entry) use ($terms) {
                $title = self::normalize($entry['title']);
                $haystack = $title.' '.self::normalize($entry['subtitle']).' '.self::normalize($entry['type']).' '.$entry['text'];
                $score = 0;

                foreach ($terms as $term) {
                    if (! str_contains($haystack, $term)) {
                        return null;
                    }

                    $score += match (true) {
                        str_starts_with($title, $term) => 30,
                        str_contains(' '.$title, ' '.$term) => 20,
                        str_contains($title, $term) => 10,
                        default => 1,
                    };
                }

                return $entry + ['score' => $score];
            })
            ->filter()
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Małe litery i bez polskich znaków, żeby "wisniewski" znalazło "Wiśniewski".
     */
    public static function normalize(?string $text): string
    {
        $text = Str::lower(strip_tags((string) $text));
        $text = strtr($text, ['ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z']);
        $text = Str::ascii($text);

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $text));
    }

    /**
     * @return array<int, array{type: string, title: string, subtitle: string, url: string, text: string}>
     */
    private static function build(): array
    {
        $entries = [];
        $add = function (string $type, string $title, ?string $subtitle, string $url, ?string $text = '') use (&$entries) {
            $entries[] = [
                'type' => $type,
                'title' => $title,
                'subtitle' => (string) $subtitle,
                'url' => $url,
                // Treść skracamy, by indeks był lekki (wystarczy do znalezienia słów kluczowych).
                'text' => Str::limit(self::normalize($text), 600, ''),
            ];
        };

        foreach (self::pages() as [$title, $subtitle, $url, $keywords]) {
            $add('Strona', $title, $subtitle, $url, $keywords);
        }

        foreach (Page::published()->orderBy('display_order')->get() as $page) {
            $add('Strona', $page->title, Str::limit((string) $page->lead, 80), route('pages.show', $page, false), $page->lead.' '.$page->content);
        }

        foreach (Team::with(['trainer'])->orderBy('display_order')->get() as $team) {
            $add('Drużyna', $team->name, $team->birth_year ? 'Rocznik '.$team->birth_year : 'Pierwsza drużyna', route('teams.show', $team, false),
                $team->description.' '.$team->trainer?->name.' '.$team->category);
        }

        foreach (Player::with('team')->where('is_active', true)->orderBy('last_name')->get() as $player) {
            $add('Zawodnik', $player->full_name, collect([$player->positionsLabel(), $player->team?->name, $player->jersey_number ? '#'.$player->jersey_number : null])->filter()->implode(' · '),
                route('players.show', $player, false), $player->bio);
        }

        $staff = User::with(['headCoachTeams', 'assistantTeams'])
            ->where(fn ($query) => $query->whereHas('headCoachTeams')->orWhereHas('assistantTeams'))
            ->orderBy('name')
            ->get();

        foreach ($staff as $member) {
            $roles = $member->headCoachTeams->toBase()->map(fn ($team) => 'Trener główny: '.$team->name)
                ->merge($member->assistantTeams->map(fn ($team) => ($team->pivot->role ?: 'Asystent trenera').': '.$team->name));
            $add('Sztab', $member->name, $roles->first(), route('trainers.show', $member, false), $roles->implode(' ').' '.$member->additional_info);
        }

        foreach (Article::published()->get() as $article) {
            $add('Aktualność', $article->title, $article->published_at?->translatedFormat('d F Y'), route('articles.show', $article, false),
                $article->excerpt.' '.$article->content);
        }

        // Najpierw nadchodzące mecze (od najbliższego), potem rozegrane (od najnowszego).
        $games = Game::with(['team', 'opponentTeam', 'competition'])
            ->where('game_date', '>=', now()->subMonths(6))
            ->get()
            ->sortBy(fn (Game $game) => $game->game_date >= now()
                ? $game->game_date->timestamp
                : PHP_INT_MAX - $game->game_date->timestamp);

        foreach ($games as $game) {
            $result = $game->isFinished() ? " ({$game->home_score}:{$game->away_score})" : '';
            $add('Mecz', ($game->team?->name ?? 'Joker').' vs '.$game->opponentShortName().$result,
                $game->game_date?->translatedFormat('d F Y, H:i').' · '.($game->is_home ? 'u siebie' : 'wyjazd'),
                route('schedule.index', [], false), $game->opponentDisplayName().' '.$game->competition?->name.' '.$game->venue);
        }

        foreach (Document::public()->latest()->get() as $document) {
            $add('Dokument', $document->title, $document->categoryLabel().' · '.Str::upper((string) $document->file_type), route('documents.index', [], false).'#dokument-'.$document->id,
                $document->description.' '.$document->category);
        }

        return $entries;
    }

    /**
     * Stałe podstrony z typowymi słowami, którymi ludzie ich szukają.
     *
     * @return array<int, array{0: string, 1: string, 2: string, 3: string}>
     */
    private static function pages(): array
    {
        return [
            ['Terminarz meczów', 'Wszystkie mecze sezonu', route('schedule.index', [], false), 'terminarz kalendarz mecze kiedy gramy godziny'],
            ['Tabele ligowe', 'Pozycje w rozgrywkach', route('standings', [], false), 'tabela liga punkty miejsce ranking'],
            ['Drużyny', 'Wszystkie drużyny klubu', route('teams.index', [], false), 'druzyny zespoly roczniki kadra seniorzy juniorzy mlodzicy kadeci'],
            ['Kontakt i zapisy', 'Napisz do klubu, zapisz dziecko', route('contact.index', [], false), 'kontakt telefon email adres zapisy nabor trening probny dolacz hala'],
            ['Dokumenty do pobrania', 'Regulaminy, zgody, formularze', route('documents.index', [], false), 'dokumenty regulamin zgoda rodo formularz deklaracja statut pobierz'],
            ['Aktualności', 'Wszystkie wpisy', route('articles.index', [], false), 'aktualnosci news wiadomosci blog'],
        ];
    }
}

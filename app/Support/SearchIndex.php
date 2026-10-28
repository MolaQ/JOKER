<?php

namespace App\Support;

use App\Http\Middleware\SetLocale;
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
 * Każdy język strony ma własny indeks (napisy i angielskie wersje wpisów po angielsku).
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
        return Cache::remember(self::CACHE_KEY.'-'.app()->getLocale(), now()->addMinutes(10), fn () => self::build());
    }

    public static function forget(): void
    {
        foreach (SetLocale::LOCALES as $locale) {
            Cache::forget(self::CACHE_KEY.'-'.$locale);
        }
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
            $add(__('Strona'), $title, $subtitle, $url, $keywords);
        }

        foreach (Page::published()->orderBy('display_order')->get() as $page) {
            $add(__('Strona'), $page->localizedTitle(), Str::limit((string) $page->localizedLead(), 80), route('pages.show', $page, false),
                $page->title.' '.$page->lead.' '.$page->content.' '.$page->title_en.' '.$page->lead_en.' '.$page->content_en);
        }

        foreach (Team::with(['trainer'])->orderBy('display_order')->get() as $team) {
            $add(__('Drużyna'), $team->name, $team->birthYearsLabel() ?? __('Pierwsza drużyna'), route('teams.show', $team, false),
                $team->description.' '.$team->trainer?->name.' '.$team->category);
        }

        foreach (Player::with('team')->where('is_active', true)->orderBy('last_name')->get() as $player) {
            $add(__('Zawodnik'), $player->full_name, collect([$player->positionsLabel(), $player->team?->name, $player->jersey_number ? '#'.$player->jersey_number : null])->filter()->implode(' · '),
                route('players.show', $player, false), $player->bio);
        }

        $staff = User::with(['headCoachTeams', 'assistantTeams'])
            ->where(fn ($query) => $query->whereHas('headCoachTeams')->orWhereHas('assistantTeams'))
            ->orderBy('name')
            ->get();

        foreach ($staff as $member) {
            $roles = $member->headCoachTeams->toBase()->map(fn ($team) => __('Trener główny').': '.$team->name)
                ->merge($member->assistantTeams->map(fn ($team) => __($team->pivot->role ?: 'Asystent trenera').': '.$team->name));
            $add(__('Sztab'), $member->name, $roles->first(), route('trainers.show', $member, false), $roles->implode(' ').' '.$member->additional_info);
        }

        foreach (Article::published()->get() as $article) {
            $add(__('Aktualność'), $article->localizedTitle(), $article->published_at?->translatedFormat('d F Y'), route('articles.show', $article, false),
                $article->title.' '.$article->excerpt.' '.$article->content.' '.$article->title_en.' '.$article->excerpt_en.' '.$article->content_en);
        }

        // Najpierw nadchodzące mecze (od najbliższego), potem rozegrane (od najnowszego).
        $games = Game::with(['team', 'opponentTeam', 'competition'])
            ->where('game_date', '>=', now()->subMonths(6))
            ->where('status', '!=', 'cancelled')
            ->get()
            ->sortBy(fn (Game $game) => $game->game_date >= now()
                ? $game->game_date->timestamp
                : PHP_INT_MAX - $game->game_date->timestamp);

        foreach ($games as $game) {
            $result = $game->isFinished() ? " ({$game->home_score}:{$game->away_score})" : '';
            $add(__('Mecz'), ($game->team?->name ?? 'Joker').' vs '.$game->opponentShortName().$result,
                $game->game_date?->translatedFormat('d F Y, H:i').' · '.($game->is_home ? __('u siebie') : __('wyjazd')),
                route('schedule.index', [], false), $game->opponentDisplayName().' '.$game->competition?->name.' '.$game->venue);
        }

        foreach (Document::public()->latest()->get() as $document) {
            $add(__('Dokument'), $document->title, $document->categoryLabel().' · '.Str::upper((string) $document->file_type), route('documents.index', [], false).'#dokument-'.$document->id,
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
            [__('Terminarz meczów'), __('Wszystkie mecze sezonu'), route('schedule.index', [], false), 'terminarz kalendarz mecze kiedy gramy godziny schedule fixtures matches calendar'],
            [__('Tabele ligowe'), __('Pozycje w rozgrywkach'), route('standings', [], false), 'tabela liga punkty miejsce ranking standings table league points'],
            [__('Drużyny'), __('Wszystkie drużyny klubu'), route('teams.index', [], false), 'druzyny zespoly roczniki kadra seniorzy juniorzy mlodzicy kadeci teams squad seniors juniors'],
            [__('Kontakt i zapisy'), __('Napisz do klubu, zapisz dziecko'), route('contact.index', [], false), 'kontakt telefon email adres zapisy nabor trening probny dolacz hala contact phone address join training'],
            [__('Dokumenty do pobrania'), __('Regulaminy, zgody, formularze'), route('documents.index', [], false), 'dokumenty regulamin zgoda rodo formularz deklaracja statut pobierz documents regulations forms download'],
            [__('Aktualności'), __('Wszystkie wpisy'), route('articles.index', [], false), 'aktualnosci news wiadomosci blog'],
        ];
    }
}

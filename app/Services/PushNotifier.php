<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Gallery;
use App\Models\Game;
use App\Models\PushSubscription;
use App\Models\TournamentMatch;
use App\Models\TrainingSession;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * Powiadomienia push (wyskakujące na telefonie i komputerze) o nowych aktualnościach, wynikach, galeriach i turniejach.
 * Treść pochodzi z ustawień „Powiadomienia: treść”, a odbiorców wybiera PushSubscription::wants().
 * Klucze VAPID tworzą się same przy pierwszym użyciu i są zapisane w ustawieniach strony.
 */
class PushNotifier
{
    /** Starsze treści (np. przy pierwszym imporcie terminarza) nie wysyłają powiadomień. */
    private const FRESH_HOURS = 48;

    public function __construct(private SiteSettings $site) {}

    public function enabled(): bool
    {
        return (bool) $this->site['push_enabled'];
    }

    /**
     * Tematy włączone przez administratora (do wyboru w okienku dzwonka).
     *
     * @return array<string, string>
     */
    public function topics(): array
    {
        $settings = ['articles' => 'push_articles', 'results' => 'push_results', 'galleries' => 'push_galleries', 'tournaments' => 'push_tournaments', 'upcoming' => 'push_upcoming'];

        return collect(PushSubscription::TOPICS)->filter(fn (string $label, string $topic) => (bool) $this->site[$settings[$topic]])->all();
    }

    public function publicKey(): string
    {
        return $this->keys()['publicKey'];
    }

    /**
     * @return array{publicKey: string, privateKey: string}
     */
    private function keys(): array
    {
        $keys = $this->site->get('push_vapid');

        if (! is_array($keys) || empty($keys['publicKey']) || empty($keys['privateKey'])) {
            $keys = VAPID::createVapidKeys();
            $this->site->set(['push_vapid' => $keys]);
        }

        return $keys;
    }

    public function articlePublished(Article $article): void
    {
        if (! $this->site['push_articles'] || ! $article->published_at || $article->published_at->isFuture() || $article->published_at->lt(now()->subHours(self::FRESH_HOURS))) {
            return;
        }

        $vars = ['tytul' => $article->title, 'zajawka' => strip_tags((string) ($article->excerpt ?: $article->content))];
        $this->send(Str::limit($this->text('push_tpl_article_title', $vars), 80), Str::limit($this->text('push_tpl_article_body', $vars), 140), route('articles.show', $article), 'article-'.$article->id, audience: ['topic' => 'articles']);
    }

    public function gameFinished(Game $game): void
    {
        $result = $game->ourSetsResult();

        if (! $this->site['push_results'] || ! $result || ! $game->game_date || $game->game_date->lt(now()->subHours(self::FRESH_HOURS))) {
            return;
        }

        [$home, $away] = $game->sidesNames();
        $vars = [
            'druzyna' => (string) $game->team?->name,
            'gospodarz' => $home,
            'gosc' => $away,
            'wynik' => $game->is_home ? "{$result[0]}:{$result[1]}" : "{$result[1]}:{$result[0]}",
            'rywal' => (string) $game->opponentDisplayName(),
        ];
        $title = match ($game->outcome()) {
            'derby' => $this->site['derby_winner'].'! '.$vars['druzyna'],
            'win' => $this->text('push_tpl_win_title', $vars),
            default => $this->text('push_tpl_loss_title', $vars),
        };

        $this->send(trim($title), $this->text('push_tpl_game_body', $vars), route('games.show', $game), 'game-'.$game->id, audience: ['topic' => 'results', 'teams' => array_filter([$game->team_id])]);
    }

    public function galleryPublished(Gallery $gallery): void
    {
        if (! $this->site['push_galleries'] || $gallery->created_at?->lt(now()->subHours(self::FRESH_HOURS))) {
            return;
        }

        $vars = ['tytul' => $gallery->title];
        $this->send($this->text('push_tpl_gallery_title', $vars), Str::limit($this->text('push_tpl_gallery_body', $vars), 120), route('galleries.show', $gallery), 'gallery-'.$gallery->id, audience: ['topic' => 'galleries']);
    }

    /**
     * Wynik meczu w turnieju: dla kibiców naszych drużyn i tych, którzy obserwują jedną z drużyn meczu.
     */
    public function tournamentMatchFinished(TournamentMatch $match): void
    {
        if (! $this->site['push_tournaments'] || ! $match->isFinished() || $match->starts_at?->lt(now()->subHours(self::FRESH_HOURS))) {
            return;
        }

        $category = $match->category;
        $vars = $this->tournamentVars($match) + [
            'wynik' => "{$match->home_sets}:{$match->away_sets}",
            'zwyciezca' => (string) $match->winner()?->name(),
        ];

        $this->send($this->text('push_tpl_tournament_title', $vars), $this->text('push_tpl_tournament_body', $vars), route('tournaments.show', ['tournament' => $category->tournament, 'category' => $category->slug()]), 'tournament-match-'.$match->id, audience: $this->tournamentAudience($match, 'tournaments'));
    }

    /**
     * Przypomnienie, że mecz zaraz się zacznie (ligowy albo turniejowy), zob. komenda joker:push-upcoming.
     */
    public function upcoming(Game|TournamentMatch $match, int $minutes): int
    {
        if (! $this->site['push_upcoming']) {
            return 0;
        }

        if ($match instanceof Game) {
            [$home, $away] = $match->sidesNames();
            $vars = ['nazwa' => (string) $match->team?->name, 'gospodarz' => $home, 'gosc' => $away, 'miejsce' => (string) $match->venue, 'godzina' => $match->game_date->format('H:i')];
            $url = route('games.show', $match);
            $audience = ['topic' => 'upcoming', 'teams' => array_filter([$match->team_id])];
        } else {
            $category = $match->category;
            $vars = $this->tournamentVars($match) + ['nazwa' => $category->tournament->name.' · '.$category->name, 'godzina' => $match->starts_at->format('H:i')];
            $url = route('tournaments.show', ['tournament' => $category->tournament, 'category' => $category->slug()]);
            $audience = $this->tournamentAudience($match, 'upcoming');
        }

        return $this->send($this->text('push_tpl_upcoming_title', $vars + ['minuty' => $minutes]), $this->text('push_tpl_upcoming_body', $vars + ['minuty' => $minutes]), $url, 'upcoming-'.class_basename($match).'-'.$match->id, audience: $audience);
    }

    /**
     * @return array<string, string>
     */
    private function tournamentVars(TournamentMatch $match): array
    {
        return [
            'turniej' => $match->category->tournament->name,
            'kategoria' => $match->category->name,
            'mecz' => $match->title(),
            'gospodarz' => $match->homeName(),
            'gosc' => $match->awayName(),
            'miejsce' => $match->court ? TrainingSession::courtLabel($match->court) : '',
        ];
    }

    /**
     * @return array{topic: string, teams: list<int>, participants: list<int>}
     */
    private function tournamentAudience(TournamentMatch $match, string $topic): array
    {
        $sides = collect([$match->home, $match->away])->filter();

        return [
            'topic' => $topic,
            'teams' => $sides->where('competitor_type', 'team')->pluck('competitor_id')->map(fn ($id) => (int) $id)->values()->all(),
            'participants' => $sides->pluck('id')->values()->all(),
        ];
    }

    /**
     * Treść z ustawień „Powiadomienia: treść” z wstawionymi polami, np. {druzyna}.
     *
     * @param  array<string, string|int>  $vars
     */
    public function text(string $key, array $vars): string
    {
        $text = strtr((string) $this->site[$key], collect($vars)->mapWithKeys(fn ($value, $name) => ['{'.$name.'}' => (string) $value])->all());

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Liczba urządzeń, które dostaną powiadomienie dla danych odbiorców (np. przed wysłaniem ręcznym).
     *
     * @param  array{topic?: ?string, teams?: list<int>|null, participants?: list<int>}  $audience
     */
    public function countAudience(array $audience): int
    {
        return PushSubscription::query()->get()->filter(fn (PushSubscription $subscription) => $subscription->wants($audience))->count();
    }

    /**
     * Nowa wiadomość: powiadomienie tylko na urządzenia odbiorców, którzy włączyli powiadomienia będąc zalogowanymi.
     *
     * @param  iterable<int>  $userIds
     */
    public function newMessage(iterable $userIds, string $title, string $body, string $url, string $tag): int
    {
        $userIds = collect($userIds)->filter()->unique()->values()->all();

        return $userIds === [] ? 0 : $this->send($title, $body, $url, $tag, $userIds);
    }

    /**
     * Wysyła powiadomienie do zapisanych przeglądarek (wszystkich albo tylko wskazanych kont). Zwraca liczbę wysłanych.
     *
     * @param  list<int>|null  $userIds
     * @param  array{topic?: ?string, teams?: list<int>|null, participants?: list<int>}  $audience  zob. PushSubscription::wants()
     */
    public function send(string $title, string $body, string $url, ?string $tag = null, ?array $userIds = null, array $audience = []): int
    {
        $subscriptions = PushSubscription::query()->when($userIds !== null, fn ($query) => $query->whereIn('user_id', $userIds));

        if (! $this->enabled() || $subscriptions->doesntExist()) {
            return 0;
        }

        try {
            $webPush = new WebPush(['VAPID' => [
                'subject' => 'mailto:'.($this->site['contact_email'] ?: 'kontakt@example.com'),
                'publicKey' => $this->keys()['publicKey'],
                'privateKey' => $this->keys()['privateKey'],
            ]], ['TTL' => 86400, 'urgency' => 'normal', 'topic' => $tag ? Str::limit(Str::slug($tag), 32, '') : null]);
            $webPush->setReuseVAPIDHeaders(true);

            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'url' => $url,
                'tag' => $tag,
                'icon' => $this->site->image('logo', asset('img/logo-180.png')),
                'badge' => asset('img/logo-96.png'),
            ], JSON_UNESCAPED_UNICODE);

            $subscriptions->chunkById(200, function ($subscriptions) use ($webPush, $payload, $audience) {
                foreach ($subscriptions as $subscription) {
                    if (! $subscription->wants($audience)) {
                        continue;
                    }

                    $webPush->queueNotification(Subscription::create([
                        'endpoint' => $subscription->endpoint,
                        'publicKey' => $subscription->public_key,
                        'authToken' => $subscription->auth_token,
                        'contentEncoding' => $subscription->content_encoding,
                    ]), $payload);
                }
            });

            $sent = 0;

            foreach ($webPush->flush() as $report) {
                $hash = PushSubscription::hashEndpoint($report->getEndpoint());

                if ($report->isSuccess()) {
                    $sent++;
                    PushSubscription::where('endpoint_hash', $hash)->update(['last_sent_at' => now()]);
                } elseif ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint_hash', $hash)->delete();
                }
            }

            return $sent;
        } catch (Throwable $exception) {
            Log::warning('Powiadomienia push: '.$exception->getMessage());

            return 0;
        }
    }
}

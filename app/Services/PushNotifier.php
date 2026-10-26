<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Gallery;
use App\Models\Game;
use App\Models\PushSubscription;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * Powiadomienia push (wyskakujące na telefonie i komputerze) o nowych aktualnościach, wynikach i galeriach.
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

        $this->send(Str::limit($article->title, 80), Str::limit(strip_tags((string) ($article->excerpt ?: $article->content)), 140), route('articles.show', $article), 'article-'.$article->id);
    }

    public function gameFinished(Game $game): void
    {
        $result = $game->ourSetsResult();

        if (! $this->site['push_results'] || ! $result || ! $game->game_date || $game->game_date->lt(now()->subHours(self::FRESH_HOURS))) {
            return;
        }

        [$home, $away] = $game->sidesNames();
        $score = $game->is_home ? "{$result[0]}:{$result[1]}" : "{$result[1]}:{$result[0]}";
        $title = match ($game->outcome()) {
            'derby' => __('Wygrała pilska siatkówka!'),
            'win' => __('Wygrana!'),
            default => __('Wynik meczu'),
        }.' '.$game->team?->name;

        $this->send(trim($title), "{$home} {$score} {$away}", route('games.show', $game), 'game-'.$game->id);
    }

    public function galleryPublished(Gallery $gallery): void
    {
        if (! $this->site['push_galleries'] || $gallery->created_at?->lt(now()->subHours(self::FRESH_HOURS))) {
            return;
        }

        $this->send(__('Nowa galeria zdjęć'), Str::limit($gallery->title, 120), route('galleries.show', $gallery), 'gallery-'.$gallery->id);
    }

    /**
     * Wysyła powiadomienie do wszystkich zapisanych przeglądarek. Zwraca liczbę wysłanych.
     */
    public function send(string $title, string $body, string $url, ?string $tag = null): int
    {
        if (! $this->enabled() || PushSubscription::doesntExist()) {
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

            PushSubscription::query()->chunkById(200, function ($subscriptions) use ($webPush, $payload) {
                foreach ($subscriptions as $subscription) {
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

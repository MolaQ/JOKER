<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zapisuje wyświetlenia stron publicznych (bez ciasteczek) i dokleja do strony podpis,
 * którym skrypt odsyła później czas na stronie, przewinięcie i otwierane zakładki.
 */
class TrackPageView
{
    private const BOTS = '/bot|crawl|spider|slurp|preview|facebookexternalhit|headless|lighthouse|monitor|curl|wget|python|http-client/i';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldTrack($request, $response)) {
            return $response;
        }

        $content = (string) $response->getContent();
        preg_match('/<title>(.*?)<\/title>/s', $content, $title);

        $view = PageView::create([
            'kind' => PageView::KIND_PAGE,
            'path' => Str::limit('/'.ltrim($request->path(), '/'), 250, ''),
            'route_name' => $request->route()?->getName(),
            'title' => isset($title[1]) ? Str::limit(html_entity_decode(trim($title[1])), 250, '') : null,
            'visitor_hash' => self::visitorHash($request),
            'referrer_host' => $this->referrerHost($request),
            'device' => $this->device((string) $request->userAgent()),
            'locale' => app()->getLocale(),
        ]);

        $response->setContent(Str::replaceFirst('</head>', '<meta name="pv" content="'.$view->token().'">'."\n</head>", $content));

        return $response;
    }

    /**
     * Skrót odwiedzającego zmieniający się codziennie: pozwala policzyć unikalne osoby w ciągu dnia,
     * ale nie śledzi nikogo dłużej i nie zapisuje adresu IP.
     */
    public static function visitorHash(Request $request): string
    {
        return hash('sha256', implode('|', [$request->ip(), $request->userAgent(), now()->toDateString(), config('app.key')]));
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        return $request->isMethod('GET')
            && ! $request->ajax()
            && ! $request->is('panel', 'panel/*', 'livewire/*', 'storage/*', 'up')
            && $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            && ! preg_match(self::BOTS, (string) $request->userAgent())
            && ! $request->user()?->isAdmin();
    }

    private function referrerHost(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        return $host && $host !== $request->getHost() ? Str::limit($host, 120, '') : null;
    }

    private function device(string $userAgent): string
    {
        return match (true) {
            (bool) preg_match('/ipad|tablet/i', $userAgent) => 'tablet',
            (bool) preg_match('/mobi|android|iphone/i', $userAgent) => 'mobile',
            default => 'desktop',
        };
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\PageView;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Raport odwiedzin: co przyciąga uwagę (najczęściej oglądane) i co ją zatrzymuje
 * (najdłużej czytane, najgłębiej przewijane), skąd przychodzą goście i które zakładki otwierają.
 */
class SiteStatistics extends Component
{
    /** @var array<int, string> */
    public const PERIODS = [7 => '7 dni', 30 => '30 dni', 90 => '3 miesiące', 365 => 'Rok'];

    /** Działy strony rozpoznawane po nazwie trasy. */
    private const SECTIONS = [
        'home' => 'Strona główna',
        'articles.' => 'Aktualności',
        'schedule.' => 'Mecze',
        'games.' => 'Mecze',
        'standings' => 'Tabele',
        'teams.' => 'Drużyny',
        'players.' => 'Zawodnicy',
        'trainers.' => 'Sztab',
        'tournaments.' => 'Turnieje',
        'memorial' => 'Memoriał',
        'pages.' => 'Podstrony',
        'documents.' => 'Dokumenty',
        'contact' => 'Kontakt',
        'search' => 'Wyszukiwarka',
    ];

    #[Url(as: 'period')]
    public int $days = 30;

    public function setPeriod(int $days): void
    {
        $this->days = array_key_exists($days, self::PERIODS) ? $days : 30;
    }

    /** Źródła wejść rozpoznawane po adresie strony, z której ktoś przyszedł. */
    private const SOURCES = [
        'Facebook' => ['facebook.', 'fb.', 'messenger.'],
        'Instagram' => ['instagram.'],
        'Google' => ['google.'],
        'YouTube' => ['youtube.', 'youtu.be'],
        'TikTok' => ['tiktok.'],
        'Inne wyszukiwarki' => ['bing.', 'duckduckgo.', 'yahoo.', 'ecosia.', 'yandex.'],
        'Związki i ligi' => ['pzps', 'wzps', 'volley', 'siatk'],
    ];

    private const WEEKDAYS = ['Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob', 'Nd'];

    private function pages(?Carbon $from = null, ?Carbon $to = null): Builder
    {
        return PageView::query()->counted()->where('kind', PageView::KIND_PAGE)
            ->where('created_at', '>=', $from ?? $this->since())
            ->when($to, fn (Builder $query) => $query->where('created_at', '<', $to));
    }

    private function since(): Carbon
    {
        return now()->subDays($this->days)->startOfDay();
    }

    /**
     * Liczby okresu: wyświetlenia, wizyty (odwiedzający liczony raz dziennie), strony na wizytę,
     * odbicia (wizyty z jedną stroną), czas i przewinięcie.
     *
     * @return array<string, float>
     */
    private function summary(Carbon $from, ?Carbon $to = null): array
    {
        $totals = $this->pages($from, $to)
            ->selectRaw('COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visits, AVG(duration_seconds) as avg_duration, AVG(max_scroll) as avg_scroll')
            ->toBase()->first();
        $perVisit = $this->pages($from, $to)->select('visitor_hash', DB::raw('COUNT(*) as pages'))->groupBy('visitor_hash')->toBase();
        $bounces = DB::query()->fromSub($perVisit, 'visits')->where('pages', 1)->count();
        $visits = (int) ($totals->visits ?? 0);

        return [
            'views' => (int) ($totals->views ?? 0),
            'visits' => $visits,
            'pages_per_visit' => $visits ? $totals->views / $visits : 0,
            'bounce' => $visits ? $bounces / $visits * 100 : 0,
            'avg_duration' => (float) ($totals->avg_duration ?? 0),
            'avg_scroll' => (float) ($totals->avg_scroll ?? 0),
        ];
    }

    /**
     * Zmiana względem poprzedniego okresu tej samej długości, w procentach (null, gdy nie ma z czym porównać).
     */
    public static function change(float $now, float $before): ?int
    {
        return $before > 0 ? (int) round(($now - $before) / $before * 100) : null;
    }

    public static function sourceName(?string $host): string
    {
        if ($host === null) {
            return 'Wejścia bezpośrednie';
        }

        foreach (self::SOURCES as $name => $needles) {
            if (Str::contains(mb_strtolower($host), $needles)) {
                return $name;
            }
        }

        return 'Inne strony';
    }

    /** Godzina i dzień tygodnia (0 = poniedziałek) w SQL dla MySQL i SQLite. */
    private static function hourSql(): string
    {
        return DB::getDriverName() === 'sqlite' ? "CAST(strftime('%H', created_at) AS INTEGER)" : 'HOUR(created_at)';
    }

    private static function weekdaySql(): string
    {
        return DB::getDriverName() === 'sqlite' ? "(CAST(strftime('%w', created_at) AS INTEGER) + 6) % 7" : 'WEEKDAY(created_at)';
    }

    /**
     * Pierwsze (wejście) albo ostatnie (wyjście) strony wizyt.
     *
     * @return Collection<int, object>
     */
    private function visitEdges(string $edge): Collection
    {
        $ids = $this->pages()->selectRaw(($edge === 'first' ? 'MIN' : 'MAX').'(id)')->groupBy('visitor_hash')->toBase();
        $visitsWithMore = $this->pages()->select('visitor_hash')->groupBy('visitor_hash')->havingRaw('COUNT(*) > 1')->toBase();

        return PageView::query()->whereIn('id', $ids)
            // Wyjście liczymy tylko z wizyt dłuższych niż jedna strona (jedna strona to odbicie, nie wyjście „po drodze”).
            ->when($edge === 'last', fn (Builder $query) => $query->whereIn('visitor_hash', $visitsWithMore))
            ->select('path', DB::raw('MAX(title) as title'), DB::raw('COUNT(*) as visits'))
            ->groupBy('path')->orderByDesc('visits')->limit(8)->toBase()->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function topIn(string $routePrefix, int $limit = 6): Collection
    {
        return $this->pages()->where('route_name', 'like', $routePrefix.'%')
            ->select('path', DB::raw('MAX(title) as title'), DB::raw('COUNT(*) as views'), DB::raw('AVG(duration_seconds) as avg_duration'))
            ->groupBy('path')->orderByDesc('views')->limit($limit)->toBase()->get();
    }

    public static function formatDuration(?float $seconds): string
    {
        $seconds = (int) round((float) $seconds);

        return $seconds >= 60 ? intdiv($seconds, 60).' min '.($seconds % 60).' s' : $seconds.' s';
    }

    public static function sectionName(?string $routeName): string
    {
        foreach (self::SECTIONS as $prefix => $label) {
            if ($routeName !== null && str_starts_with($routeName, $prefix)) {
                return $label;
            }
        }

        return 'Inne';
    }

    /**
     * @return Collection<int, object>
     */
    private function topPages(string $orderBy, int $minViews = 1): Collection
    {
        return $this->pages()
            ->select('path', DB::raw('MAX(title) as title'), DB::raw('COUNT(*) as views'), DB::raw('COUNT(DISTINCT visitor_hash) as visitors'), DB::raw('AVG(duration_seconds) as avg_duration'), DB::raw('AVG(max_scroll) as avg_scroll'))
            ->groupBy('path')
            ->havingRaw('COUNT(*) >= ?', [$minViews])
            ->orderByDesc($orderBy)
            ->limit(15)
            ->toBase()
            ->get();
    }

    public function render(): View
    {
        $summary = $this->summary($this->since());
        $previous = $this->summary($this->since()->subDays($this->days), $this->since());

        $daily = $this->pages()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visits')
            ->groupBy('day')
            ->toBase()
            ->get()
            ->keyBy('day');

        $series = collect(range($this->days - 1, 0))->map(fn (int $ago) => [
            'date' => now()->subDays($ago),
            'views' => (int) ($daily[now()->subDays($ago)->toDateString()]->views ?? 0),
            'visits' => (int) ($daily[now()->subDays($ago)->toDateString()]->visits ?? 0),
        ]);

        // Dziś i wczoraj godzina po godzinie.
        $hourly = fn (Carbon $day) => $this->pages($day->copy()->startOfDay(), $day->copy()->addDay()->startOfDay())
            ->selectRaw(self::hourSql().' as hour, COUNT(*) as views')->groupBy('hour')->toBase()->pluck('views', 'hour');
        $today = $hourly(today());
        $yesterday = $hourly(today()->subDay());
        $hours = collect(range(0, 23))->map(fn (int $hour) => ['hour' => $hour, 'today' => (int) ($today[$hour] ?? 0), 'yesterday' => (int) ($yesterday[$hour] ?? 0)]);

        // Kiedy odwiedzają: dzień tygodnia × godzina.
        $heat = $this->pages()->selectRaw(self::weekdaySql().' as weekday, '.self::hourSql().' as hour, COUNT(*) as views')
            ->groupBy('weekday', 'hour')->toBase()->get();
        $heatmap = collect(range(0, 6))->map(fn (int $weekday) => [
            'day' => self::WEEKDAYS[$weekday],
            'hours' => collect(range(0, 23))->map(fn (int $hour) => (int) ($heat->first(fn ($row) => (int) $row->weekday === $weekday && (int) $row->hour === $hour)->views ?? 0))->all(),
        ]);

        // Teraz na stronie: ostatnie 5 minut.
        $online = PageView::query()->counted()->where('kind', PageView::KIND_PAGE)->where('created_at', '>=', now()->subMinutes(5));
        $now = [
            'people' => (clone $online)->distinct()->count('visitor_hash'),
            'pages' => (clone $online)->select('path', DB::raw('MAX(title) as title'), DB::raw('COUNT(DISTINCT visitor_hash) as people'))->groupBy('path')->orderByDesc('people')->limit(6)->toBase()->get(),
        ];

        // Ile stron ogląda się w jednej wizycie.
        $perVisit = $this->pages()->select('visitor_hash', DB::raw('COUNT(*) as pages'))->groupBy('visitor_hash')->toBase()->get()->pluck('pages');
        $depth = collect(['1 strona' => [1, 1], '2–3 strony' => [2, 3], '4–9 stron' => [4, 9], '10 i więcej' => [10, PHP_INT_MAX]])
            ->map(fn (array $range) => $perVisit->filter(fn ($pages) => $pages >= $range[0] && $pages <= $range[1])->count());

        // Źródło wizyty to strona, z której przyszło jej pierwsze wyświetlenie (dalsze kliknięcia są już wewnątrz strony).
        $sources = PageView::query()->whereIn('id', $this->pages()->selectRaw('MIN(id)')->groupBy('visitor_hash')->toBase())
            ->select('referrer_host', DB::raw('COUNT(*) as visits'))->groupBy('referrer_host')->toBase()->get()
            ->groupBy(fn (object $row) => self::sourceName($row->referrer_host))
            ->map(fn (Collection $rows) => $rows->sum('visits'))
            ->sortDesc();

        $sections = $this->pages()
            ->selectRaw('route_name, COUNT(*) as views, AVG(duration_seconds) as avg_duration')
            ->groupBy('route_name')
            ->toBase()
            ->get()
            ->groupBy(fn (object $row) => self::sectionName($row->route_name))
            ->map(fn (Collection $rows, string $name) => [
                'name' => $name,
                'views' => $rows->sum('views'),
                'avg_duration' => $rows->sum(fn (object $row) => $row->avg_duration * $row->views) / max(1, $rows->sum('views')),
            ])
            ->sortByDesc('views')
            ->values();

        return view('livewire.admin.site-statistics', [
            'summary' => $summary,
            'previous' => $previous,
            'series' => $series,
            'maxDaily' => max(1, $series->max('views')),
            'hours' => $hours,
            'maxHourly' => max(1, $hours->max('today'), $hours->max('yesterday')),
            'heatmap' => $heatmap,
            'maxHeat' => max(1, $heat->max('views') ?? 0),
            'now' => $now,
            'depth' => $depth,
            'sources' => $sources,
            'entries' => $this->visitEdges('first'),
            'exits' => $this->visitEdges('last'),
            'topArticles' => $this->topIn('articles.show'),
            'topPlayers' => $this->topIn('players.show'),
            'topTeams' => $this->topIn('teams.show'),
            'locales' => $this->pages()->select('locale', DB::raw('COUNT(*) as views'))->groupBy('locale')->toBase()->pluck('views', 'locale'),
            'sections' => $sections,
            'popular' => $this->topPages('views'),
            'engaging' => $this->topPages('avg_duration', 3),
            'tabs' => PageView::query()
                ->counted()
                ->where('kind', PageView::KIND_TAB)
                ->where('created_at', '>=', $this->since())
                ->select('label', DB::raw('MAX(title) as title'), DB::raw('COUNT(*) as clicks'))
                ->groupBy('label')
                ->orderByDesc('clicks')
                ->limit(15)
                ->toBase()
                ->get(),
            'referrers' => $this->pages()->whereNotNull('referrer_host')
                ->select('referrer_host', DB::raw('COUNT(*) as views'))
                ->groupBy('referrer_host')
                ->orderByDesc('views')
                ->limit(10)
                ->toBase()
                ->get(),
            'devices' => $this->pages()->select('device', DB::raw('COUNT(*) as views'))->groupBy('device')->toBase()->pluck('views', 'device'),
        ]);
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\PageView;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    #[Url(as: 'okres')]
    public int $days = 30;

    public function setPeriod(int $days): void
    {
        $this->days = array_key_exists($days, self::PERIODS) ? $days : 30;
    }

    private function pages(): Builder
    {
        return PageView::query()->where('kind', PageView::KIND_PAGE)->where('created_at', '>=', $this->since());
    }

    private function since(): Carbon
    {
        return now()->subDays($this->days)->startOfDay();
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
        $totals = $this->pages()
            ->selectRaw('COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visitors, AVG(duration_seconds) as avg_duration, AVG(max_scroll) as avg_scroll')
            ->toBase()
            ->first();

        $daily = $this->pages()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as views')
            ->groupBy('day')
            ->toBase()
            ->pluck('views', 'day');

        $series = collect(range($this->days - 1, 0))->map(fn (int $ago) => [
            'date' => now()->subDays($ago),
            'views' => (int) ($daily[now()->subDays($ago)->toDateString()] ?? 0),
        ]);

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
            'totals' => $totals,
            'series' => $series,
            'maxDaily' => max(1, $series->max('views')),
            'sections' => $sections,
            'popular' => $this->topPages('views'),
            'engaging' => $this->topPages('avg_duration', 3),
            'tabs' => PageView::query()
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

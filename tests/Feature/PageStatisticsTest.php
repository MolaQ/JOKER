<?php

use App\Livewire\Admin\SiteStatistics;
use App\Livewire\VisitCounter;
use App\Models\PageView;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $this->team = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'youth']);
});

test('public pages are recorded without cookies and report time, scroll and tabs', function () {
    $response = $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone) Mobile')->withHeader('Referer', 'https://www.facebook.com/')->get('/teams/kadeci');

    $view = PageView::sole();
    expect($view->path)->toBe('/teams/kadeci')
        ->and($view->route_name)->toBe('teams.show')
        ->and($view->title)->toContain('Kadeci')
        ->and($view->device)->toBe('mobile')
        ->and($view->referrer_host)->toBe('www.facebook.com');
    $response->assertSee('<meta name="pv" content="'.$view->token().'">', false);

    $this->post('/stats/view', ['pv' => $view->token(), 'seconds' => 75, 'scroll' => 80])->assertNoContent();
    $this->post('/stats/tab', ['pv' => $view->token(), 'label' => 'Wyniki'])->assertNoContent();
    $this->post('/stats/view', ['pv' => $view->id.'.zlypodpis', 'seconds' => 9999])->assertNoContent();

    $view->refresh();
    expect($view->duration_seconds)->toBe(75)
        ->and($view->max_scroll)->toBe(80)
        ->and(PageView::where('kind', 'tab')->sole()->label)->toBe('Wyniki');
});

test('bots, admins and the admin panel are not counted', function () {
    $this->withHeader('User-Agent', 'Googlebot/2.1')->get('/teams/kadeci');
    $this->actingAs(User::factory()->admin()->create())->withHeader('User-Agent', 'Mozilla/5.0')->get('/teams/kadeci');
    $this->get('/panel');

    expect(PageView::count())->toBe(0);
});

test('admin sees what attracts and what holds attention', function () {
    foreach ([['/matches', 'Mecze | Joker', 'schedule.index', 10], ['/news/relacja', 'Relacja | Joker', 'articles.show', 240]] as [$path, $title, $route, $seconds]) {
        foreach (range(1, 3) as $visitor) {
            PageView::create(['path' => $path, 'title' => $title, 'route_name' => $route, 'visitor_hash' => str_repeat((string) $visitor, 64), 'device' => 'mobile', 'duration_seconds' => $seconds, 'max_scroll' => 50]);
        }
    }
    PageView::create(['path' => '/matches', 'title' => 'Mecze | Joker', 'route_name' => 'schedule.index', 'visitor_hash' => str_repeat('9', 64), 'device' => 'desktop']);

    $this->actingAs(User::factory()->admin()->create());
    $this->get(route('admin.statistics.index'))->assertOk();

    Livewire::test(SiteStatistics::class)
        ->assertSeeInOrder(['Najczęściej oglądane', 'Mecze', 'Relacja', 'Najdłużej czytane', 'Relacja', '4 min 0 s'])
        ->assertSee('Aktualności');

    $this->actingAs(User::factory()->trainer()->create())->get(route('admin.statistics.index'))->assertForbidden();
});

test('footer counter shows people online, visitors today and all page views', function () {
    $this->withHeader('User-Agent', 'Mozilla/5.0')->get('/teams/kadeci');
    $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone) Mobile')->get('/teams');
    PageView::forceCreate(['kind' => 'page', 'path' => '/', 'visitor_hash' => str_repeat('a', 64), 'created_at' => now()->subDays(3)]);
    Cache::forget('visit-counter');

    Livewire::test(VisitCounter::class)
        ->assertSeeInOrder(['2', 'osoby teraz', '2', 'gości dziś', '3', 'odsłony']);
});

test('admin sees who visits: visits, bounces, sources, entry pages, time of day and what is read', function () {
    $view = fn (string $visitor, string $path, string $route, ?string $referrer = null, string $at = 'now') => PageView::forceCreate([
        'kind' => 'page', 'path' => $path, 'title' => ucfirst(trim($path, '/')).' | Joker', 'route_name' => $route,
        'visitor_hash' => str_repeat($visitor, 64), 'device' => 'mobile', 'locale' => 'pl', 'referrer_host' => $referrer,
        'created_at' => $at === 'now' ? now() : Carbon::parse($at),
    ]);
    // Wizyta a: z Facebooka, trzy strony. Wizyta b: z Google, jedna strona (odbicie). Wizyta c: bezpośrednio, dwie strony.
    $view('a', '/news/memorial', 'articles.show', 'm.facebook.com', '-30 minutes');
    $view('a', '/players/jan-kowalski', 'players.show', null, '-20 minutes');
    $view('a', '/teams/kadeci', 'teams.show', null, '-10 minutes');
    $view('b', '/news/memorial', 'articles.show', 'www.google.com', '-3 minutes');
    $view('c', '/', 'home', null, '-2 minutes');
    $view('c', '/players/jan-kowalski', 'players.show', null, '-1 minute');
    // Stare odświeżenia paska „Na żywo” nie liczą się do niczego.
    foreach (range(1, 20) as $i) {
        $view('d', '/live-bar', 'live-bar');
    }
    // Poprzedni okres do porównania: jedna wizyta.
    $view('e', '/', 'home', null, '-40 days');

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(SiteStatistics::class)
        ->assertViewHas('summary', fn (array $summary) => $summary['views'] === 6 && $summary['visits'] === 3 && (float) $summary['pages_per_visit'] === 2.0 && (int) round($summary['bounce']) === 33)
        ->assertViewHas('sources', fn ($sources) => $sources->all() == ['Facebook' => 1, 'Google' => 1, 'Wejścia bezpośrednie' => 1])
        ->assertViewHas('now', fn (array $now) => $now['people'] === 2)
        ->assertViewHas('entries', fn ($entries) => $entries->firstWhere('path', '/news/memorial')->visits === 2)
        ->assertViewHas('exits', fn ($exits) => $exits->pluck('path')->sort()->values()->all() === ['/players/jan-kowalski', '/teams/kadeci'])
        ->assertViewHas('topPlayers', fn ($players) => $players->first()->views === 2)
        ->assertViewHas('depth', fn ($depth) => $depth->all() === ['1 strona' => 1, '2–3 strony' => 2, '4–9 stron' => 0, '10 i więcej' => 0])
        ->assertSee('Teraz na stronie')->assertSee('Kiedy odwiedzają')->assertSee('Strony wejścia')->assertDontSee('/live-bar');

    // Odświeżenie paska nie zapisuje się już jako wyświetlenie.
    $this->get('/live-bar?from=/');
    expect(PageView::where('route_name', 'live-bar')->count())->toBe(20);
});

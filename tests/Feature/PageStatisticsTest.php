<?php

use App\Livewire\Admin\SiteStatistics;
use App\Livewire\VisitCounter;
use App\Models\PageView;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $this->team = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'youth']);
});

test('public pages are recorded without cookies and report time, scroll and tabs', function () {
    $response = $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone) Mobile')->withHeader('Referer', 'https://www.facebook.com/')->get('/druzyny/kadeci');

    $view = PageView::sole();
    expect($view->path)->toBe('/druzyny/kadeci')
        ->and($view->route_name)->toBe('teams.show')
        ->and($view->title)->toContain('Kadeci')
        ->and($view->device)->toBe('mobile')
        ->and($view->referrer_host)->toBe('www.facebook.com');
    $response->assertSee('<meta name="pv" content="'.$view->token().'">', false);

    $this->post('/statystyki/wyswietlenie', ['pv' => $view->token(), 'seconds' => 75, 'scroll' => 80])->assertNoContent();
    $this->post('/statystyki/zakladka', ['pv' => $view->token(), 'label' => 'Wyniki'])->assertNoContent();
    $this->post('/statystyki/wyswietlenie', ['pv' => $view->id.'.zlypodpis', 'seconds' => 9999])->assertNoContent();

    $view->refresh();
    expect($view->duration_seconds)->toBe(75)
        ->and($view->max_scroll)->toBe(80)
        ->and(PageView::where('kind', 'tab')->sole()->label)->toBe('Wyniki');
});

test('bots, admins and the admin panel are not counted', function () {
    $this->withHeader('User-Agent', 'Googlebot/2.1')->get('/druzyny/kadeci');
    $this->actingAs(User::factory()->admin()->create())->withHeader('User-Agent', 'Mozilla/5.0')->get('/druzyny/kadeci');
    $this->get('/panel');

    expect(PageView::count())->toBe(0);
});

test('admin sees what attracts and what holds attention', function () {
    foreach ([['/mecze', 'Mecze | Joker', 'schedule.index', 10], ['/aktualnosci/relacja', 'Relacja | Joker', 'articles.show', 240]] as [$path, $title, $route, $seconds]) {
        foreach (range(1, 3) as $visitor) {
            PageView::create(['path' => $path, 'title' => $title, 'route_name' => $route, 'visitor_hash' => str_repeat((string) $visitor, 64), 'device' => 'mobile', 'duration_seconds' => $seconds, 'max_scroll' => 50]);
        }
    }
    PageView::create(['path' => '/mecze', 'title' => 'Mecze | Joker', 'route_name' => 'schedule.index', 'visitor_hash' => str_repeat('9', 64), 'device' => 'desktop']);

    $this->actingAs(User::factory()->admin()->create());
    $this->get(route('admin.statistics.index'))->assertOk();

    Livewire::test(SiteStatistics::class)
        ->assertSeeInOrder(['Najczęściej oglądane', 'Mecze', 'Relacja', 'Najdłużej czytane', 'Relacja', '4 min 0 s'])
        ->assertSee('Aktualności');

    $this->actingAs(User::factory()->trainer()->create())->get(route('admin.statistics.index'))->assertForbidden();
});

test('footer counter shows people online, visitors today and all page views', function () {
    $this->withHeader('User-Agent', 'Mozilla/5.0')->get('/druzyny/kadeci');
    $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone) Mobile')->get('/druzyny');
    PageView::forceCreate(['kind' => 'page', 'path' => '/', 'visitor_hash' => str_repeat('a', 64), 'created_at' => now()->subDays(3)]);
    Cache::forget('visit-counter');

    Livewire::test(VisitCounter::class)
        ->assertSee('2 osoby teraz')
        ->assertSee('2 gości dziś')
        ->assertSee('3 odsłon');
});

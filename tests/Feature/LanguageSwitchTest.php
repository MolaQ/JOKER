<?php

use App\Http\Middleware\SetLocale;
use App\Livewire\Admin\ArticleManager;
use App\Livewire\Admin\PageManager;
use App\Livewire\GamesBrowser;
use App\Models\Article;
use App\Models\Game;
use App\Models\Page;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->season = Season::create(['name' => '2026/2027', 'start_date' => now()->subMonth(), 'end_date' => now()->addMonths(8), 'is_current' => true]);
    $this->seniors = Team::create(['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior']);
    $this->gameDate = Carbon::parse('2026-11-14 18:00');

    Game::create([
        'team_id' => $this->seniors->id, 'season_id' => $this->season->id, 'opponent' => 'Warta Zawiercie',
        'is_home' => true, 'game_date' => $this->gameDate, 'status' => 'scheduled',
    ]);
});

test('the site is in Polish by default with both flags in the header', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<html lang="pl">', false)
        ->assertSee('Aktualności')
        ->assertSee(route('locale.switch', 'pl'), false)
        ->assertSee(route('locale.switch', 'en'), false)
        ->assertSeeInOrder(['hreflang="pl"', 'hreflang="en"'], false);
});

test('switching to English translates the menu and the calendar', function () {
    $this->from(route('schedule.index'))
        ->get(route('locale.switch', 'en'))
        ->assertRedirect(route('schedule.index'))
        ->assertSessionHas(SetLocale::SESSION_KEY, 'en');

    $this->get(route('schedule.index'))
        ->assertOk()
        ->assertSee('<html lang="en">', false)
        ->assertSee('News')
        ->assertSee('Matches')
        ->assertSee('Standings')
        ->assertSee('Teams')
        ->assertSee('November')
        ->assertSeeInOrder(['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'])
        ->assertSee('1 match')
        ->assertSee('Upcoming')
        ->assertDontSee('Aktualności')
        ->assertDontSee('listopad');
});

test('switching back to Polish restores Polish texts', function () {
    $this->get(route('locale.switch', 'en'));
    $this->get(route('locale.switch', 'pl'))->assertSessionHas(SetLocale::SESSION_KEY, 'pl');

    $this->get(route('schedule.index'))
        ->assertSee('<html lang="pl">', false)
        ->assertSee('Aktualności')
        ->assertSee('listopad')
        ->assertSee('1 mecz')
        ->assertDontSee('Upcoming');
});

test('an unknown language is rejected', function () {
    $this->get('/jezyk/de')->assertNotFound();
});

test('an article with an English version shows it with a British flag', function () {
    $article = Article::factory()->create([
        'title' => 'Wygrana z liderem',
        'title_en' => 'Victory over the leaders',
        'excerpt' => 'Polska zajawka',
        'excerpt_en' => 'English excerpt',
        'content' => '<p>Polska treść meczu</p>',
        'content_en' => '<p>English match report</p>',
        'status' => 'published',
        'published_at' => now()->subDay(),
        'author_id' => User::factory(),
    ]);

    $this->get(route('locale.switch', 'en'));

    $this->get(route('articles.index'))
        ->assertSee('Victory over the leaders')
        ->assertSee('English version available')
        ->assertDontSee('Wygrana z liderem');

    $this->get(route('articles.show', $article))
        ->assertSee('Victory over the leaders')
        ->assertSee('English match report')
        ->assertSee('English excerpt')
        ->assertDontSee('Polska treść meczu');

    $this->get(route('locale.switch', 'pl'));

    $this->get(route('articles.show', $article))
        ->assertSee('Wygrana z liderem')
        ->assertSee('Polska treść meczu')
        ->assertSee('Dostępna wersja angielska');
});

test('an article without a translation falls back to Polish', function () {
    $article = Article::factory()->create([
        'title' => 'Tylko po polsku',
        'title_en' => null,
        'content' => '<p>Polska treść</p>',
        'content_en' => null,
        'status' => 'published',
        'published_at' => now()->subDay(),
        'author_id' => User::factory(),
    ]);

    $this->get(route('locale.switch', 'en'));

    $this->get(route('articles.index'))
        ->assertSee('Tylko po polsku')
        ->assertDontSee('English version available');

    $this->get(route('articles.show', $article))
        ->assertSee('Tylko po polsku')
        ->assertSee('Polska treść')
        ->assertSee('This post is only available in Polish.');
});

test('English plural forms are correct', function () {
    app()->setLocale('en');

    expect(GamesBrowser::yearsLabel(1))->toBe('1 year')
        ->and(GamesBrowser::yearsLabel(17))->toBe('17 years')
        ->and(trans_choice(':count mecz|:count mecze|:count meczów', 5))->toBe('5 matches');

    app()->setLocale('pl');

    expect(GamesBrowser::yearsLabel(22))->toBe('22 lata')
        ->and(trans_choice(':count mecz|:count mecze|:count meczów', 5))->toBe('5 meczów')
        ->and(trans_choice(':count mecz|:count mecze|:count meczów', 3))->toBe('3 mecze');
});

test('the admin panel stays Polish when the visitor chose English', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
    $this->get(route('locale.switch', 'en'));

    $this->get(route('admin.dashboard'))->assertOk();

    expect(app()->getLocale())->toBe('pl');
});

test('Livewire updates keep the chosen language', function () {
    $this->get(route('locale.switch', 'en'));
    $html = $this->get(route('schedule.index'))->getContent();

    preg_match('/wire:snapshot="([^"]+)"[^>]*wire:effects="[^"]*"[^>]*wire:id="[^"]+"[^>]*wire:name="games-browser"/', $html, $snapshotMatch);
    preg_match('/data-update-uri="([^"]+)"/', $html, $uriMatch);

    $response = $this->withHeaders(['X-Livewire' => '1'])->postJson($uriMatch[1], [
        '_token' => csrf_token(),
        'components' => [[
            'snapshot' => html_entity_decode($snapshotMatch[1]),
            'updates' => ['period' => 'wszystkie'],
            'calls' => [],
        ]],
    ]);

    $response->assertOk();
    expect($response->json('components.0.effects.html'))
        ->toContain('November')
        ->toContain('All')
        ->not->toContain('listopad');
});

test('admins can add an optional English version of an article and a page', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    Livewire::test(ArticleManager::class)
        ->call('openCreateModal')
        ->set('title', 'Nowy wpis')
        ->set('content', '<p>Treść</p>')
        ->set('title_en', 'New post')
        ->set('content_en', '<p>Content</p>')
        ->call('save')
        ->assertHasNoErrors();

    $article = Article::where('title', 'Nowy wpis')->firstOrFail();
    expect($article->hasEnglish())->toBeTrue()
        ->and($article->excerpt_en)->toBeNull();

    Livewire::test(PageManager::class)
        ->call('openCreateModal')
        ->set('title', 'O klubie')
        ->set('content', '<p>Treść</p>')
        ->set('title_en', 'About the club')
        ->set('lead_en', 'Who we are')
        ->call('save')
        ->assertHasNoErrors();

    app()->setLocale('en');
    $page = Page::where('title', 'O klubie')->firstOrFail();
    expect($page->localizedTitle())->toBe('About the club')
        ->and($page->localizedLead())->toBe('Who we are')
        ->and($page->localizedContent())->toBe('<p>Treść</p>');
});

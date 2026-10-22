<?php

use App\Models\Article;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(fn () => Cache::forget('sitemap.xml'));

test('sitemap lists public pages, teams, players and articles', function () {
    $team = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'cadet']);
    $player = Player::factory()->create(['first_name' => 'Marcel', 'last_name' => 'Molak', 'team_id' => $team->id]);
    $article = Article::factory()->for(User::factory(), 'author')->create(['status' => 'published', 'published_at' => now()->subDay()]);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<urlset', false)
        ->assertSee(route('teams.show', $team), false)
        ->assertSee(route('players.show', $player), false)
        ->assertSee(route('articles.show', $article), false);
});

test('robots file hides the panel and points to the sitemap', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Disallow: /panel')
        ->assertSee('Sitemap: '.route('sitemap'));
});

test('public pages carry canonical address and structured data', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.url('/').'">', false)
        ->assertSee('application/ld+json', false)
        ->assertSee('SportsOrganization')
        ->assertSee('og:title', false)
        ->assertSee('index, follow', false);
});

test('login page is not indexed', function () {
    $this->get('/login')->assertOk()->assertSee('noindex, follow', false);
});

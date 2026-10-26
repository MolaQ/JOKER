<?php

use App\Livewire\Admin\PlayerManager;
use App\Livewire\Admin\SeoManager;
use App\Models\Article;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Support\Sitemap;
use Livewire\Livewire;

test('an article published without a date shows up at once and a featured one is pinned first', function () {
    $author = User::factory()->create();
    $old = Article::factory()->for($author, 'author')->create(['status' => 'published', 'published_at' => now()->subWeek(), 'is_featured' => true, 'title' => 'Stary wyróżniony']);
    $today = Article::factory()->for($author, 'author')->create(['status' => 'published', 'published_at' => null, 'title' => 'Dzisiejszy news']);

    expect($today->fresh()->published_at)->not->toBeNull()
        ->and(Article::forListing()->pluck('id')->all())->toBe([$old->id, $today->id]);

    $this->get(route('articles.index'))->assertSeeInOrder(['Stary wyróżniony', 'Dzisiejszy news']);
    $this->get(route('articles.show', $today))->assertOk();
});

test('players can be filtered by team and position buttons and searched by number', function () {
    $this->actingAs(User::factory()->admin()->create());
    $team = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'junior_mlodszy']);
    Player::factory()->create(['team_id' => $team->id, 'first_name' => 'Adam', 'last_name' => 'Nowacki', 'position' => 'opposite', 'jersey_number' => 7]);
    Player::factory()->create(['team_id' => $team->id, 'first_name' => 'Lech', 'last_name' => 'Liberowy', 'position' => 'libero', 'jersey_number' => 17]);

    Livewire::test(PlayerManager::class)
        ->set('positionFilter', 'opposite')->assertSee('Nowacki')->assertDontSee('Liberowy')
        ->set('positionFilter', '')->set('search', '17')->assertSee('Liberowy')->assertDontSee('Nowacki');

    // Zajęty numer w tej samej drużynie nie przechodzi zapisu.
    Livewire::test(PlayerManager::class)
        ->call('openCreateModal')
        ->set('first_name', 'Jan')->set('last_name', 'Zając')->set('team_id', $team->id)->set('jersey_number', 7)
        ->call('save')
        ->assertHasErrors(['jersey_number' => 'unique']);
});

test('sitemap is rebuilt from the panel and refreshed after content changes', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(SeoManager::class)->call('rebuild')->assertSee('aktualna');
    expect(Sitemap::meta()['stale'])->toBeFalse();

    $article = Article::factory()->for(User::factory(), 'author')->create(['status' => 'published', 'published_at' => now()->subHour()]);
    expect(Sitemap::meta()['stale'])->toBeTrue();

    $this->get('/sitemap.xml')->assertOk()->assertSee(route('articles.show', $article), false);
    $this->get(route('admin.seo.index'))->assertOk()->assertSee('Mapa strony (sitemap.xml)');
});

test('missing pages show the club 404 page', function () {
    $this->get('/nie-ma-takiej-strony')->assertNotFound()->assertSee('Aut! Tej strony nie ma');
});

<?php

use App\Livewire\Admin\ArticleManager;
use App\Livewire\Admin\DocumentManager;
use App\Livewire\Admin\GalleryManager;
use App\Livewire\Admin\GameManager;
use App\Livewire\Admin\UserDirectory;
use App\Livewire\Admin\UserManager;
use App\Models\Article;
use App\Models\Document;
use App\Models\Gallery;
use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    Carbon::setTestNow('2026-11-12 12:00');
    $this->admin = User::factory()->admin()->create();
    $this->season = Season::factory()->create();
    $this->team = Team::factory()->create(['name' => 'Kadeci']);
    $this->other = Team::factory()->create(['name' => 'Seniorzy']);
    $game = fn (array $attributes) => Game::factory()->create(['team_id' => $this->team->id, 'season_id' => $this->season->id, 'status' => 'finished', ...$attributes]);

    $this->homeWin = $game(['opponent' => 'Rywal Wygrana', 'is_home' => true, 'home_score' => 3, 'away_score' => 1, 'kind' => 'league', 'game_date' => '2026-11-01 17:00', 'venue' => 'Hala Arena']);
    $this->awayLoss = $game(['opponent' => 'Rywal Porażka', 'is_home' => false, 'home_score' => 3, 'away_score' => 0, 'kind' => 'friendly', 'game_date' => '2026-10-10 17:00']);
    $this->awayWin = $game(['opponent' => 'Rywal Wyjazd', 'is_home' => false, 'home_score' => 2, 'away_score' => 3, 'kind' => 'league', 'game_date' => '2026-11-11 17:00']);
    $this->upcoming = $game(['opponent' => 'Rywal Przyszły', 'is_home' => true, 'home_score' => null, 'away_score' => null, 'status' => 'scheduled', 'kind' => 'tournament', 'game_date' => '2026-11-20 10:00', 'team_id' => $this->other->id]);
});

function visibleOpponents($component): array
{
    return $component->viewData('games')->pluck('opponent')->sort()->values()->all();
}

test('match list filters by kind, place, result, team and season', function () {
    $list = Livewire::actingAs($this->admin)->test(GameManager::class);

    $list->call('setFilter', 'kindFilter', 'friendly');
    expect(visibleOpponents($list))->toBe(['Rywal Porażka']);

    $list->call('clearFilters')->call('setFilter', 'placeFilter', 'away');
    expect(visibleOpponents($list))->toBe(['Rywal Porażka', 'Rywal Wyjazd']);

    $list->call('clearFilters')->call('setFilter', 'resultFilter', 'win');
    expect(visibleOpponents($list))->toBe(['Rywal Wygrana', 'Rywal Wyjazd']);

    $list->call('clearFilters')->call('setFilter', 'resultFilter', 'loss');
    expect(visibleOpponents($list))->toBe(['Rywal Porażka']);

    $list->call('clearFilters')->call('setFilter', 'resultFilter', 'none');
    expect(visibleOpponents($list))->toBe(['Rywal Przyszły']);

    $list->call('clearFilters')->call('setFilter', 'teamFilter', (string) $this->other->id);
    expect(visibleOpponents($list))->toBe(['Rywal Przyszły']);

    // Drugie kliknięcie tego samego przycisku wyłącza filtr.
    $list->call('setFilter', 'teamFilter', (string) $this->other->id);
    expect(visibleOpponents($list))->toHaveCount(4)->and($list->instance()->activeFiltersCount())->toBe(0);
});

test('match list filters by date presets and a custom range', function () {
    $list = Livewire::actingAs($this->admin)->test(GameManager::class);

    $list->call('setDatePreset', 'upcoming');
    expect(visibleOpponents($list))->toBe(['Rywal Przyszły'])
        ->and($list->instance()->activeDatePreset())->toBe('upcoming');

    $list->call('setDatePreset', 'past');
    expect(visibleOpponents($list))->toBe(['Rywal Porażka', 'Rywal Wygrana', 'Rywal Wyjazd']);

    $list->call('setDatePreset', 'week');
    expect(visibleOpponents($list))->toBe(['Rywal Wyjazd']);

    $list->set('dateFrom', '2026-10-01')->set('dateTo', '2026-10-31');
    expect(visibleOpponents($list))->toBe(['Rywal Porażka'])
        ->and($list->instance()->activeDatePreset())->toBeNull();

    $list->call('clearFilters');
    expect(visibleOpponents($list))->toHaveCount(4);
});

test('match list search looks in venue and team name, and results sort by our set difference', function () {
    $list = Livewire::actingAs($this->admin)->test(GameManager::class);

    $list->set('search', 'Arena');
    expect(visibleOpponents($list))->toBe(['Rywal Wygrana']);

    $list->set('search', 'Seniorzy');
    expect(visibleOpponents($list))->toBe(['Rywal Przyszły']);

    $list->set('search', '')->call('sortBy', 'wynik');
    expect($list->viewData('games')->pluck('opponent')->all())->toBe(['Rywal Wygrana', 'Rywal Wyjazd', 'Rywal Porażka', 'Rywal Przyszły']);

    $list->assertSee('Znaleziono')->assertSee('Na wyjeździe')->assertSee('Bieżący sezon');
});

test('article list filters by link, features, author and publication date', function () {
    $author = User::factory()->create(['name' => 'Ola Autorka']);
    $base = ['status' => 'published', 'author_id' => $this->admin->id, 'is_featured' => false, 'title_en' => null];
    Article::factory()->create([...$base, 'title' => 'Relacja meczowa', 'published_at' => '2026-11-02 10:00'])->games()->attach($this->homeWin);
    Article::factory()->create([...$base, 'title' => 'Przypięty tekst', 'is_featured' => true, 'published_at' => '2026-09-01 10:00']);
    Article::factory()->create([...$base, 'title' => 'Tekst Oli', 'author_id' => $author->id, 'published_at' => '2026-11-10 10:00', 'content' => '<p>Wyjątkowe słowo kluczowe</p>']);

    $list = Livewire::actingAs($this->admin)->test(ArticleManager::class);
    $titles = fn () => $list->viewData('articles')->pluck('title')->sort()->values()->all();

    $list->call('setFilter', 'linkFilter', 'game');
    expect($titles())->toBe(['Relacja meczowa']);

    $list->call('clearFilters')->call('setFilter', 'extraFilter', 'featured');
    expect($titles())->toBe(['Przypięty tekst']);

    $list->call('clearFilters')->call('setFilter', 'authorFilter', (string) $author->id);
    expect($titles())->toBe(['Tekst Oli']);

    $list->call('clearFilters')->set('search', 'kluczowe');
    expect($titles())->toBe(['Tekst Oli']);

    $list->call('clearFilters')->call('setDatePreset', 'month');
    expect($titles())->toBe(['Relacja meczowa', 'Tekst Oli']);

    $list->call('clearFilters')->call('sortBy', 'data');
    expect($list->viewData('articles')->pluck('title')->all())->toBe(['Tekst Oli', 'Relacja meczowa', 'Przypięty tekst']);
});

test('users, galleries, documents and people lists filter with buttons', function () {
    $fan = User::factory()->create(['name' => 'Kibic Nowy', 'last_login_at' => null]);
    $fan->assignRole(UserRole::Fan);
    $old = User::factory()->create(['name' => 'Dawny Gość', 'last_login_at' => now()->subYear()]);

    $users = Livewire::actingAs($this->admin)->test(UserManager::class);
    $names = fn ($component, string $key) => $component->viewData($key)->pluck($key === 'users' ? 'name' : 'title')->all();

    $users->call('setFilter', 'roleFilter', 'fan');
    expect($names($users, 'users'))->toBe(['Kibic Nowy']);
    $users->call('clearFilters')->call('setFilter', 'loginFilter', 'stale');
    expect($names($users, 'users'))->toBe(['Dawny Gość']);

    Gallery::create(['title' => 'Galeria kadetów', 'team_id' => $this->team->id, 'is_published' => true, 'event_date' => '2026-11-05']);
    Gallery::create(['title' => 'Piknik klubowy', 'is_published' => false, 'event_date' => '2026-06-01']);
    $galleries = Livewire::actingAs($this->admin)->test(GalleryManager::class);
    $galleries->call('setFilter', 'teamFilter', (string) $this->team->id);
    expect($names($galleries, 'galleries'))->toBe(['Galeria kadetów']);
    $galleries->call('clearFilters')->call('setFilter', 'visibilityFilter', 'hidden');
    expect($names($galleries, 'galleries'))->toBe(['Piknik klubowy']);
    $galleries->call('clearFilters')->call('setDatePreset', 'month');
    expect($names($galleries, 'galleries'))->toBe(['Galeria kadetów']);

    Document::factory()->create(['title' => 'Regulamin', 'category' => 'regulations', 'is_public' => true, 'uploaded_by' => $this->admin->id]);
    Document::factory()->create(['title' => 'Zgoda RODO', 'category' => 'rodo', 'is_public' => false, 'uploaded_by' => $this->admin->id]);
    $documents = Livewire::actingAs($this->admin)->test(DocumentManager::class);
    $documents->call('setFilter', 'categoryFilter', 'rodo');
    expect($names($documents, 'documents'))->toBe(['Zgoda RODO']);
    $documents->call('clearFilters')->call('setFilter', 'visibilityFilter', 'public');
    expect($names($documents, 'documents'))->toBe(['Regulamin']);

    $people = Livewire::actingAs($this->admin)->test(UserDirectory::class);
    $people->call('setFilter', 'roleFilter', 'fan');
    expect($people->viewData('users')->pluck('name')->all())->toBe(['Kibic Nowy']);
});

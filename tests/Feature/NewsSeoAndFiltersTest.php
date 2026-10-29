<?php

use App\Livewire\Admin\ArticleManager;
use App\Livewire\Admin\AwardManager;
use App\Livewire\Admin\PlayerManager;
use App\Livewire\Admin\SeoManager;
use App\Models\Article;
use App\Models\Award;
use App\Models\Game;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Support\Sitemap;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Symfony\Component\Mailer\Exception\TransportException;

test('an article published without a date shows up at once and a featured one is pinned first', function () {
    $author = User::factory()->create();
    $old = Article::factory()->for($author, 'author')->create(['status' => 'published', 'published_at' => now()->subWeek(), 'is_featured' => true, 'title' => 'Stary wyróżniony']);
    $today = Article::factory()->for($author, 'author')->create(['status' => 'published', 'published_at' => null, 'is_featured' => false, 'title' => 'Dzisiejszy news']);

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

test('mecz dwóch pilskich drużyn pokazuje niebieskie serduszko zamiast porażki', function () {
    $season = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $derby = Game::factory()->create(['team_id' => Team::factory(), 'season_id' => $season->id, 'opponent' => 'LOMS Joker Piła', 'opponent_team_id' => null, 'status' => 'finished', 'is_home' => false, 'home_score' => 3, 'away_score' => 2]);
    $rival = Game::factory()->create(['team_id' => Team::factory(), 'season_id' => $season->id, 'opponent' => 'ENEA Energetyk Poznań', 'opponent_team_id' => null, 'status' => 'finished', 'is_home' => true, 'home_score' => 0, 'away_score' => 2]);
    $asbl = Game::factory()->make(['opponent' => 'KS ASBL Piła (kadeci II)', 'opponent_team_id' => null, 'status' => 'finished', 'home_score' => 3, 'away_score' => 0]);

    expect($derby->outcome())->toBe('derby')
        ->and($asbl->outcome())->toBe('derby')
        ->and($rival->outcome())->toBe('loss');

    $this->get(route('games.show', $derby))->assertOk()->assertSee('Wygrała pilska siatkówka');
});

test('lista artykułów w panelu pokazuje polubienia, komentarze, film i galerię', function () {
    $admin = User::factory()->admin()->create();
    $season = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $game = Game::factory()->create(['team_id' => Team::factory(), 'season_id' => $season->id, 'streams' => [['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'label' => '']]]);
    $game->galleries()->create(['title' => 'Zdjęcia', 'is_published' => true]);
    $article = Article::factory()->create(['author_id' => $admin->id, 'title' => 'Relacja z derbów', 'status' => 'published', 'views_count' => 1234]);
    $article->games()->attach($game);
    $article->likes()->create(['user_id' => $admin->id]);
    $article->comments()->create(['user_id' => $admin->id, 'content' => 'Brawo!', 'is_approved' => false]);

    Livewire::actingAs($admin)->test(ArticleManager::class)
        ->assertSee('Relacja z derbów')
        ->assertSee('1 234')
        ->assertSee('+1')
        ->assertSee('Film')
        ->assertSee('Galeria');
});

test('wyróżnienia z logo są w stopce i w danych dla Google, a panel je dodaje', function () {
    $this->get('/')->assertSee('Brązowy Ośrodek PZPS 2024/25, 2025/26 i 2026')->assertSee('Złoty certyfikat')
        ->assertSee('/img/wyroznienia/brazowy-osrodek-pzps.png', false)
        ->assertSee('Certyfikacja Młodzieżowych Ośrodków Siatkarskich PZPS)', false);

    Livewire::actingAs(User::factory()->admin()->create())->test(AwardManager::class)
        ->call('openCreateModal')
        ->set('name', 'Nagroda prezydenta miasta')
        ->set('note', '2025')
        ->call('save')
        ->assertHasNoErrors();

    expect(Award::latest('id')->first()->display_order)->toBe(3);
    $this->get('/')->assertSee('Nagroda prezydenta miasta');

    $last = Award::latest('id')->first();
    Livewire::test(AwardManager::class)->call('move', $last->id, -1);
    expect(Award::visible()->pluck('name')->all()[1])->toBe('Nagroda prezydenta miasta');
});

test('awaria serwera poczty przy przypomnieniu hasła wraca do formularza z komunikatem zamiast błędu 500', function () {
    User::factory()->create(['email' => 'trener@example.com']);
    Password::shouldReceive('sendResetLink')
        ->andThrow(new TransportException('Connection refused'));

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'trener@example.com'])
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('error');
});

test('a player in a second team this season is counted and filtered in both teams', function () {
    $this->actingAs(User::factory()->admin()->create());
    $season = Season::factory()->create(['is_current' => true]);
    $juniors = Team::create(['name' => 'Juniorzy', 'slug' => 'juniorzy', 'category' => 'junior', 'birth_year' => 2008]);
    $seniors = Team::create(['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior']);
    $player = Player::factory()->create(['team_id' => $seniors->id, 'first_name' => 'Alan', 'last_name' => 'Dwudrużynowy']);
    $player->teams()->attach([$seniors->id => ['season_id' => $season->id], $juniors->id => ['season_id' => $season->id]]);

    Livewire::test(PlayerManager::class)
        ->assertViewHas('teamCounts', fn ($counts) => $counts[$juniors->id] === 1 && $counts[$seniors->id] === 1)
        ->assertViewHas('totalCount', 1)
        ->assertSee('+ Juniorzy')
        ->set('teamFilter', (string) $juniors->id)->assertSee('Dwudrużynowy');
});

test('the player list shows when a player with an account last logged in', function () {
    $this->actingAs(User::factory()->admin()->create());
    $account = User::factory()->create(['password' => 'tajne-haslo-123']);
    Player::factory()->create(['user_id' => $account->id, 'last_name' => 'Zalogowany']);

    $this->post('/logout');
    $this->post(route('login'), ['email' => $account->email, 'password' => 'tajne-haslo-123']);

    expect($account->fresh()->last_login_at)->not->toBeNull();

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(PlayerManager::class)
        ->assertSee('Ostatnie logowanie')
        ->assertDontSee('Odbiór')
        ->assertSee($account->fresh()->last_login_at->diffForHumans());
});

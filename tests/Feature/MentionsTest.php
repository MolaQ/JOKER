<?php

use App\Livewire\Admin\ArticleManager;
use App\Livewire\Admin\MiniVolleyballManager;
use App\Models\Article;
use App\Models\MiniGroup;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\PlayerPosition;
use App\Support\Mentions;
use App\Support\SiteSettings;

beforeEach(function () {
    Mentions::flush();
    $this->team = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'youth']);
    $this->player = Player::factory()->create(['first_name' => 'Marcel', 'last_name' => 'Molak', 'team_id' => $this->team->id, 'position' => PlayerPosition::Opposite]);
});

it('turns @name into a link to the player profile, leaving tags and unknown names alone', function () {
    $html = Mentions::link('<p>Świetny mecz @Marcel Molak i @Jan Nieznany. <a href="/x">@Marcel Molak</a> <img alt="@Marcel Molak"></p>');

    expect($html)
        ->toContain('<a href="'.route('players.show', $this->player, false).'" class="mention">Marcel Molak</a> i @Jan Nieznany.')
        ->toContain('<a href="/x">@Marcel Molak</a>')
        ->toContain('alt="@Marcel Molak"');
});

it('shows mentions on the player profile only when articles mention them', function () {
    $this->get(route('players.show', $this->player))->assertOk()->assertDontSee('Wzmianki');

    Article::factory()->create(['author_id' => User::factory()->create()->id, 'title' => 'Kadeci wygrywają', 'content' => '<p>Najlepszy był @Marcel Molak.</p>', 'status' => 'published', 'published_at' => now()->subDay()]);
    Article::factory()->create(['author_id' => User::factory()->create()->id, 'title' => 'Szkic', 'content' => '<p>@Marcel Molak</p>', 'status' => 'draft']);
    Article::factory()->create(['author_id' => User::factory()->create()->id, 'title' => 'Bez oznaczenia', 'content' => '<p>Marcel Molak zagrał.</p>', 'status' => 'published', 'published_at' => now()->subDay()]);

    $this->get(route('players.show', $this->player))->assertOk()
        ->assertSee('Wzmianki')
        ->assertSee('Kadeci wygrywają')
        ->assertDontSee('Szkic')
        ->assertDontSee('Bez oznaczenia');
});

it('links mentions in the article and shows the configurable position bubble', function () {
    $article = Article::factory()->create(['author_id' => User::factory()->create()->id, 'content' => '<p>Brawo @Marcel Molak!</p>', 'status' => 'published', 'published_at' => now()->subDay()]);
    $this->get(route('articles.show', $article))->assertOk()->assertSee('class="mention"', false);

    app(SiteSettings::class)->set(['position_opposite' => 'Nasz najlepszy strzelec.']);
    $this->get(route('players.show', $this->player))->assertOk()->assertSee('Nasz najlepszy strzelec.');
});

it('gives the editor a list of players to mention', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->getJson(route('admin.mentions'))
        ->assertOk()
        ->assertJsonFragment(['name' => 'Marcel Molak', 'team' => 'Kadeci']);
});

it('lets the editor choose the article author or type an outside author', function () {
    $admin = User::factory()->admin()->create(['name' => 'Marcin Admin']);
    $coach = User::factory()->trainer()->create(['name' => 'Dawid Trener']);
    $this->actingAs($admin);

    Livewire\Livewire::test(ArticleManager::class)
        ->call('openCreateModal')
        ->assertSet('authorId', (string) $admin->id)
        ->set('title', 'Relacja')->set('content', '<p>Tekst</p>')
        ->set('authorId', (string) $coach->id)
        ->call('save')->assertHasNoErrors();

    $article = Article::where('title', 'Relacja')->firstOrFail();
    expect($article->author_id)->toBe($coach->id)->and($article->authorName())->toBe('Dawid Trener');

    Livewire\Livewire::test(ArticleManager::class)
        ->call('openEditModal', $article->id)
        ->set('authorName', 'Jan Gość')->set('status', 'published')
        ->call('save')->assertHasNoErrors();

    expect($article->fresh()->authorName())->toBe('Jan Gość');
    $this->get(route('articles.show', $article))->assertSee('Jan Gość');
});

it('gives every logged-in active account the list of people to mention, including mini children', function () {
    $group = MiniGroup::create(['name' => 'Dwójki testowe', 'players_on_court' => 2]);
    Player::factory()->create(['first_name' => 'Ania', 'last_name' => 'Majsz', 'team_id' => null, 'mini_group_id' => $group->id]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('mentions'))
        ->assertOk()
        ->assertJsonFragment(['name' => 'Marcel Molak', 'team' => 'Kadeci'])
        ->assertJsonFragment(['name' => 'Ania Majsz', 'team' => 'Dwójki testowe']);

    $this->actingAs(User::factory()->inactive()->create())->getJson(route('mentions'))->assertForbidden();
    auth()->logout();
    $this->getJson(route('mentions'))->assertUnauthorized();
});

it('turns on mentions in the mini group editors and links them on the group page', function () {
    $group = MiniGroup::create(['name' => 'Dwójki testowe', 'players_on_court' => 2, 'about' => '<p>Trener @Marcel Molak zaprasza.</p>', 'rules' => '<p>Sędziuje @Marcel Molak.</p>']);

    $this->actingAs(User::factory()->admin()->create());
    Livewire\Livewire::withQueryParams(['section' => 'grupa', 'group' => $group->id])
        ->test(MiniVolleyballManager::class)
        ->assertSee('oznaczenia.json', false)->assertSee('Zasady gry');

    $this->get(route('mini.show', $group))->assertOk()
        ->assertSee('<a href="'.route('players.show', $this->player, false).'" class="mention">Marcel Molak</a> zaprasza.', false);
});

it('links mentions in team descriptions and player bios', function () {
    $this->team->update(['description' => '<p>Trenuje nas @Marcel Molak.</p>']);
    $this->player->update(['bio' => '<p>Kapitan, kolega @Marcel Molak.</p>']);

    $this->get(route('teams.show', $this->team))->assertOk()->assertSee('class="mention"', false);
    $this->get(route('players.show', $this->player))->assertOk()->assertSee('class="mention"', false);
});

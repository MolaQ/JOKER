<?php

use App\Models\Article;
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

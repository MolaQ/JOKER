<?php

use App\Livewire\LikeButton;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Gallery;
use App\Models\Game;
use App\Models\GameComment;
use App\Models\Like;
use App\Models\Page;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->season = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $this->team = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'youth']);
    $this->game = Game::factory()->create(['team_id' => $this->team->id, 'season_id' => $this->season->id, 'opponent' => 'MKS Kalisz']);
    $this->article = Article::factory()->for(User::factory(), 'author')->create(['status' => 'published', 'published_at' => now()->subDay(), 'allow_comments' => true]);
});

test('guests see only comment counts and a login link', function () {
    Comment::create(['article_id' => $this->article->id, 'user_id' => User::factory()->create()->id, 'content' => 'Tajny komentarz kibica', 'is_approved' => true]);
    GameComment::create(['game_id' => $this->game->id, 'user_id' => User::factory()->create()->id, 'content' => 'Świetny mecz!', 'is_approved' => true]);

    $this->get(route('articles.show', $this->article))
        ->assertOk()
        ->assertSee('💬')
        ->assertSee('1 komentarz')
        ->assertSee('Zaloguj się, aby komentować')
        ->assertDontSee('Tajny komentarz kibica')
        ->assertDontSee('name="content"', false);

    $this->get(route('games.show', $this->game))
        ->assertOk()
        ->assertSee('Zaloguj się, aby komentować')
        ->assertDontSee('Świetny mecz!');

    $this->get(route('articles.index'))->assertOk()->assertSee('💬');
});

test('logged in users see and add comments on articles and games', function () {
    $user = User::factory()->fan()->create();
    Comment::create(['article_id' => $this->article->id, 'user_id' => User::factory()->create()->id, 'content' => 'Tajny komentarz kibica', 'is_approved' => true]);

    $this->actingAs($user)->get(route('articles.show', $this->article))
        ->assertSee('Tajny komentarz kibica')
        ->assertSee('name="content"', false);

    $this->actingAs($user)->post(route('articles.comments.store', $this->article), ['content' => 'Brawo drużyna!'])->assertRedirect();
    $this->actingAs($user)->post(route('games.comments.store', $this->game), ['content' => 'Świetny mecz!'])
        ->assertRedirect(route('games.show', $this->game).'#komentarze');

    expect(Comment::where('content', 'Brawo drużyna!')->where('user_id', $user->id)->exists())->toBeTrue()
        ->and(GameComment::where('game_id', $this->game->id)->where('user_id', $user->id)->value('content'))->toBe('Świetny mecz!');

    $this->actingAs($user)->get(route('games.show', $this->game))->assertSee('Świetny mecz!');
});

test('guests cannot post game comments', function () {
    $this->post(route('games.comments.store', $this->game), ['content' => 'Spam'])->assertRedirect(route('login'));

    expect(GameComment::count())->toBe(0);
});

test('logged in users like every kind of content', function (string $type, Closure $make) {
    $model = $make($this);
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(LikeButton::class, ['type' => $type, 'id' => $model->id])
        ->assertSet('likesCount', 0)
        ->call('toggle')
        ->assertSet('liked', true)
        ->assertSet('likesCount', 1)
        ->assertDispatched('like-toggled');

    expect($model->likes()->where('user_id', $user->id)->exists())->toBeTrue();

    Livewire::actingAs($user)
        ->test(LikeButton::class, ['type' => $type, 'id' => $model->id])
        ->call('toggle')
        ->assertSet('likesCount', 0);
})->with([
    'article' => ['article', fn ($test) => $test->article],
    'game' => ['game', fn ($test) => $test->game],
    'team' => ['team', fn ($test) => $test->team],
    'player' => ['player', fn ($test) => Player::factory()->create(['team_id' => $test->team->id])],
    'page' => ['page', fn ($test) => Page::factory()->create()],
    'tournament' => ['tournament', fn ($test) => Tournament::factory()->create()],
    'tournament match' => ['tournament_match', fn ($test) => Tournament::factory()->create()->categories()->create(['name' => 'Kadeci'])->matches()->create(['stage' => TournamentMatch::STAGE_GROUP])],
    'gallery' => ['gallery', fn ($test) => Gallery::factory()->create(['match_id' => $test->game->id])],
]);

test('guest like does nothing and shows only the count', function () {
    Like::create(['user_id' => User::factory()->create()->id, 'likeable_type' => $this->article->getMorphClass(), 'likeable_id' => $this->article->id]);

    Livewire::test(LikeButton::class, ['type' => 'article', 'id' => $this->article->id])
        ->assertSet('likesCount', 1)
        ->assertSee('Zaloguj się, aby polubić')
        ->assertDontSeeHtml('wire:click="toggle"')
        ->call('toggle')
        ->assertSet('likesCount', 1)
        ->assertNotDispatched('like-toggled');

    expect(Like::count())->toBe(1);
});

test('like buttons appear on team, page and tournament pages', function () {
    $page = Page::factory()->create(['is_published' => true]);
    $tournament = Tournament::factory()->create(['has_page' => true]);

    $this->get(route('teams.show', $this->team))->assertOk()->assertSee('Zaloguj się, aby polubić');
    $this->get(route('pages.show', $page))->assertOk()->assertSee('Zaloguj się, aby polubić');
    $this->get(route('tournaments.show', $tournament))->assertOk()->assertSee('Zaloguj się, aby polubić');
    $this->get(route('games.show', $this->game))->assertOk()->assertSee('Zaloguj się, aby polubić');
});

<?php

use App\Livewire\FanMvpPanel;
use App\Models\FanMvpVote;
use App\Models\Game;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Support\FanMvp;
use App\Support\SiteSettings;
use App\UserRole;
use Livewire\Livewire;

beforeEach(function () {
    $this->season = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $this->team = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'youth']);
    $this->game = Game::factory()->create(['team_id' => $this->team->id, 'season_id' => $this->season->id, 'opponent' => 'MKS Kalisz', 'game_date' => now()->subHour(), 'status' => 'live']);
    $this->adam = Player::factory()->create(['team_id' => $this->team->id, 'first_name' => 'Adam', 'last_name' => 'Atakujący', 'is_active' => true]);
    $this->bartek = Player::factory()->create(['team_id' => $this->team->id, 'first_name' => 'Bartek', 'last_name' => 'Blokujący', 'is_active' => true]);
    $this->fan = User::factory()->create(['role' => UserRole::Fan, 'is_active' => true]);
});

test('guests see neither the voting nor the result', function () {
    $this->get(route('games.show', $this->game))->assertOk()->assertDontSee('kibicowskie-mvp', false)->assertDontSee('Atakujący');

    Livewire::test(FanMvpPanel::class, ['type' => 'game', 'id' => $this->game->id])->assertDontSee('Atakujący')->call('vote', $this->adam->id);

    expect(FanMvpVote::count())->toBe(0);
});

test('a logged in fan votes once and can change the vote while the window is open', function () {
    $this->actingAs($this->fan)->get(route('games.show', $this->game))->assertOk()->assertSee('kibicowskie-mvp', false)->assertSee('Atakujący')->assertDontSee('%</span>', false);

    Livewire::actingAs($this->fan)->test(FanMvpPanel::class, ['type' => 'game', 'id' => $this->game->id])
        ->call('vote', $this->adam->id)->assertSee('Twój głos')
        ->call('vote', $this->bartek->id);

    expect(FanMvpVote::count())->toBe(1)->and(FanMvp::votedFor($this->fan, $this->game))->toBe($this->bartek->id);
});

test('a player cannot vote for himself and a parent cannot vote for their child', function () {
    $playerUser = User::factory()->create(['role' => UserRole::Fan, 'is_active' => true]);
    $this->adam->update(['user_id' => $playerUser->id]);
    $parent = User::factory()->create(['role' => UserRole::Parent, 'is_active' => true]);
    $this->adam->guardians()->attach($parent->id);

    expect(FanMvp::vote($playerUser, $this->game, $this->adam->id))->toContain('Nie można oddać głosu na samego siebie')
        ->and(FanMvp::vote($parent, $this->game, $this->adam->id))->toContain('Nie można głosować na swoje dziecko')
        ->and(FanMvp::vote($parent, $this->game, $this->bartek->id))->toBeNull()
        ->and(FanMvp::vote($playerUser, $this->game, $this->bartek->id))->toBeNull()
        ->and(FanMvpVote::where('player_id', $this->adam->id)->count())->toBe(0);

    Livewire::actingAs($parent)->test(FanMvpPanel::class, ['type' => 'game', 'id' => $this->game->id])->assertSee('Nie można głosować na swoje dziecko')->assertDontSee('Atakujący')->assertSee('Blokujący');
    Livewire::actingAs($playerUser)->test(FanMvpPanel::class, ['type' => 'game', 'id' => $this->game->id])->assertSee('Nie można oddać głosu na samego siebie')->assertDontSee('Atakujący');
    Livewire::actingAs($this->fan)->test(FanMvpPanel::class, ['type' => 'game', 'id' => $this->game->id])->assertDontSee('Nie można')->assertSee('Atakujący');
});

test('voting is closed before the match starts, after the configured window and for matches without a time', function () {
    $this->game->update(['game_date' => now()->addHour()]);
    expect(FanMvp::state($this->game->fresh()))->toBe('before')->and(FanMvp::vote($this->fan, $this->game->fresh(), $this->adam->id))->not->toBeNull();

    $this->game->update(['game_date' => now()->subHours(5)]);
    expect(FanMvp::state($this->game->fresh()))->toBe('closed')->and(FanMvp::vote($this->fan, $this->game->fresh(), $this->adam->id))->not->toBeNull();

    app(SiteSettings::class)->set(['fan_mvp_hours' => '6']);
    expect(FanMvp::state($this->game->fresh()))->toBe('open');

    $this->game->update(['game_date' => now()->startOfDay()]);
    expect(FanMvp::state($this->game->fresh()))->toBe('off');
});

test('the result shows only after the voting ends, as percentages', function () {
    $second = User::factory()->create(['role' => UserRole::Fan, 'is_active' => true]);
    $third = User::factory()->create(['role' => UserRole::Fan, 'is_active' => true]);
    FanMvp::vote($this->fan, $this->game, $this->adam->id);
    FanMvp::vote($second, $this->game, $this->adam->id);
    FanMvp::vote($third, $this->game, $this->bartek->id);

    Livewire::actingAs($this->fan)->test(FanMvpPanel::class, ['type' => 'game', 'id' => $this->game->id])->assertDontSee('67%');
    expect(FanMvp::winners($this->game))->toBeEmpty();

    $this->travel(4)->hours();

    Livewire::actingAs($this->fan)->test(FanMvpPanel::class, ['type' => 'game', 'id' => $this->game->id])->assertSee('67%')->assertSee('33%')->assertSee('★ MVP');
    expect(FanMvp::winners($this->game)->pluck('player.id')->all())->toBe([$this->adam->id]);

    $this->actingAs($this->fan)->get(route('players.show', $this->adam))->assertOk()->assertSee('fan-mvp-badge', false);
    auth()->logout();
    $this->get(route('players.show', $this->adam))->assertOk()->assertDontSee('fan-mvp-badge', false);
});

test('an admin removes a player and the title passes to the next one', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
    $second = User::factory()->create(['role' => UserRole::Fan, 'is_active' => true]);
    FanMvp::vote($this->fan, $this->game, $this->adam->id);
    FanMvp::vote($second, $this->game, $this->adam->id);
    FanMvp::vote($admin, $this->game, $this->bartek->id);

    Livewire::actingAs($this->fan)->test(FanMvpPanel::class, ['type' => 'game', 'id' => $this->game->id])->call('exclude', $this->adam->id);
    expect(FanMvp::candidates($this->game))->toHaveCount(2);

    $this->travel(4)->hours();

    Livewire::actingAs($admin)->test(FanMvpPanel::class, ['type' => 'game', 'id' => $this->game->id])->assertSee('Usuń z głosowania')->call('exclude', $this->adam->id);

    expect(FanMvp::winners($this->game)->pluck('player.id')->all())->toBe([$this->bartek->id])
        ->and(FanMvp::winsFor($this->adam))->toBeEmpty()
        ->and(FanMvp::winsFor($this->bartek))->toHaveCount(1);
});

test('removing a player during the voting gives the votes back to fans', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
    FanMvp::vote($this->fan, $this->game, $this->adam->id);

    Livewire::actingAs($admin)->test(FanMvpPanel::class, ['type' => 'game', 'id' => $this->game->id])->call('exclude', $this->adam->id);

    expect(FanMvpVote::count())->toBe(0)
        ->and(FanMvp::vote($this->fan, $this->game, $this->adam->id))->not->toBeNull()
        ->and(FanMvp::vote($this->fan, $this->game, $this->bartek->id))->toBeNull();
});

test('the module can be switched off globally and for a single match', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);

    Livewire::actingAs($admin)->test(FanMvpPanel::class, ['type' => 'game', 'id' => $this->game->id])->call('toggleDisabled');
    expect(FanMvp::state($this->game))->toBe('off');
    $this->actingAs($this->fan)->get(route('games.show', $this->game))->assertOk()->assertDontSee('kibicowskie-mvp', false);

    Livewire::actingAs($admin)->test(FanMvpPanel::class, ['type' => 'game', 'id' => $this->game->id])->call('toggleDisabled');
    expect(FanMvp::state($this->game))->toBe('open');

    app(SiteSettings::class)->set(['fan_mvp_enabled' => false]);
    expect(FanMvp::state($this->game))->toBe('off');
    $this->actingAs($admin)->get(route('games.show', $this->game))->assertOk()->assertDontSee('kibicowskie-mvp', false);
});

test('the home page shows the voting banner only to logged in users', function () {
    $this->get(route('home'))->assertOk()->assertDontSee('Zagłosuj');
    $this->actingAs($this->fan)->get(route('home'))->assertOk()->assertSee('Zagłosuj');
});

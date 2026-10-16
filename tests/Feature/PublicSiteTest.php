<?php

use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\UserRole;

beforeEach(function () {
    $this->seed();
});

test('public pages load', function (string $url) {
    $this->get($url)->assertOk();
})->with(['/', '/teams', '/news', '/schedule', '/standings', '/contact', '/zdjecia', '/dokumenty', '/szukaj', '/strona/faq', '/strona/o-klubie']);

test('team page lists players and staff with roles, each linking to a profile', function () {
    $team = Team::firstWhere('slug', 'seniorzy');
    $player = $team->seasonRoster()->first();
    $physio = User::firstWhere('name', 'Agnieszka Dąbrowska');

    $this->get(route('teams.show', $team))
        ->assertOk()
        ->assertSee(route('players.show', $player))
        ->assertSee('Trener główny')
        ->assertSee('Fizjoterapeutka')
        ->assertSee(route('trainers.show', $physio));
});

test('player and staff profiles load', function () {
    $this->get(route('players.show', Player::first()))->assertOk();
    $this->get(route('trainers.show', User::firstWhere('name', 'Agnieszka Dąbrowska')))->assertOk()->assertSee('Fizjoterapeutka');
});

test('users outside the staff have no public profile', function () {
    $fan = User::where('role', UserRole::Fan)->first();

    $this->get(route('trainers.show', $fan))->assertNotFound();
});

test('seeded admin is marcin.molak@gmail.com', function () {
    expect(User::firstWhere('email', 'marcin.molak@gmail.com')?->role)->toBe(UserRole::Admin);
});

test('joker:admin promotes an existing user', function () {
    $fan = User::where('role', UserRole::Fan)->first();

    $this->artisan('joker:admin', ['email' => $fan->email])->assertSuccessful();

    expect($fan->fresh()->role)->toBe(UserRole::Admin);
});

test('search finds people without Polish characters', function () {
    $this->get(route('search', ['q' => 'wisniewski']))
        ->assertOk()
        ->assertSee('Tomasz Wiśniewski');
});

test('search index covers every kind of content', function () {
    $types = collect($this->getJson(route('search.data'))->assertOk()->json())->pluck('type')->unique();

    expect($types->all())->toContain('Strona', 'Drużyna', 'Zawodnik', 'Sztab', 'Aktualność', 'Mecz', 'Dokument');
});

test('search index refreshes after content changes', function () {
    $this->getJson(route('search.data'));

    Team::first()->update(['name' => 'Drużyna Testowa XYZ']);

    expect(collect($this->getJson(route('search.data'))->json())->pluck('title'))->toContain('Drużyna Testowa XYZ');
});

test('upcoming match can be added to a phone calendar', function () {
    $game = Game::upcoming()->first();

    $this->get(route('games.calendar', $game))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->assertSee('BEGIN:VEVENT', false);
});

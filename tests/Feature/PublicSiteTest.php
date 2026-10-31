<?php

use App\Models\Game;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed();
});

test('public pages load', function (string $url) {
    $this->get($url)->assertOk();
})->with(['/', '/teams', '/news', '/matches', '/standings', '/contact', '/photo-credits', '/documents', '/search', '/page/faq', '/page/o-klubie']);

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
    $fan = User::withRole(UserRole::Fan)->first();

    $this->get(route('trainers.show', $fan))->assertNotFound();
});

test('seeded admin is marcin.molak@gmail.com', function () {
    expect(User::firstWhere('email', 'marcin.molak@gmail.com')?->isAdmin())->toBeTrue();
});

test('joker:admin promotes an existing user', function () {
    $fan = User::withRole(UserRole::Fan)->first();

    $this->artisan('joker:admin', ['email' => $fan->email])->assertSuccessful();

    expect($fan->fresh()->roles()->all())->toBe([UserRole::Admin, UserRole::Fan]);
});

test('search finds people without Polish characters', function () {
    $this->get(route('search', ['q' => 'wisniewski']))
        ->assertOk()
        ->assertSee('Tomasz Wiśniewski');
});

test('search index covers every kind of content', function () {
    Game::factory()->create(['team_id' => Team::firstWhere('slug', 'seniorzy')->id, 'season_id' => Season::current()->value('id')]);

    $types = collect($this->getJson(route('search.data'))->assertOk()->json())->pluck('type')->unique();

    expect($types->all())->toContain('Strona', 'Drużyna', 'Zawodnik', 'Sztab', 'Aktualność', 'Mecz', 'Dokument');
});

test('search index refreshes after content changes', function () {
    $this->getJson(route('search.data'));

    Team::first()->update(['name' => 'Drużyna Testowa XYZ']);

    expect(collect($this->getJson(route('search.data'))->json())->pluck('title'))->toContain('Drużyna Testowa XYZ');
});

test('upcoming match can be added to a phone calendar', function () {
    $game = Game::factory()->create([
        'team_id' => Team::firstWhere('slug', 'seniorzy')->id,
        'season_id' => Season::current()->value('id'),
        'game_date' => now()->addWeek(),
        'status' => 'scheduled',
    ]);

    $this->get(route('games.calendar', $game))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->assertSee('BEGIN:VEVENT', false);
});

test('uploaded images are served even when the public/storage link is missing', function () {
    Storage::fake('public');
    Storage::disk('public')->put('sponsors/logo.png', 'obrazek');

    $this->get('/storage/sponsors/logo.png')->assertOk();
});

test('the admin panel lives under /panel and the old /admin address redirects there', function () {
    $admin = User::firstWhere('email', 'marcin.molak@gmail.com');

    // Konto z seedera nie ma jeszcze zgody RODO: pierwsze wejście prowadzi do zgody.
    $this->actingAs($admin)->get('/panel')->assertRedirect(route('account.consent'));
    $this->post(route('account.consent.store'), ['privacy' => '1', 'policy' => '1'])->assertRedirect('/panel');
    $this->get('/panel')->assertOk();
    $this->get('/admin')->assertRedirect('/panel');
    $this->get('/admin/teams')->assertRedirect('/panel/teams');
});

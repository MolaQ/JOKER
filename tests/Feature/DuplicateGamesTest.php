<?php

use App\Livewire\Admin\DuplicateGameReview;
use App\Models\Game;
use App\Models\RivalTeam;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Support\Approvals;
use App\Support\DuplicateGames;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
    $this->team = Team::factory()->create(['name' => 'Juniorzy II']);
    $this->season = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
});

function dubelGame(array $attributes): Game
{
    return Game::factory()->create(array_merge(['team_id' => test()->team->id, 'season_id' => test()->season->id, 'opponent' => 'Volley Wieleń', 'opponent_team_id' => null, 'status' => 'scheduled'], $attributes));
}

test('two games of one team with the same rival on one day are a possible duplicate', function () {
    $a = dubelGame(['game_date' => '2026-10-28 17:00', 'status' => 'finished', 'home_score' => 3, 'away_score' => 0]);
    $b = dubelGame(['game_date' => '2026-10-28 18:00', 'is_home' => false, 'external_id' => 'pzps-99']);
    dubelGame(['game_date' => '2026-10-28 17:00', 'opponent' => 'Inny klub']);
    dubelGame(['game_date' => '2026-10-29 17:00']);

    expect(DuplicateGames::groups()->keys()->all())->toBe([DuplicateGames::key([$a->id, $b->id])])
        ->and(Approvals::sectionsFor($this->admin)['duble']['count'])->toBe(1)
        ->and(Approvals::pendingCountFor($this->admin))->toBeGreaterThanOrEqual(1);
});

test('a rival from the database matches a game with only the typed name', function () {
    $rival = RivalTeam::factory()->create(['full_name' => 'Volley kost-pol.pl Wieleń', 'short_name' => 'Volley Wieleń']);
    dubelGame(['game_date' => '2026-10-28 17:00', 'opponent_team_id' => $rival->id]);
    dubelGame(['game_date' => '2026-10-28 18:00', 'opponent' => 'volley wielen']);

    expect(DuplicateGames::count())->toBe(1);
});

test('cancelled games are not duplicates', function () {
    dubelGame(['game_date' => '2026-10-28 17:00']);
    dubelGame(['game_date' => '2026-10-28 18:00', 'status' => 'cancelled']);

    expect(DuplicateGames::count())->toBe(0);
});

test('the extra game goes to the trash and "not a duplicate" hides the warning', function () {
    $a = dubelGame(['game_date' => '2026-10-28 17:00']);
    $b = dubelGame(['game_date' => '2026-10-28 18:00']);

    Livewire::test(DuplicateGameReview::class)
        ->assertSee('Możliwy dubel')
        ->call('delete', $b->id)
        ->assertDispatched('toast', message: 'Mecz trafił do kosza. Przywrócisz go w: System → Kosz.');

    expect(Game::find($b->id))->toBeNull()->and(DuplicateGames::count())->toBe(0);

    $c = dubelGame(['game_date' => '2026-10-28 19:00']);
    Livewire::test(DuplicateGameReview::class)->call('dismiss', DuplicateGames::key([$a->id, $c->id]));

    expect(DuplicateGames::count())->toBe(0);

    dubelGame(['game_date' => '2026-10-28 20:00']);
    expect(DuplicateGames::count())->toBe(1);
});

test('the games list marks a possible duplicate and the section page opens', function () {
    dubelGame(['game_date' => '2026-10-28 17:00']);
    dubelGame(['game_date' => '2026-10-28 18:00']);

    $this->get(route('admin.games.index'))->assertOk()->assertSee('możliwy dubel');
    $this->get(route('admin.duplicate-games.index'))->assertOk()->assertSee('Możliwe duble meczów')->assertSee('To nie dubel');
});

test('a coach sees only duplicates of own teams', function () {
    dubelGame(['game_date' => '2026-10-28 17:00']);
    dubelGame(['game_date' => '2026-10-28 18:00']);
    $coach = User::factory()->create();
    $own = Team::factory()->create(['trainer_id' => $coach->id]);

    expect(DuplicateGames::count($coach))->toBe(0);

    Game::factory()->count(2)->create(['team_id' => $own->id, 'season_id' => $this->season->id, 'opponent' => 'X', 'opponent_team_id' => null, 'status' => 'scheduled', 'game_date' => '2026-11-02 10:00']);
    expect(DuplicateGames::count($coach))->toBe(1);
});

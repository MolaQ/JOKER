<?php

use App\Livewire\StandingsBrowser;
use App\Models\Competition;
use App\Models\LeagueStanding;
use App\Models\Player;
use App\Models\RivalTeam;
use App\Models\Season;
use App\Models\Team;
use Livewire\Livewire;

beforeEach(function () {
    $this->season = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $this->team = Team::create(['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior']);
});

test('player pages have readable addresses and old ones redirect', function () {
    $player = Player::factory()->create(['first_name' => 'Łukasz', 'last_name' => 'Żółć', 'team_id' => $this->team->id]);

    expect(route('players.show', $player))->toEndWith("/zawodnicy/{$player->id}-lukasz-zolc");

    $this->get("/zawodnicy/{$player->id}-lukasz-zolc")->assertOk();
    $this->get("/zawodnicy/{$player->id}")->assertRedirect("/zawodnicy/{$player->id}-lukasz-zolc")->assertStatus(301);
    $this->get("/players/{$player->id}")->assertRedirect("/zawodnicy/{$player->id}")->assertStatus(301);
});

test('team pages use the slug and old english addresses redirect', function () {
    $this->get('/druzyny/seniorzy')->assertOk();
    $this->get("/druzyny/{$this->team->id}")->assertRedirect('/druzyny/seniorzy');
    $this->get('/teams/seniorzy')->assertRedirect('/druzyny/seniorzy');
    $this->get('/schedule?pokaz=wyniki')->assertRedirect('/mecze?pokaz=wyniki');
});

test('standings switch competitions without reloading and accept old links', function () {
    $league = Competition::factory()->create(['name' => 'III Liga Mężczyzn', 'season_id' => $this->season->id]);
    $youth = Competition::factory()->create(['name' => 'Młodzicy', 'season_id' => $this->season->id]);

    LeagueStanding::create([
        'competition_id' => $youth->id,
        'season_id' => $this->season->id,
        'competitor_type' => 'rival_team',
        'competitor_id' => RivalTeam::create(['name' => 'UKS Kłos', 'full_name' => 'UKS Kłos Wronki'])->id,
        'position' => 1, 'points' => 6,
    ]);

    Livewire::test(StandingsBrowser::class)
        ->call('select', $youth->getRouteKey())
        ->assertSet('competition', "{$youth->id}-mlodzicy")
        ->assertSee('UKS Kłos');

    $this->get("/tabele?rozgrywki={$league->getRouteKey()}")->assertOk()->assertSee('III Liga Mężczyzn');
    $this->get("/standings?competition={$youth->id}")->assertRedirect("/tabele?competition={$youth->id}");
});

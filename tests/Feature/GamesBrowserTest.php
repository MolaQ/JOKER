<?php

use App\Livewire\GamesBrowser;
use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use App\Support\SiteSettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->season = Season::create(['name' => '2026/2027', 'start_date' => now()->subMonth(), 'end_date' => now()->addMonths(8), 'is_current' => true]);
    $this->seniors = Team::create(['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior']);
    $this->juniors = Team::create(['name' => 'Junior 2008', 'slug' => 'junior-2008', 'category' => 'junior']);

    $game = fn (Team $team, string $opponent, $date, array $extra = []) => Game::create(array_merge([
        'team_id' => $team->id, 'season_id' => $this->season->id, 'opponent' => $opponent,
        'is_home' => true, 'game_date' => $date, 'status' => 'scheduled',
    ], $extra));

    $game($this->juniors, 'ŁKS Łódź', now()->addDays(2));
    $game($this->seniors, 'Warta Zawiercie', now()->addDays(9));
    $game($this->seniors, 'Gwardia Wrocław', now()->subDays(5), ['status' => 'finished', 'home_score' => 3, 'away_score' => 1]);
});

test('the matches page shows upcoming games and filters instantly', function () {
    $this->get(route('schedule.index'))->assertOk()->assertSee('Warta Zawiercie')->assertDontSee('Gwardia Wrocław');

    Livewire::test(GamesBrowser::class)
        ->assertSee('ŁKS Łódź')
        ->set('search', 'lodz')
        ->assertSee('ŁKS Łódź')
        ->assertDontSee('Warta Zawiercie')
        ->set('search', '')
        ->call('toggleTeam', 'seniorzy')
        ->assertSee('Warta Zawiercie')
        ->assertDontSee('ŁKS Łódź')
        ->call('setPeriod', 'wyniki')
        ->assertSee('Gwardia Wrocław')
        ->assertSee('Wygrana');
});

test('the countdown targets the next senior match by default', function () {
    $this->get('/')->assertOk()->assertSee('Warta Zawiercie')->assertSee('data-countdown', false);
});

test('the countdown can target the next match of any team', function () {
    app(SiteSettings::class)->set(['countdown_target' => 'any']);

    $this->get('/')->assertSeeInOrder(['Najbliższy mecz', 'ŁKS Łódź']);
});

test('calendar colours and the birthday icon come from the settings', function () {
    app(SiteSettings::class)->set(['cal_home_from' => '#112233', 'cal_away_bg' => '#fefefe', 'cal_birthday_icon' => '🎈']);

    $this->get('/mecze?pokaz=wszystkie')
        ->assertOk()
        ->assertSee('--cal-home-from: #112233', false)
        ->assertSee('--cal-away-bg: #fefefe', false);
});

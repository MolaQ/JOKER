<?php

use App\Livewire\GamesBrowser;
use App\Models\Game;
use App\Models\MiniGroup;
use App\Models\MiniPlayer;
use App\Models\MiniTrip;
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
    $this->get(route('schedule.index', ['show' => 'upcoming']))->assertOk()->assertSee('Warta Zawiercie')->assertDontSee('Gwardia Wrocław');

    Livewire::test(GamesBrowser::class)
        ->call('setPeriod', 'upcoming')
        ->assertSee('ŁKS Łódź')
        ->set('search', 'lodz')
        ->assertSee('ŁKS Łódź')
        ->assertDontSee('Warta Zawiercie')
        ->set('search', '')
        ->call('toggleTeam', 'seniorzy')
        ->assertSee('Warta Zawiercie')
        ->assertDontSee('ŁKS Łódź')
        ->call('setPeriod', 'results')
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
    app(SiteSettings::class)->set(['cal_home_bg' => '#112233', 'cal_away_bg' => '#fefefe', 'cal_birthday_icon' => '🎈']);

    $this->get('/matches?show=all')
        ->assertOk()
        ->assertSee('--cal-home-bg: #112233', false)
        ->assertSee('--cal-away-bg: #fefefe', false);
});

test('the calendar shows all matches by default', function () {
    $this->get(route('schedule.index'))->assertOk()->assertSee('Warta Zawiercie')->assertSee('Gwardia Wrocław');
});

test('minivolleyball tournaments show in the calendar with category names, places and lineups', function () {
    $group = MiniGroup::create(['name' => 'Czwórki dziewcząt', 'players_on_court' => 4]);
    $child = MiniPlayer::create(['mini_group_id' => $group->id, 'first_name' => 'Zosia', 'last_name' => 'Nowak', 'is_active' => true]);
    $home = MiniTrip::create(['title' => 'Faza powiatowa', 'starts_on' => now()->addWeek()->toDateString(), 'city' => 'Piła']);
    $home->teams()->create(['mini_group_id' => $group->id, 'name' => 'Joker 1', 'place' => 1, 'teams_count' => 10])->players()->attach($child);
    MiniTrip::create(['title' => 'Turniej w Wałczu', 'starts_on' => now()->addWeeks(2)->toDateString(), 'city' => 'Wałcz']);
    app(SiteSettings::class)->set(['mini_format_4' => 'Quattro']);

    Livewire::test(GamesBrowser::class)
        ->assertSee('Faza powiatowa')
        ->assertSee('u siebie · Piła')
        ->assertSee('Quattro')
        ->assertSee('Zosia Nowak')
        ->set('place', 'away')
        ->assertDontSee('Faza powiatowa')
        ->assertSee('Turniej w Wałczu')
        ->set('place', '')
        ->set('competition', GamesBrowser::FILTER_MINI)
        ->assertSee('Faza powiatowa');

    app(SiteSettings::class)->set(['mini_in_calendar' => false]);
    Livewire::test(GamesBrowser::class)->assertDontSee('Faza powiatowa');
});

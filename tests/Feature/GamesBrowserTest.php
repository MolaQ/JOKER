<?php

use App\Livewire\GamesBrowser;
use App\Models\Competition;
use App\Models\Game;
use App\Models\LeagueStanding;
use App\Models\MiniGroup;
use App\Models\MiniTrip;
use App\Models\Player;
use App\Models\RivalTeam;
use App\Models\Season;
use App\Models\Team;
use App\Models\Tournament;
use App\Support\SiteSettings;
use Livewire\Livewire;

beforeEach(function () {
    // Środek miesiąca: kalendarz domyślnie pokazuje bieżący miesiąc, a mecze testowe są ±9 dni od dziś.
    $this->travelTo(now()->startOfMonth()->addDays(14)->setTime(12, 0));
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

test('the calendar shows the current month by default and switches months with buttons', function () {
    $nextMonth = now()->addMonthNoOverflow()->startOfMonth()->addDays(3);
    Game::create(['team_id' => $this->seniors->id, 'season_id' => $this->season->id, 'opponent' => 'Daleki Rywal', 'is_home' => false, 'game_date' => $nextMonth, 'status' => 'scheduled']);

    Livewire::test(GamesBrowser::class)
        ->assertSet('month', '')
        ->assertSee('Warta Zawiercie')
        ->assertDontSee('Daleki Rywal')
        ->assertSeeHtml('wire:click="setMonth(\''.$nextMonth->format('Y-m').'\')"')
        ->call('setMonth', $nextMonth->format('Y-m'))
        ->assertSee('Daleki Rywal')
        ->assertDontSee('Warta Zawiercie')
        ->call('setMonth', 'all')
        ->assertSee('Daleki Rywal')
        ->assertSee('Warta Zawiercie')
        ->call('setMonth', $nextMonth->format('Y-m'))
        ->set('search', 'warta')
        ->assertSet('month', 'all')
        ->assertSee('Warta Zawiercie');
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
    $child = Player::create(['mini_group_id' => $group->id, 'first_name' => 'Zosia', 'last_name' => 'Nowak', 'is_active' => true]);
    $home = MiniTrip::create(['title' => 'Faza powiatowa', 'starts_on' => now()->addWeek()->toDateString(), 'city' => 'Piła']);
    $home->teams()->create(['mini_group_id' => $group->id, 'name' => 'Joker 1', 'place' => 1, 'teams_count' => 10])->players()->attach($child);
    MiniTrip::create(['title' => 'Turniej w Wałczu', 'starts_on' => now()->addWeeks(2)->toDateString(), 'city' => 'Wałcz']);

    Livewire::test(GamesBrowser::class)
        ->assertSee('Faza powiatowa')
        ->assertSee('Piła')
        ->assertSee('Czwórki dziewcząt | 10 drużyn')
        ->assertSee('Zosia Nowak')
        ->set('place', 'away')
        ->assertDontSee('Faza powiatowa')
        ->assertSee('Turniej w Wałczu')
        ->set('place', '')
        ->set('competition', GamesBrowser::FILTER_MINI)
        ->assertSee('Faza powiatowa');

    // Wyszukiwarka szuka po całej treści kafelka: grupa, dziecko, miejsce.
    foreach (['czworki', 'Zosia', '1. miejsce'] as $phrase) {
        Livewire::test(GamesBrowser::class)->set('search', $phrase)->assertSee('Faza powiatowa')->assertDontSee('Turniej w Wałczu');
    }

    app(SiteSettings::class)->set(['mini_in_calendar' => false]);
    Livewire::test(GamesBrowser::class)->assertDontSee('Faza powiatowa');
});

test('search finds tournaments by any team or category and games by result words', function () {
    $tournament = Tournament::factory()->create(['name' => 'Memoriał Winklera', 'edition' => null, 'season_id' => $this->season->id, 'start_date' => now()->addDays(4), 'end_date' => now()->addDays(5)]);
    $category = $tournament->categories()->create(['name' => 'Kadetki']);
    $rival = RivalTeam::factory()->create(['full_name' => 'Chemik Police']);
    $category->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => $rival->id]);

    Livewire::test(GamesBrowser::class)->call('setPeriod', 'upcoming')
        ->set('search', 'chemik')->assertSee('Memoriał Winklera')->assertDontSee('Warta Zawiercie')
        ->set('search', 'kadetki')->assertSee('Memoriał Winklera');

    Livewire::test(GamesBrowser::class)->call('setPeriod', 'results')
        ->set('search', 'wygrana')->assertSee('Gwardia Wrocław');
});

test('our places in the tables show above the calendar and a chosen team gets its table beside it', function () {
    $league = Competition::create(['name' => 'III Liga Mężczyzn', 'season_id' => $this->season->id]);
    $rival = RivalTeam::create(['full_name' => 'Sparta Złotów', 'short_name' => 'Sparta']);
    LeagueStanding::create(['competition_id' => $league->id, 'season_id' => $this->season->id, 'competitor_type' => 'rival_team', 'competitor_id' => $rival->id, 'position' => 1, 'played' => 4, 'points' => 12]);
    LeagueStanding::create(['competition_id' => $league->id, 'season_id' => $this->season->id, 'competitor_type' => 'team', 'competitor_id' => $this->seniors->id, 'position' => 2, 'played' => 4, 'points' => 9]);

    Livewire::test(GamesBrowser::class)
        ->assertSee('Nasze miejsca w tabelach')
        ->assertSee('z 2 drużyn')
        ->assertDontSee('Pełna tabela')
        ->call('toggleTeam', 'seniorzy')
        ->assertSee('2. miejsce')
        ->assertSee('Sparta')
        ->assertSee('Pełna tabela')
        ->assertSeeHtml('tab=tables&amp;league='.$league->getRouteKey());

    $this->get('/matches')->assertOk()->assertSee('Terminarz i wyniki')->assertSee('Tabele');
});

test('minivolleyball sits next to the teams and narrows to one group', function () {
    $fours = MiniGroup::firstOrCreate(['name' => 'Czwórki'], ['players_on_court' => 4]);
    $twos = MiniGroup::firstOrCreate(['name' => 'Dwójki'], ['players_on_court' => 2]);
    MiniTrip::create(['title' => 'Turniej czwórek', 'starts_on' => now()->addDays(3)->toDateString(), 'city' => 'Piła'])->teams()->create(['mini_group_id' => $fours->id, 'name' => 'Joker 1']);
    MiniTrip::create(['title' => 'Turniej dwójek', 'starts_on' => now()->addDays(4)->toDateString(), 'city' => 'Wałcz'])->teams()->create(['mini_group_id' => $twos->id, 'name' => 'Joker 2']);

    Livewire::test(GamesBrowser::class)
        ->call('toggleMini')
        ->assertSet('competition', GamesBrowser::FILTER_MINI)
        ->assertSee(['Turniej czwórek', 'Turniej dwójek', 'Wszystkie grupy'])
        ->assertDontSee('Warta Zawiercie')
        ->call('setMiniGroup', (string) $twos->id)
        ->assertSee('Turniej dwójek')
        ->assertDontSee('Turniej czwórek')
        // Drużyna zdejmuje filtr minisiatkówki i grupy.
        ->call('toggleTeam', 'seniorzy')
        ->assertSet('competition', '')
        ->assertSet('miniGroup', '')
        ->assertSee('Warta Zawiercie')
        ->assertDontSee('Turniej dwójek');
});

test('a tournament run on the site shows our matches in the calendar instead of the tournament tile', function () {
    $tournament = Tournament::create(['name' => 'Memoriał', 'start_date' => now()->addDays(3)->toDateString(), 'end_date' => now()->addDays(3)->toDateString(), 'city' => 'Piła', 'is_organizer' => true, 'has_page' => true, 'season_id' => $this->season->id]);
    $tournament->teams()->attach($this->juniors->id);
    $category = $tournament->categories()->create(['name' => 'Juniorzy']);
    $ours = $category->participants()->create(['competitor_type' => 'team', 'competitor_id' => $this->juniors->id]);
    $rival = $category->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => 'MKS Kalisz'])->id]);
    $category->matches()->create(['stage' => 'group', 'home_participant_id' => $ours->id, 'away_participant_id' => $rival->id, 'starts_at' => now()->addDays(3)->setTime(9, 45)]);

    Livewire::test(GamesBrowser::class)
        ->assertSeeHtml('MKS Kalisz')
        ->assertSeeHtml('09:45')
        ->assertDontSeeHtml('title="Memoriał')
        ->call('toggleTeam', 'seniorzy')
        ->assertDontSeeHtml('MKS Kalisz');
});

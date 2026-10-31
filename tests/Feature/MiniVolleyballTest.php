<?php

use App\Livewire\Admin\MiniVolleyballManager;
use App\Models\MiniGroup;
use App\Models\MiniPlayer;
use App\Models\MiniTrip;
use App\Models\MiniTripTeam;
use App\Models\User;
use App\Support\SiteSettings;
use Livewire\Livewire;

test('the migration prepares four hidden groups', function () {
    expect(MiniGroup::orderBy('display_order')->pluck('name')->all())->toBe(['Czwórki', 'Trójki', 'Dwójki', 'Singielki'])
        ->and(MiniGroup::where('is_active', true)->exists())->toBeFalse();
    $this->get('/')->assertOk()->assertDontSee('Wszystko o minisiatkówce');
});

test('a trainer sets up groups, children and a trip with a result that shows on the site', function () {
    MiniGroup::query()->delete();
    $this->actingAs(User::factory()->trainer()->create());

    $manager = Livewire::test(MiniVolleyballManager::class)
        ->set('section', 'grupy')
        ->set('group.name', 'Czwórki')->set('group.players_on_court', '4')->set('group.age_label', 'U-13')
        ->set('group.birth_year_from', '2013')->set('group.birth_year_to', '2014')->set('group.rules', 'Boisko 7×7 m, siatka 2,15 m.')
        ->call('saveGroup')->assertHasNoErrors()
        ->set('group.name', 'Trójki')->set('group.players_on_court', '3')->call('saveGroup');

    $fours = MiniGroup::firstWhere('slug', 'czworki');
    $threes = MiniGroup::firstWhere('slug', 'trojki');
    expect($fours->ageLine())->toBe('U-13 · rocznik 2013–2014');

    $manager->set('section', 'dzieci')
        ->set('player.first_name', 'Antek')->set('player.last_name', 'Kowalski')->set('player.birth_year', '2013')->set('player.group', (string) $fours->id)->set('player.image_consent', true)->call('savePlayer')->assertHasNoErrors()
        ->set('player.first_name', 'Kuba')->set('player.last_name', 'Młodszy')->set('player.birth_year', '2015')->set('player.group', (string) $threes->id)->call('savePlayer');

    $antek = MiniPlayer::firstWhere('first_name', 'Antek');
    $kuba = MiniPlayer::firstWhere('first_name', 'Kuba');

    $manager->set('section', 'wyjazdy')->call('newTrip')
        ->set('trip.title', 'Mistrzostwa Wielkopolski')->set('trip.starts_on', now()->subDay()->toDateString())->set('trip.city', 'Leszno')
        ->call('saveTrip')->assertHasNoErrors()
        ->set('newTeam.group', (string) $fours->id)->set('newTeam.name', 'Joker 1')->call('addTeam');

    $team = MiniTripTeam::sole();
    // Młodsze dziecko z trójek gra w czwórkach.
    $manager->call('togglePlayer', $team->id, $antek->id)->call('togglePlayer', $team->id, $kuba->id)
        ->set("results.{$team->id}.place", '1')->set("results.{$team->id}.teams_count", '18');

    expect($team->fresh()->resultLabel())->toBe('1. miejsce na 18 drużyn')
        ->and($team->players()->pluck('first_name')->sort()->values()->all())->toBe(['Antek', 'Kuba'])
        ->and($fours->medals())->toHaveCount(1);

    $this->get(route('mini.show', $fours))->assertOk()
        ->assertSee('Antek Kowalski')->assertSee('1. miejsce na 18 drużyn')->assertSee('Boisko 7×7 m')->assertSee('Mistrzostwa Wielkopolski');
    $this->get(route('mini.index'))->assertOk()->assertSee('Czwórki')->assertSee('Trójki');
    $this->get('/')->assertOk()->assertSee('Minisiatkówka')->assertSee('Ostatnio: 1. miejsce, Leszno');
});

test('child photos need parental consent and trip details are only for parents and staff', function () {
    MiniGroup::query()->delete();
    $group = MiniGroup::create(['name' => 'Dwójki', 'players_on_court' => 2]);
    MiniPlayer::create(['mini_group_id' => $group->id, 'first_name' => 'Ola', 'last_name' => 'Nowak', 'photo_path' => 'mini/ola.jpg', 'image_consent' => false]);
    $trip = MiniTrip::create(['title' => 'Turniej w Pile', 'starts_on' => now()->addWeek(), 'parent_info' => 'Zbiórka 7:00 pod halą']);
    $trip->teams()->create(['mini_group_id' => $group->id, 'name' => 'Joker 1']);

    expect(MiniPlayer::sole()->photoUrl())->toBeNull();

    $this->get(route('mini.show', $group))->assertOk()->assertSee('Turniej w Pile')->assertDontSee('Zbiórka 7:00');
    $this->actingAs(User::factory()->create())->get(route('mini.show', $group))->assertDontSee('Zbiórka 7:00');
    $this->actingAs(User::factory()->parent()->create())->get(route('mini.show', $group))->assertSee('Zbiórka 7:00');
});

test('only roles with the mini volleyball permission open the panel', function () {
    $this->actingAs(User::factory()->trainer()->create())->get('/panel/minisiatkowka')->assertOk();
    $this->actingAs(User::factory()->editor()->create())->get('/panel/minisiatkowka')->assertForbidden();
});

test('the whole mini volleyball module and each part of it can be switched off in the settings', function () {
    MiniGroup::query()->delete();
    $group = MiniGroup::create(['name' => 'Czwórki', 'players_on_court' => 4, 'rules' => 'Siatka 2,15 m']);
    MiniPlayer::create(['mini_group_id' => $group->id, 'first_name' => 'Antek', 'last_name' => 'Kowalski', 'birth_year' => 2013]);
    $settings = app(SiteSettings::class);

    $settings->set(['mini_child_names' => 'initial', 'mini_show_birth_year' => false, 'mini_show_rules' => false, 'mini_title' => 'Mini Joker']);
    $this->get(route('mini.show', $group))->assertOk()->assertSee('Antek K.')->assertDontSee('Kowalski')->assertDontSee('2013')->assertDontSee('Siatka 2,15 m')->assertSee('Mini Joker');

    $settings->set(['module_mini' => false]);
    $this->get(route('mini.index'))->assertNotFound();
    $this->get(route('mini.show', $group))->assertNotFound();
    $this->get('/')->assertOk()->assertDontSee('Wszystko o minisiatkówce');
    $this->actingAs(User::factory()->trainer()->create())->get('/panel/minisiatkowka')->assertNotFound();
});

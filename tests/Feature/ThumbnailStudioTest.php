<?php

use App\Livewire\Admin\ThumbnailStudio;
use App\Models\Game;
use App\Models\RivalTeam;
use App\Models\Season;
use App\Models\Team;
use App\Models\ThumbnailTemplate;
use App\Models\User;
use App\Support\ThumbnailData;
use App\UserRole;
use Livewire\Livewire;

test('the thumbnails page needs a login and the graphics permission', function () {
    $this->get('/miniatury')->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create(['role' => UserRole::Fan]))->get('/miniatury')->assertForbidden();
    $this->actingAs(User::factory()->create(['role' => UserRole::Photographer]))->get('/miniatury')->assertOk()->assertSee('Miniatury transmisji');
    $this->get('/robots.txt')->assertSee('Disallow: /miniatury');
});

test('a game fills both graphics and the rival colour is remembered', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Photographer]));
    $rival = RivalTeam::create(['full_name' => 'Fort Tarnowo']);
    $game = Game::factory()->create([
        'season_id' => Season::factory()->create()->id,
        'team_id' => Team::factory()->create()->id,
        'opponent' => 'Fort Tarnowo',
        'opponent_team_id' => $rival->id,
        'is_home' => true,
        'game_date' => '2026-11-15 12:00',
        'venue' => 'Hala POLA',
        'status' => 'finished',
        'home_score' => 3,
        'away_score' => 1,
        'sets_score' => [[25, 23], [21, 25], [25, 18], [25, 20]],
        'mvp' => 'MVP: Adam Nowak',
    ]);

    Livewire::test(ThumbnailStudio::class)
        ->set('pick', 'game:'.$game->id)
        ->assertSet('m.away.name', 'Fort')
        ->assertSet('m.away.sub', 'Tarnowo')
        ->assertSee('Hala POLA')
        ->assertSee('MVP: Adam Nowak')
        ->assertSee('Niedziela')
        ->set('m.away.color', '#123456');

    expect($rival->fresh()->brand_color)->toBe('#123456')
        ->and(ThumbnailData::fromGame($game->fresh())['away']['color'])->toBe('#123456');
});

test('templates are saved per user and only the author or an admin deletes them', function () {
    $author = User::factory()->create(['role' => UserRole::Photographer]);
    $this->actingAs($author);

    Livewire::test(ThumbnailStudio::class)
        ->set('templateId', null)
        ->set('templateName', 'Liga kadetów')
        ->set('s.accent', '#ff0000')
        ->set('s.font', 'serif')
        ->call('saveTemplate', true)
        ->assertHasNoErrors();

    $template = ThumbnailTemplate::where('name', 'Liga kadetów')->firstOrFail();
    expect($template->user_id)->toBe($author->id)
        ->and($template->resolvedSettings()['font'])->toBe('serif');

    $other = User::factory()->create(['role' => UserRole::Photographer]);
    expect($template->canBeChangedBy($other))->toBeFalse()
        ->and($template->canBeChangedBy($author))->toBeTrue()
        ->and($template->canBeChangedBy(User::factory()->admin()->create()))->toBeTrue();

    $this->actingAs($other);
    Livewire::test(ThumbnailStudio::class)->set('templateId', $template->id)->call('deleteTemplate')->assertForbidden();
    expect(ThumbnailTemplate::find($template->id))->not->toBeNull();
});

test('names split into two lines and the score comes from sets', function () {
    expect(ThumbnailData::splitName('LOMS Joker Piła (seniorzy)'))->toBe(['LOMS Joker Piła', 'seniorzy'])
        ->and(ThumbnailData::splitName('MKS Kalisz'))->toBe(['MKS', 'Kalisz'])
        ->and(ThumbnailData::score('', ThumbnailData::parseSets('25:20, 23:25 15:12')))->toBe([2, 1]);
});

test('the result is typed in score and set fields', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Photographer]));

    Livewire::test(ThumbnailStudio::class)
        ->set('setRows.0.h', '25')->set('setRows.0.a', '20')
        ->set('setRows.1.h', '23')->set('setRows.1.a', '25')
        ->set('setRows.2.h', '15')->set('setRows.2.a', '12')
        ->assertSet('m.sets', '25:20, 23:25, 15:12')
        ->assertSet('m.score', '')
        ->set('scoreHome', '2')->set('scoreAway', '1')
        ->assertSet('m.score', '2:1')
        ->call('swapSides')
        ->assertSet('scoreHome', '1')
        ->assertSet('setRows.0.h', '20');
});

test('the VS graphic and the title colours are configurable and saved in the template', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Photographer]));

    $component = Livewire::test(ThumbnailStudio::class)
        ->set('templateId', null)
        ->set('templateName', 'VS duże')
        ->set('s.layout', 'modern')
        ->set('s.vs_style', 'burst')
        ->set('s.vs_size', 140)
        ->call('nudgeVs', -20)
        ->call('nudgeVs', -20)
        ->set('s.preview_title', 'Wielki mecz')
        ->set('s.title_color', '#ffcc00')
        ->assertSet('s.vs_offset', -40)
        ->assertSee('Wielki')
        ->assertSee('--tc:#ffcc00', false)
        ->assertSee('--vss:1.4', false)
        ->assertSee('--vsy:-40px', false)
        ->assertSeeHtml('data-ytt-vs-art')
        ->set('s.vs_team_colors', false)
        ->set('s.vs_v2', '#00ff00')
        ->assertSee('--vv2:#00ff00', false)
        ->set('s.show_stage', false)
        ->set('m.stage', 'Grupa Z')
        ->assertDontSee('Grupa Z')
        ->set('s.show_stage', true)
        ->set('s.stage_text', 'Półfinał')
        ->assertSee('Półfinał')
        ->set('s.vs_team_colors', true)
        ->set('m.home.color', '#112233')
        ->assertSee('--vs2:#112233', false);

    $component->call('chooseTemplate', null)->assertSet('templateId', null)->assertSet('s.vs_size', 100);
    $component->set('s.vs_size', 140)->set('s.title_color', '#ffcc00')->set('s.stage_text', 'Półfinał')->set('s.vs_team_colors', true)->set('s.vs_offset', -40)->set('templateName', 'VS duże');
    $component->call('nudgeVs', -1000)->assertSet('s.vs_offset', -400)
        ->call('saveTemplate', true)->assertHasNoErrors();

    $settings = ThumbnailTemplate::where('name', 'VS duże')->firstOrFail()->resolvedSettings();
    expect($settings['vs_size'])->toBe(140)
        ->and($settings['vs_offset'])->toBe(-400)
        ->and($settings['title_color'])->toBe('#ffcc00')
        ->and($settings['stage_text'])->toBe('Półfinał')
        ->and($settings['vs_team_colors'])->toBeTrue();

    $component->set('s.vs_style', 'nieznany')->call('saveTemplate')->assertHasErrors('s.vs_style');
});

test('finished games stay on the list and the period filter narrows it', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Photographer]));
    $season = Season::factory()->create(['is_current' => true]);
    $team = Team::factory()->create();
    $past = Game::factory()->create(['season_id' => $season->id, 'team_id' => $team->id, 'opponent' => 'Dawny Rywal', 'game_date' => now()->subWeeks(3), 'status' => 'finished', 'home_score' => 3, 'away_score' => 0]);
    $future = Game::factory()->create(['season_id' => $season->id, 'team_id' => $team->id, 'opponent' => 'Przyszły Rywal', 'game_date' => now()->addWeek()]);

    Livewire::test(ThumbnailStudio::class)
        ->set('fSeason', (string) $season->id)
        ->assertSee('Dawny Rywal')->assertSee('Przyszły Rywal')
        ->set('fPeriod', 'results')
        ->assertSee('Dawny Rywal')->assertDontSee('Przyszły Rywal')
        ->set('fPeriod', 'upcoming')
        ->assertDontSee('Dawny Rywal')->assertSee('Przyszły Rywal');
});

test('the bars layout keeps the day and time in the bottom bar', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Photographer]));

    Livewire::test(ThumbnailStudio::class)
        ->set('s.layout', 'bars')
        ->set('m.date', '2026-10-28')
        ->set('m.time', '18:45')
        ->set('m.place', 'Hala LOMS')
        ->assertSee('Środa, 28 października 2026 · godz. 18:45 | Hala LOMS');
});

test('the game search ignores Polish letters and finds games by date', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Photographer]));
    $season = Season::factory()->create(['is_current' => true]);
    $team = Team::factory()->create();
    $rival = RivalTeam::create(['full_name' => 'Piątka Turek', 'short_name' => 'Piątka']);
    Game::factory()->create(['season_id' => $season->id, 'team_id' => $team->id, 'opponent' => 'Piątka Turek', 'opponent_team_id' => $rival->id, 'game_date' => '2026-11-15 17:00']);
    Game::factory()->create(['season_id' => $season->id, 'team_id' => $team->id, 'opponent' => 'Łódzki Klub', 'game_date' => '2026-11-05 18:00']);
    Game::factory()->create(['season_id' => $season->id, 'team_id' => $team->id, 'opponent' => 'Stary Rywal', 'game_date' => '2015-11-11 18:00']);

    $studio = Livewire::test(ThumbnailStudio::class)->set('fSeason', '');

    $studio->set('fSearch', 'piatka')->assertSee('Piątka Turek')->assertDontSee('Łódzki Klub');
    $studio->set('fSearch', 'lodzki')->assertSee('Łódzki Klub')->assertDontSee('Piątka Turek');
    $studio->set('fSearch', '15.11')->assertSee('Piątka Turek')->assertDontSee('Łódzki Klub')->assertDontSee('Stary Rywal');
    $studio->set('fSearch', '5.11.2026')->assertSee('Łódzki Klub')->assertDontSee('Piątka Turek');
    $studio->set('fSearch', '2026-11-15')->assertSee('Piątka Turek')->assertDontSee('Łódzki Klub');
    $studio->set('fSearch', '15 listopada')->assertSee('Piątka Turek')->assertDontSee('Łódzki Klub');
});

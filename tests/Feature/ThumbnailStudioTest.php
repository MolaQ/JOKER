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

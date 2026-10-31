<?php

use App\Models\MiniGroup;
use App\Models\MiniTrip;
use App\Support\ShareCards;
use App\Support\SiteSettings;

test('mini results get a share graphic without children names unless switched on', function () {
    $group = MiniGroup::firstWhere('slug', 'czworki');
    $player = $group->players()->create(['first_name' => 'Antek', 'last_name' => 'Kowalski']);
    $trip = MiniTrip::create(['title' => 'Turniej w Lesznie', 'city' => 'Leszno', 'starts_on' => now()->subDay()]);
    $team = $trip->teams()->create(['mini_group_id' => $group->id, 'name' => 'Joker 1', 'place' => 2, 'teams_count' => 12]);
    $team->players()->attach($player);

    $card = ShareCards::miniTrip($trip->load('teams.group', 'teams.players'));
    expect($card['rows'][0])->toMatchArray(['place' => 2, 'name' => 'Joker 1', 'group' => 'Czwórki | 12 drużyn', 'icons' => 4, 'players' => '']);

    app(SiteSettings::class)->set(['share_mini_names' => true]);
    expect(ShareCards::miniTrip($trip->fresh(['teams.group', 'teams.players']))['rows'][0]['players'])->toBe('Antek Kowalski');

    app(SiteSettings::class)->set(['share_mini' => false]);
    expect(ShareCards::miniTrip($trip->fresh(['teams.group', 'teams.players'])))->toBeNull();
});

test('the share button appears on the mini page and carries the theme from settings', function () {
    app(SiteSettings::class)->set(['share_accent' => '#ff0000', 'share_footer' => 'jokerpila.pl #JokerPiła']);
    $group = MiniGroup::firstWhere('slug', 'czworki');
    $group->update(['is_active' => true]);
    $trip = MiniTrip::create(['title' => 'Turniej w Lesznie', 'starts_on' => now()->subDay()]);
    $trip->teams()->create(['mini_group_id' => $group->id, 'name' => 'Joker 1', 'place' => 1]);

    $this->get(route('mini.show', $group))->assertOk()->assertSee('data-share-card', false)->assertSee('#ff0000')->assertSee('#JokerPiła');
});

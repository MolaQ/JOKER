<?php

use App\Models\Tournament;
use App\Models\TournamentMatch;

it('shows the live bar on the site while a tournament match is in progress', function () {
    $tournament = Tournament::create(['name' => 'Memoriał', 'edition' => 'XXV', 'start_date' => now()->toDateString(), 'has_page' => true]);
    $category = $tournament->categories()->create(['name' => 'Kadetki', 'display_order' => 0]);
    $match = $category->matches()->create(['stage' => TournamentMatch::STAGE_PLAYOFF, 'label' => 'Półfinał', 'starts_at' => now()]);

    $this->get(route('home'))->assertOk()->assertDontSee('data-live-bar', false);

    $match->update(['is_live' => true]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('data-live-bar', false)
        ->assertSee('Trwa 1 mecz')
        ->assertSee(route('tournaments.show', $tournament), false);

    $match->update(['is_live' => false]);

    $this->get(route('home'))->assertOk()->assertDontSee('data-live-bar', false);
});

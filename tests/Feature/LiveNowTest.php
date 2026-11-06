<?php

use App\Models\RivalTeam;
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
        ->assertSee('Do ustalenia')
        ->assertSee('0:0')
        ->assertSee(e(route('tournaments.show', ['tournament' => $tournament, 'tab' => 'matches'])), false);

    $match->update(['is_live' => false]);

    $this->get(route('home'))->assertOk()->assertDontSee('data-live-bar', false);
});

it('shows the live score and leads to the matches of that category, updating after every set', function () {
    $tournament = Tournament::create(['name' => 'Memoriał', 'edition' => 'XXV', 'start_date' => now()->toDateString(), 'has_page' => true]);
    $tournament->categories()->create(['name' => 'Kadetki', 'display_order' => 0]);
    $juniors = $tournament->categories()->create(['name' => 'Juniorki', 'display_order' => 1]);
    $team = fn (string $name) => $juniors->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => $name])->id]);
    $match = $juniors->matches()->create(['stage' => TournamentMatch::STAGE_GROUP, 'home_participant_id' => $team('Sparta Złotów')->id, 'away_participant_id' => $team('Orzeł Wałcz')->id, 'starts_at' => now(), 'court' => 'Sektor I']);

    $match->update(['is_live' => true]);
    $this->get(route('home'))->assertOk()
        ->assertSeeInOrder(['Sparta Złotów', '0:0', 'Orzeł Wałcz'])
        ->assertSee('Juniorki · Sektor I')
        ->assertSee(e(route('tournaments.show', ['tournament' => $tournament, 'category' => $juniors->slug(), 'tab' => 'matches'])), false);

    // Wpisany set widać od razu, bez czekania na odświeżenie paska.
    $match->update(['home_sets' => 1, 'away_sets' => 0]);
    $this->get(route('home'))->assertSeeInOrder(['Sparta Złotów', '1:0', 'Orzeł Wałcz']);

    // Na stronie turnieju paska nie ma.
    $this->get(route('tournaments.show', ['tournament' => $tournament, 'category' => $juniors->slug(), 'tab' => 'matches']))->assertOk()->assertDontSee('data-live-bar', false);
});

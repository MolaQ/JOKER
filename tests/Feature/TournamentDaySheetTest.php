<?php

use App\Models\RivalTeam;
use App\Models\Tournament;
use App\Models\User;

it('lists all matches of one tournament day from every category in time order', function () {
    $tournament = Tournament::create(['name' => 'Memoriał', 'start_date' => '2026-11-14', 'end_date' => '2026-11-15', 'has_page' => true]);
    $add = function (string $name, int $order) use ($tournament) {
        $category = $tournament->categories()->create(['name' => $name, 'display_order' => $order]);
        $team = fn (string $club) => $category->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => $club.' '.$name])->id]);

        return [$category, $team('Sparta'), $team('Orzeł')];
    };
    [$cadets, $a, $b] = $add('Kadetki', 0);
    [$juniors, $c, $d] = $add('Juniorki', 1);
    $cadets->matches()->create(['stage' => 'group', 'home_participant_id' => $a->id, 'away_participant_id' => $b->id, 'starts_at' => '2026-11-14 11:00', 'court' => 'Sektor I', 'home_sets' => 2, 'away_sets' => 1, 'sets_score' => [[25, 20], [20, 25], [15, 10]]]);
    $juniors->matches()->create(['stage' => 'group', 'home_participant_id' => $c->id, 'away_participant_id' => $d->id, 'starts_at' => '2026-11-14 10:00', 'court' => 'Sektor II']);
    $juniors->matches()->create(['stage' => 'group', 'home_participant_id' => $d->id, 'away_participant_id' => $c->id, 'starts_at' => '2026-11-15 09:00', 'court' => 'Sektor II']);

    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.tournaments.day-sheet', [$tournament, 'day' => '2026-11-14']))->assertOk()
        ->assertSeeInOrder(['10:00', 'Juniorki', 'Sparta Juniorki – Orzeł Juniorki', '11:00', 'Kadetki', 'Sparta Kadetki – Orzeł Kadetki', '2:1', '25:20, 20:25, 15:10'])
        ->assertSee('2 mecze')
        ->assertDontSee('Orzeł Juniorki – Sparta Juniorki');

    $this->get(route('admin.tournaments.day-sheet', [$tournament, 'day' => '2026-11-15']))->assertOk()
        ->assertSee('Orzeł Juniorki – Sparta Juniorki')->assertDontSee('Sparta Kadetki');

    $this->get(route('admin.tournaments.edit', $tournament).'?section=mecze')->assertOk()->assertSee('Mecze dnia (wszystkie kategorie)')->assertSee(route('admin.tournaments.day-sheet', $tournament), false);
});

it('is only for the club panel', function () {
    $tournament = Tournament::create(['name' => 'Memoriał', 'start_date' => '2026-11-14', 'has_page' => true]);

    $this->get(route('admin.tournaments.day-sheet', $tournament))->assertRedirect();
    $this->actingAs(User::factory()->create())->get(route('admin.tournaments.day-sheet', $tournament))->assertForbidden();
});

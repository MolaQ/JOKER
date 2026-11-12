<?php

use App\Models\Competition;
use App\Models\LeagueStanding;
use App\Models\Season;
use App\Models\Team;

test('our team in a league table is named as in the competition, not by its club name', function () {
    $team = Team::factory()->create(['name' => 'Seniorzy']);
    $season = Season::factory()->create();
    $competition = Competition::factory()->create(['season_id' => $season->id]);

    $imported = LeagueStanding::factory()->create(['competition_id' => $competition->id, 'season_id' => $season->id, 'competitor_type' => 'team', 'competitor_id' => $team->id, 'team_id' => $team->id, 'source_name' => 'LOMS Joker Piła']);
    expect($imported->competitionName())->toBe('LOMS Joker Piła');

    $manual = LeagueStanding::factory()->create(['competition_id' => $competition->id, 'season_id' => $season->id, 'competitor_type' => 'team', 'competitor_id' => $team->id, 'team_id' => $team->id, 'source_name' => null]);
    expect($manual->fresh()->competitionName())->not->toBe('Seniorzy');
});

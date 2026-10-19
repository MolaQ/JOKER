<?php

use App\Livewire\StandingsBrowser;
use App\Models\Competition;
use App\Models\Game;
use App\Models\LeagueStanding;
use App\Models\Season;
use App\Models\Team;
use App\Services\ScheduleImporter;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->season = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $this->seniors = Team::create(['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior']);
    $this->juniors = Team::create(['name' => 'Juniorzy', 'slug' => 'juniorzy', 'category' => 'junior']);
    $this->juniorsTwo = Team::create(['name' => 'Juniorzy II', 'slug' => 'juniorzy-ii', 'category' => 'junior']);

    Http::fake([
        'wielkopolskasiatkowka.com.pl/wp-json/*slug=ii-liga-mezczyzn-5*' => Http::response(file_get_contents(base_path('tests/Fixtures/ws-ii-liga.json')), 200, ['Content-Type' => 'application/json']),
        'wielkopolskasiatkowka.com.pl/wp-json/*slug=liga-juniorow-26-27*' => Http::response(json_encode([['content' => ['rendered' => file_get_contents(base_path('tests/Fixtures/ws-liga-juniorow.html'))]]]), 200, ['Content-Type' => 'application/json']),
        '*team_id=701' => Http::response(file_get_contents(base_path('tests/Fixtures/wzps-druzyna.html'))),
        '*match_id=522' => Http::response(file_get_contents(base_path('tests/Fixtures/wzps-mecz.html'))),
        '*' => Http::response(''),
    ]);
});

function secondLeague(Team $seniors): Competition
{
    $league = Competition::create([
        'name' => 'II Liga Mężczyzn',
        'season_id' => Season::current()->value('id'),
        'source_url' => "https://wielkopolskasiatkowka.com.pl/ii-liga-mezczyzn-5/\nhttps://wzps.poznan.pl/obsady/index.php?mode=8&club_id=7&team_id=701",
    ]);
    $league->teams()->attach($seniors->id, ['external_name' => 'LOMS Joker Piła']);

    return $league;
}

function juniorLeague(Team $juniors, Team $juniorsTwo): Competition
{
    $league = Competition::create([
        'name' => 'Liga Juniorów',
        'season_id' => Season::current()->value('id'),
        'source_url' => 'https://wielkopolskasiatkowka.com.pl/liga-juniorow-26-27/',
    ]);
    $league->teams()->attach([
        $juniors->id => ['external_name' => 'LOMS Joker Piła; LOMS Joker Piła 1'],
        $juniorsTwo->id => ['external_name' => 'LOMS Joker Piła II; LOMS Joker Piła 2'],
    ]);

    return $league;
}

test('results come from wielkopolskasiatkowka and are joined with WZPS times, venues and match numbers', function () {
    $league = secondLeague($this->seniors);

    app(ScheduleImporter::class)->sync($league);

    // Tylko mecze II ligi - mecze III ligi ze strony drużyny w WZPS nie trafiają do tych rozgrywek.
    expect(Game::where('competition_id', $league->id)->count())->toBe(3);

    $wolves = Game::firstWhere('opponent', 'LUKS Wilki Wilczyn');
    expect($wolves->external_id)->toBe('522')
        ->and($wolves->ourSetsResult())->toBe([3, 2])
        ->and($wolves->sets_score)->toBe([[25, 27], [22, 25], [25, 22], [25, 20], [16, 14]])
        ->and($wolves->result_source)->toBe('wielkopolskasiatkowka')
        ->and($wolves->game_date->format('Y-m-d H:i'))->toBe('2026-10-10 17:00')
        ->and($wolves->venue)->toContain('Hala Sportowa LOMS')
        ->and($wolves->round_label)->toBe('Grupa 1 · 2. kolejka');

    $poznan = Game::firstWhere('external_id', '518');
    expect($poznan->is_home)->toBeFalse()
        ->and($poznan->ourSetsResult())->toBe([3, 2]);

    // Mecz, którego nie ma w WZPS, dostaje klucz ze strony wielkopolskasiatkowka.
    $upcoming = Game::where('competition_id', $league->id)->where('external_id', 'like', 'ws-%')->sole();
    expect($upcoming->opponent)->toBe('Fort Tarnowo Podgórne')
        ->and($upcoming->status)->toBe('scheduled');

    $standings = LeagueStanding::where('competition_id', $league->id)->orderBy('position')->get();
    expect($standings)->toHaveCount(3)
        ->and($standings[1]->competitor_id)->toBe($this->seniors->id)
        ->and($standings[2]->competitorName())->toBe('Ogrobet Bejenka Budownictwo LUKS Wilki Wilczyn');
});

test('syncing twice keeps one game per match and a result entered in the panel wins', function () {
    $league = secondLeague($this->seniors);
    $importer = app(ScheduleImporter::class);
    $importer->sync($league);

    Game::firstWhere('external_id', '522')->update(['home_score' => 3, 'away_score' => 0, 'result_source' => 'manual']);

    $result = $importer->sync($league->fresh());

    expect($result['created'])->toBe(0)
        ->and(Game::where('competition_id', $league->id)->count())->toBe(3)
        ->and(Game::firstWhere('external_id', '522')->ourSetsResult())->toBe([3, 0]);
});

test('youth stages, groups, walkovers and postponed matches are read in order', function () {
    $league = juniorLeague($this->juniors, $this->juniorsTwo);

    app(ScheduleImporter::class)->sync($league);

    $standings = LeagueStanding::where('competition_id', $league->id)->get();
    expect($standings->map(fn (LeagueStanding $row) => $row->stage_name.' / '.$row->group_name)->unique()->values()->all())
        ->toBe(['Etap 2 / Grupa D', 'Etap 2 / Grupa E', 'Etap 1 / Grupa B'])
        ->and($standings->where('competitor_id', $this->juniorsTwo->id)->where('competitor_type', 'team')->pluck('position')->all())->toBe([1, 2])
        ->and($standings->firstWhere('group_name', 'Grupa E')->competitor_id)->toBe($this->juniors->id);

    $twoGames = Game::where('team_id', $this->juniorsTwo->id)->orderBy('game_date')->get();
    expect($twoGames)->toHaveCount(5)
        ->and($twoGames->first()->game_date->toDateString())->toBe('2026-09-27')
        ->and($twoGames->first()->result_note)->toBe('walkower')
        ->and($twoGames->first()->round_label)->toBe('Etap 1 · Grupa B · 1. kolejka');

    $postponed = $twoGames->firstWhere('opponent', 'KS VOLLEYSTARS');
    expect($postponed->game_date->toDateString())->toBe('2026-10-08')
        ->and($postponed->status)->toBe('scheduled');

    expect(Game::where('team_id', $this->juniors->id)->sole()->round_label)->toBe('Etap 2 · Grupa E · 1. kolejka');
});

test('a WZPS score fills in until wielkopolskasiatkowka publishes one', function () {
    $league = secondLeague($this->seniors);
    app(ScheduleImporter::class)->import($league, collect(), collect([[
        'nr' => '13', 'date' => '2026-10-17', 'home' => 'Fort Tarnowo Podgórne', 'away' => 'LOMS Joker Piła', 'note' => null,
        'home_score' => null, 'away_score' => null, 'sets' => [], 'score_known' => false,
        'stage' => null, 'group' => 'Grupa 1', 'round' => '3. kolejka', 'source_key' => 'nr13',
    ]]), collect([[
        'external_id' => '530', 'date' => '2026-10-17', 'time' => '18:00', 'home' => 'Fort Tarnowo Podgórne', 'away' => 'LOMS Joker Piła',
        'home_score' => 1, 'away_score' => 3, 'sets' => [[25, 20], [20, 25], [18, 25], [22, 25]], 'score_known' => true,
    ]]), recalculateStandings: false);

    $game = Game::where('competition_id', $league->id)->sole();
    expect($game->external_id)->toBe('530')
        ->and($game->game_date->format('H:i'))->toBe('18:00')
        ->and($game->ourSetsResult())->toBe([3, 1])
        ->and($game->result_source)->toBe('wzps');
});

test('names of the same rival from both sites are recognised', function (string $first, string $second, bool $same) {
    expect(ScheduleImporter::similarity($first, $second) > 0)->toBe($same);
})->with([
    ['LUKS Wilki Wilczyn', 'Ogrobet Bejenka Budownictwo LUKS Wilki Wilczyn', true],
    ['Trefl Gdańsk II', 'Energa Trefl Gdańsk II', true],
    ['GKS Stoczniowiec', 'Stoczniowiec Politechnika Gdańska', true],
    ['Fort Tarnowo Podgórne', 'KS Kórnik', false],
]);

test('the team page shows the road through stages and the standings page switches stages', function () {
    $league = juniorLeague($this->juniors, $this->juniorsTwo);
    app(ScheduleImporter::class)->sync($league);

    $this->get(route('teams.show', $this->juniorsTwo))
        ->assertOk()
        ->assertSeeInOrder(['Etap 1 · Grupa B', '2. miejsce', 'Etap 2 · Grupa D', '1. miejsce'])
        ->assertSeeText('Gra jako: LOMS Joker Piła II / LOMS Joker Piła 2');

    Livewire\Livewire::test(StandingsBrowser::class)
        ->assertSet('competition', '')
        ->assertSeeInOrder(['Etap 2', 'Etap 1', 'Grupa D', 'Grupa E'])
        ->call('selectStage', 'Etap 1')
        ->assertSee('Grupa B')
        ->assertDontSee('Grupa D');
});

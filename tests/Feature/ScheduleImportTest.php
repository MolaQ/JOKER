<?php

use App\Livewire\Admin\CompetitionManager;
use App\Livewire\Admin\GameManager;
use App\Livewire\GamesBrowser;
use App\Models\Competition;
use App\Models\Game;
use App\Models\LeagueStanding;
use App\Models\RivalTeam;
use App\Models\Season;
use App\Models\Team;
use App\Models\TrashItem;
use App\Models\User;
use App\Services\ScheduleImporter;
use App\UserRole;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $season = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $this->seniors = Team::create(['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior']);
    $this->cadets = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'kadet']);

    $this->competition = Competition::create([
        'points_win_3_0' => 3, 'points_win_3_1' => 3, 'points_win_3_2' => 2,
        'points_loss_2_3' => 1, 'points_loss_1_3' => 0, 'points_loss_0_3' => 0,
        'name' => 'III Liga Mężczyzn',
        'season_id' => $season->id,
        'source_url' => "https://wzps.poznan.pl/obsady/index.php?mode=12&type=11\nhttps://wzps.poznan.pl/obsady/index.php?mode=8&club_id=7&team_id=701",
    ]);

    // W III lidze kadeci grają jako „LOMS Joker Piła II”, a seniorzy jako „LOMS Joker Piła”.
    $this->competition->teams()->attach([
        $this->seniors->id => ['external_name' => 'LOMS Joker Piła'],
        $this->cadets->id => ['external_name' => 'LOMS Joker Piła II'],
    ]);

    Http::fake([
        'sedziowie.pzps.pl/*' => Http::response(file_get_contents(base_path('tests/Fixtures/pzps-liga.html'))),
        '*type=11' => Http::response(file_get_contents(base_path('tests/Fixtures/wzps-liga.html'))),
        '*team_id=701' => Http::response(file_get_contents(base_path('tests/Fixtures/wzps-druzyna.html'))),
        '*type=3' => Http::response(file_get_contents(base_path('tests/Fixtures/wzps-mlodzicy.html'))),
        '*match_id=522' => Http::response(file_get_contents(base_path('tests/Fixtures/wzps-mecz.html'))),
        '*' => Http::response(''),
    ]);
});

test('imports only our matches, with scores, sets and kick-off times from both pages', function () {
    $result = app(ScheduleImporter::class)->sync($this->competition);

    expect($result['created'])->toBe(4)
        ->and(Game::count())->toBe(4);

    $won = Game::firstWhere('external_id', '406');
    expect($won->team_id)->toBe($this->seniors->id)
        ->and($won->is_home)->toBeTrue()
        ->and($won->status)->toBe('finished')
        ->and([$won->home_score, $won->away_score])->toBe([3, 0])
        ->and($won->sets_score)->toBe([[25, 22], [25, 20], [25, 15]])
        ->and($won->game_date->format('Y-m-d H:i'))->toBe('2026-09-26 16:00')
        ->and($won->opponentTeam->full_name)->toBe('Fort Tarnowo Podgórne');

    $upcoming = Game::firstWhere('external_id', '410');
    expect($upcoming->status)->toBe('scheduled')
        ->and($upcoming->home_score)->toBeNull()
        ->and($upcoming->game_date->format('H:i'))->toBe('18:30');
});

test('a team entered under another name is recognised as ours', function () {
    app(ScheduleImporter::class)->sync($this->competition);

    $away = Game::firstWhere('external_id', '411');
    expect($away->team_id)->toBe($this->cadets->id)
        ->and($away->is_home)->toBeFalse()
        ->and($away->ourSetsResult())->toBe([1, 3]);

    // Derby: „LOMS Joker PiłaII” bez spacji na stronie drużyny to ta sama nazwa.
    $derby = Game::firstWhere('external_id', '416');
    expect($derby->team_id)->toBe($this->seniors->id)
        ->and($derby->opponent)->toBe('LOMS Joker Piła II')
        ->and($derby->opponent_team_id)->toBeNull()
        ->and($derby->guest_team_id)->toBe($this->cadets->id)
        ->and($derby->kickoffLabel())->toContain('godz. do ustalenia');

    // Mecz pokazuje się w kalendarzu i na stronie obu naszych drużyn.
    expect(Game::forTeam($this->cadets->id)->pluck('external_id')->all())->toContain('416')
        ->and(Game::forTeam($this->seniors->id)->pluck('external_id')->all())->toContain('416');
    Livewire::test(GamesBrowser::class)->set('team', 'kadeci')->assertSee('LOMS Joker Piła II');
});

test('a game can name a second team of ours by hand', function () {
    $this->actingAs(User::factory()->admin()->create());
    $game = Game::factory()->create(['team_id' => $this->seniors->id, 'season_id' => $this->competition->season_id, 'opponent' => 'LOMS Joker Piła II', 'game_date' => now()->addDays(3), 'status' => 'scheduled']);

    Livewire::test(GameManager::class)
        ->call('openEditModal', $game->id)
        ->set('guest_team_id', $this->cadets->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($game->fresh()->guest_team_id)->toBe($this->cadets->id);
    $this->get(route('teams.show', $this->cadets))->assertOk()->assertSee('LOMS Joker Piła II');
});

test('running the import again updates matches instead of duplicating them', function () {
    $importer = app(ScheduleImporter::class);
    $importer->sync($this->competition);

    Game::firstWhere('external_id', '410')->update(['venue' => 'Hala Regionalnego Centrum Sportu']);

    $result = $importer->sync($this->competition->fresh());

    expect(Game::count())->toBe(4)
        ->and($result['created'])->toBe(0)
        ->and(Game::firstWhere('external_id', '410')->venue)->toBe('Hala Regionalnego Centrum Sportu');
});

test('Joker names without a team are reported to the admin', function () {
    $this->competition->teams()->detach($this->cadets->id);

    app(ScheduleImporter::class)->sync($this->competition);

    expect($this->competition->fresh()->sync_message)->toContain('Nieprzypisane nazwy: LOMS Joker Piła II');
});

test('the sync command imports every competition with an address', function () {
    $this->artisan('joker:sync-schedules')->assertSuccessful();

    expect(Game::count())->toBe(4);
});

test('admin saves the source address and team names, and the import runs at once', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->competition->update(['source_url' => null]);
    $this->competition->teams()->detach();

    Livewire::actingAs($admin)
        ->test(CompetitionManager::class)
        ->call('openEditModal', $this->competition->id)
        ->set('source_url', 'https://wzps.poznan.pl/obsady/index.php?mode=12&type=11')
        ->set('team_ids', [$this->seniors->id, $this->cadets->id])
        ->set('external_names', [$this->seniors->id => 'LOMS Joker Piła', $this->cadets->id => 'LOMS Joker Piła II'])
        ->call('save')
        ->assertHasNoErrors();

    expect(Game::count())->toBe(4)
        ->and($this->competition->teams()->find($this->cadets->id)->pivot->external_name)->toBe('LOMS Joker Piła II');
});

test('the league table is copied from the federation page, with our teams marked', function () {
    app(ScheduleImporter::class)->sync($this->competition);

    $standings = LeagueStanding::where('competition_id', $this->competition->id)->where('group_name', 'Grupa 1')->orderBy('position')->get();

    expect(LeagueStanding::where('competition_id', $this->competition->id)->count())->toBe(7)
        ->and($standings)->toHaveCount(5)
        ->and($standings->pluck('position')->all())->toBe([1, 2, 3, 4, 5])
        ->and($standings[0]->competitorName())->toBe('Volley kost-pol.pl Wieleń')
        ->and($standings[0]->points)->toBe(5)
        ->and($standings[2]->competitor_id)->toBe($this->seniors->id)
        ->and($standings[2]->isOwnTeam())->toBeTrue()
        ->and([$standings[3]->team_id, $standings[3]->won, $standings[3]->lost, $standings[3]->points_for])->toBe([$this->cadets->id, 1, 1, 202])
        ->and($standings->pluck('stage_name')->unique()->all())->toBe(['Etap 1'])
        ->and($this->competition->fresh()->sync_message)->toContain('Tabela pobrana');
});

test('a league seen only on the team page gets the matches no other competition has', function () {
    app(ScheduleImporter::class)->sync($this->competition);

    $seniorsLeague = Competition::create([
        'name' => 'Seniorzy WZPS',
        'season_id' => $this->competition->season_id,
        'source_url' => 'https://wzps.poznan.pl/obsady/index.php?mode=8&club_id=7&team_id=701',
    ]);
    $seniorsLeague->teams()->attach($this->seniors->id, ['external_name' => 'LOMS Joker Piła']);

    app(ScheduleImporter::class)->sync($seniorsLeague);

    expect(Game::where('competition_id', $seniorsLeague->id)->pluck('external_id')->sort()->values()->all())->toBe(['518', '522']);

    // Wynik, hala i godzina ze strony meczu.
    $wolves = Game::firstWhere('external_id', '522');
    expect($wolves->opponent)->toBe('LUKS Wilki Wilczyn')
        ->and($wolves->status)->toBe('finished')
        ->and($wolves->ourSetsResult())->toBe([3, 1])
        ->and($wolves->venue)->toBe('64-920 Piła, ul. Wincentego Pola 11 Hala Sportowa LOMS w Pile')
        ->and($wolves->game_date->format('Y-m-d H:i'))->toBe('2026-10-10 17:00');
});

test('youth tables keep every stage and group', function () {
    $youngsters = Team::create(['name' => 'Młodzicy', 'slug' => 'mlodzicy', 'category' => 'mlodzik']);
    $youngstersTwo = Team::create(['name' => 'Młodzicy II', 'slug' => 'mlodzicy-ii', 'category' => 'mlodzik']);
    $league = Competition::create([
        'name' => 'Młodzicy',
        'season_id' => $this->competition->season_id,
        'source_url' => 'https://wzps.poznan.pl/obsady/index.php?mode=12&type=3',
    ]);
    $league->teams()->attach([
        $youngsters->id => ['external_name' => 'LOMS Joker Piła'],
        $youngstersTwo->id => ['external_name' => 'LOMS Joker Piła II'],
    ]);

    app(ScheduleImporter::class)->sync($league);

    $standings = LeagueStanding::where('competition_id', $league->id)->get();
    expect($standings)->toHaveCount(10)
        ->and($standings->map(fn (LeagueStanding $row) => $row->stage_name.' / '.$row->group_name)->unique()->values()->all())
        ->toBe(['Etap 2 / Grupa 1', 'Etap 2 / Grupa 2', 'Etap 2 / Grupa 3', 'Etap 1 / Grupa 1'])
        ->and($standings->where('stage_name', 'Etap 2')->firstWhere('competitor_id', $youngsters->id)->points)->toBe(12);

    // Sety „0:0” to puste pola formularza - nie pokazujemy ich.
    $won = Game::where('team_id', $youngsters->id)->firstWhere('external_id', '681');
    expect($won->sets_score)->toBe([[10, 25], [24, 26]])
        ->and($won->ourSetsResult())->toBe([2, 0])
        ->and(Game::where('team_id', $youngsters->id)->count())->toBe(2)
        ->and(Game::where('team_id', $youngstersTwo->id)->count())->toBe(1);
});

test('match detail addresses are built next to the source page, whatever the link says', function () {
    $html = '<table><tr><td><a href="obsady/index.php?mode=4&amp;stage=2&amp;match_id=249">1</a></td><td>2026-10-11</td><td>LOMS Joker Piła</td><td>-</td><td>ASBL</td><td>3:0</td></tr></table>';

    $row = app(ScheduleImporter::class)->parsePage($html, 'https://wzps.poznan.pl/obsady//index.php?mode=12&type=5')['matches']->sole();

    expect($row['detail_url'])->toBe('https://wzps.poznan.pl/obsady/index.php?mode=4&stage=2&match_id=249');
});

test('a renamed rival is matched by its former name and new names wait for review', function () {
    $fort = RivalTeam::create(['full_name' => 'Fort Tarnowo Podgórne Sponsor', 'aliases' => ['Fort Tarnowo Podgórne']]);

    app(ScheduleImporter::class)->sync($this->competition);

    expect(Game::firstWhere('external_id', '406')->opponent_team_id)->toBe($fort->id)
        ->and(RivalTeam::where('full_name', 'Fort Tarnowo Podgórne')->exists())->toBeFalse()
        ->and($fort->fresh()->needs_review)->toBeFalse()
        ->and(RivalTeam::where('needs_review', true)->count())->toBeGreaterThan(0);
});

test('reads II liga results from the PZPS referee system', function () {
    $league = Competition::create([
        'points_win_3_0' => 3, 'points_win_3_1' => 3, 'points_win_3_2' => 2,
        'points_loss_2_3' => 1, 'points_loss_1_3' => 0, 'points_loss_0_3' => 0,
        'name' => 'II Liga Mężczyzn', 'season_id' => $this->competition->season_id,
        'source_url' => 'https://sedziowie.pzps.pl/system/index.php?mode=12&type=9',
    ]);
    $league->teams()->attach($this->seniors->id, ['external_name' => 'LOMS Joker Piła']);

    app(ScheduleImporter::class)->sync($league);

    expect($league->games()->count())->toBe(3);
    $won = $league->games()->firstWhere('external_id', 'pzps-555');
    expect($won->is_home)->toBeFalse()
        ->and($won->opponent)->toBe('Enea Energetyk Poznań')
        ->and($won->status)->toBe('finished')
        ->and($won->ourSetsResult())->toBe([3, 2])
        ->and($league->games()->firstWhere('external_id', 'pzps-588')->status)->toBe('scheduled')
        ->and(LeagueStanding::where('competition_id', $league->id)->count())->toBe(2);
});

test('PZPS updates a match already imported from another source and cleans up its own duplicate', function () {
    $league = Competition::create([
        'points_win_3_0' => 3, 'points_win_3_1' => 3, 'points_win_3_2' => 2,
        'points_loss_2_3' => 1, 'points_loss_1_3' => 0, 'points_loss_0_3' => 0,
        'name' => 'II Liga Mężczyzn', 'season_id' => $this->competition->season_id,
        'source_url' => 'https://sedziowie.pzps.pl/system/index.php?mode=12&type=9',
    ]);
    $league->teams()->attach($this->seniors->id, ['external_name' => 'LOMS Joker Piła']);
    $base = ['team_id' => $this->seniors->id, 'season_id' => $league->season_id, 'competition_id' => $league->id, 'opponent' => 'Enea Energetyk Poznań', 'is_home' => false, 'game_date' => '2026-10-03 18:00', 'status' => 'scheduled', 'home_score' => null, 'away_score' => null, 'sets_score' => null];
    $original = Game::factory()->create($base + ['external_id' => '4410', 'match_report' => '<p>Relacja</p>']);
    $duplicate = Game::factory()->create($base + ['external_id' => 'pzps-555']);

    app(ScheduleImporter::class)->sync($league);

    expect(Game::find($duplicate->id))->toBeNull()
        ->and($original->fresh()->external_id)->toBe('pzps-555')
        ->and($original->fresh()->ourSetsResult())->toBe([3, 2])
        ->and($league->games()->whereDate('game_date', '2026-10-03')->count())->toBe(1)
        ->and(TrashItem::count())->toBe(1);
});

test('standings rows with a dotted position and extra columns are still read', function () {
    $html = '<h2>Etap 1</h2><b>Grupa 1</b><table><tr><td>4.</td><td><img src="x.png"> LOMS Joker Piła</td><td>6</td><td>8</td><td>3</td><td>11</td><td>14</td><td>563</td><td>568</td><td>0,786</td><td>0,991</td></tr></table>';

    $tables = app(ScheduleImporter::class)->parsePage($html, 'https://sedziowie.pzps.pl/system/index.php?mode=12&type=9')['tables'];

    expect($tables)->toHaveCount(1)
        ->and($tables[0]['rows'][0])->toMatchArray(['position' => 4, 'name' => 'LOMS Joker Piła', 'played' => 6, 'points' => 8, 'sets_lost' => 14]);
});

test('source addresses pasted with &amp; still work', function () {
    $this->competition->update(['source_url' => 'https://sedziowie.pzps.pl/system/index.php?mode=12&amp;type=9']);

    expect($this->competition->sourceUrls())->toBe(['https://sedziowie.pzps.pl/system/index.php?mode=12&type=9']);
});

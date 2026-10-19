<?php

use App\Livewire\Admin\CompetitionManager;
use App\Models\Competition;
use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
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
        '*type=11' => Http::response(file_get_contents(base_path('tests/Fixtures/wzps-liga.html'))),
        '*team_id=701' => Http::response(file_get_contents(base_path('tests/Fixtures/wzps-druzyna.html'))),
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
        ->and($derby->kickoffLabel())->toContain('godz. do ustalenia');
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

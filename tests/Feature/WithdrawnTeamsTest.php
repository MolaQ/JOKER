<?php

use App\Livewire\Admin\CompetitionManager;
use App\Livewire\GamesBrowser;
use App\Models\Competition;
use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Services\ScheduleImporter;
use App\Services\WielkopolskaSiatkowkaParser;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function thirdLeagueWithWithdrawal(): Competition
{
    Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $seniors = Team::create(['name' => 'Seniorzy II', 'slug' => 'seniorzy-ii', 'category' => 'senior']);

    $html = '<figure class="wp-block-table"><table><tbody><tr><td>Grupa A</td><td>Grupa B</td></tr>'
        .'<tr><td>LOMS Joker Piła 2</td><td>KS Kórnik</td></tr>'
        .'<tr><td>NKS Czarnków &#8211; zespół wycofał się</td><td>Rosmosis-Wawrzyniak ASR Krotoszyn</td></tr></tbody></table></figure>'
        .'<h1>ETAP 1</h1><h4>Grupa A</h4><p>1. kolejka: 26.12.2026</p>'
        .'<figure class="wp-block-table"><table><thead><tr><th>Nr meczu</th><th>gospodarz</th><th>gość</th><th>wynik</th></tr></thead><tbody>'
        .'<tr><td></td><td>NKS Czarnków</td><td>LOMS Joker Piła 2</td><td></td></tr>'
        .'<tr><td>1</td><td>LOMS Joker Piła 2</td><td>Fort Tarnowo Podgórne</td><td></td></tr>'
        .'</tbody></table></figure>';

    Http::fake([
        'wielkopolskasiatkowka.com.pl/*' => Http::response(json_encode([['content' => ['rendered' => $html]]]), 200, ['Content-Type' => 'application/json']),
        '*' => Http::response(''),
    ]);

    $league = Competition::create([
        'name' => 'III Liga Mężczyzn',
        'season_id' => Season::current()->value('id'),
        'source_url' => 'https://wielkopolskasiatkowka.com.pl/iii-liga-mezczyzn-26-27/',
    ]);
    $league->teams()->attach($seniors->id, ['external_name' => 'LOMS Joker Piła 2']);

    return $league;
}

test('dopisek o wycofaniu drużyny jest rozpoznawany w różnych postaciach', function () {
    expect(WielkopolskaSiatkowkaParser::withdrawnTeam('NKS Czarnków – zespół wycofał się'))->toBe('NKS Czarnków')
        ->and(WielkopolskaSiatkowkaParser::withdrawnTeam('KS Kórnik (wycofana)'))->toBe('KS Kórnik')
        ->and(WielkopolskaSiatkowkaParser::withdrawnTeam('Drużyna UKS Piątka Turek wycofała się z rozgrywek.'))->toBe('UKS Piątka Turek')
        ->and(WielkopolskaSiatkowkaParser::withdrawnTeam('LOMS Joker Piła 2'))->toBeNull();
});

test('mecze z drużyną wycofaną według strony związku nie trafiają do kalendarza', function () {
    $league = thirdLeagueWithWithdrawal();

    app(ScheduleImporter::class)->sync($league);

    expect($league->fresh()->withdrawn_teams)->toBe(['NKS Czarnków'])
        ->and(Game::where('competition_id', $league->id)->pluck('opponent')->all())->toBe(['Fort Tarnowo Podgórne']);
});

test('wpisanie wycofanej drużyny w panelu odwołuje jej mecze, a skreślenie przywraca', function () {
    $league = thirdLeagueWithWithdrawal();
    $team = $league->teams()->first();
    $game = Game::factory()->create(['team_id' => $team->id, 'season_id' => $league->season_id, 'competition_id' => $league->id, 'opponent' => 'GKS Zamek Gołańcz', 'opponent_team_id' => null, 'status' => 'scheduled', 'home_score' => null, 'away_score' => null, 'game_date' => now()->addWeek()]);

    $league->update(['withdrawn_teams' => ['gks zamek golancz']]);
    expect($league->applyWithdrawals())->toBe(1)
        ->and($game->fresh()->status)->toBe('cancelled');

    Livewire::test(GamesBrowser::class)->assertDontSee('GKS Zamek Gołańcz');

    $admin = User::factory()->admin()->create();
    Livewire::actingAs($admin)->test(CompetitionManager::class)
        ->call('openEditModal', $league->id)
        ->assertSet('withdrawn_teams', 'gks zamek golancz')
        ->set('source_url', '')
        ->set('withdrawn_teams', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($game->fresh()->status)->toBe('scheduled')
        ->and($game->fresh()->result_note)->toBeNull();
});

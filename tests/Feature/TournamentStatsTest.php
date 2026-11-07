<?php

use App\Livewire\Admin\TournamentEditor;
use App\Livewire\TournamentPage;
use App\Models\RivalTeam;
use App\Models\Tournament;
use App\Models\User;
use App\Support\TournamentStats;
use Livewire\Livewire;

beforeEach(function () {
    $this->tournament = Tournament::create(['name' => 'Memoriał', 'start_date' => '2026-11-15', 'has_page' => true]);
    $this->category = $this->tournament->categories()->create(['name' => 'Junior młodszy', 'display_order' => 0]);
    $this->groupA = $this->category->groups()->create(['name' => 'A']);
    $this->groupB = $this->category->groups()->create(['name' => 'B']);
    $team = fn (string $name, $group) => $this->category->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => $name])->id, 'tournament_group_id' => $group->id]);
    [$this->sparta, $this->orzel, $this->gwardia] = [$team('Sparta', $this->groupA), $team('Orzeł', $this->groupA), $team('Gwardia', $this->groupA)];
    [$this->wisla, $this->lech] = [$team('Wisła', $this->groupB), $team('Lech', $this->groupB)];
    $this->play = function ($home, $away, array $sets, $group, string $time = '10:00') {
        $homeSets = count(array_filter($sets, fn ($set) => $set[0] > $set[1]));

        return $this->category->matches()->create([
            'stage' => 'group', 'tournament_group_id' => $group->id, 'home_participant_id' => $home->id, 'away_participant_id' => $away->id,
            'starts_at' => '2026-11-15 '.$time, 'sets_score' => $sets, 'home_sets' => $homeSets, 'away_sets' => count($sets) - $homeSets,
        ]);
    };
});

test('tie-break means the last set was played with the sets level, in every format', function () {
    // Do dwóch wygranych: 1:1 i trzeci set.
    expect(TournamentStats::hasTiebreak([[25, 20], [20, 25], [15, 10]]))->toBeTrue()
        // Zawsze trzy sety: po 2:0 trzeci set nie jest tie-breakiem.
        ->and(TournamentStats::hasTiebreak([[25, 20], [25, 22], [20, 25]]))->toBeFalse()
        // Do trzech wygranych: 2:2 i piąty set.
        ->and(TournamentStats::hasTiebreak([[25, 20], [20, 25], [25, 23], [22, 25], [15, 13]]))->toBeTrue()
        ->and(TournamentStats::hasTiebreak([[25, 20], [20, 25], [25, 23], [25, 22]]))->toBeFalse()
        ->and(TournamentStats::hasTiebreak([[25, 20], [25, 22]]))->toBeFalse()
        ->and(TournamentStats::isDeuce([26, 24]))->toBeTrue()
        ->and(TournamentStats::isDeuce([16, 14]))->toBeTrue()
        ->and(TournamentStats::isDeuce([25, 23]))->toBeFalse()
        ->and(TournamentStats::isDeuce([15, 13]))->toBeFalse();
});

test('team stats count points, comebacks, tie-breaks, deuce sets and set streaks', function () {
    // Orzeł odwraca mecz z 0:2 (do trzech wygranych), wygrywa tie-break.
    ($this->play)($this->sparta, $this->orzel, [[25, 20], [26, 24], [20, 25], [22, 25], [13, 15]], $this->groupA, '10:00');
    // Sparta 3 sety przy formacie „zawsze trzy”: 2:0 i przegrany trzeci.
    ($this->play)($this->sparta, $this->gwardia, [[25, 10], [25, 12], [23, 25]], $this->groupA, '11:00');
    // Mecz trwający się nie liczy.
    $this->category->matches()->create(['stage' => 'group', 'tournament_group_id' => $this->groupA->id, 'home_participant_id' => $this->orzel->id, 'away_participant_id' => $this->gwardia->id, 'is_live' => true, 'sets_score' => [[25, 0]], 'home_sets' => 1, 'away_sets' => 0]);

    $stats = TournamentStats::forCategory($this->category->fresh());
    $teams = $stats->teams()->keyBy(fn ($row) => $row['team']);

    expect($teams['Orzeł'])->toMatchArray(['matches' => 1, 'wins' => 1, 'comebacks' => 1, 'big_comebacks' => 1, 'tiebreaks' => 1, 'tiebreaks_played' => 1, 'points' => 109, 'points_lost' => 106, 'streak' => 3, 'deuce' => 0])
        ->and($teams['Sparta'])->toMatchArray(['matches' => 2, 'wins' => 1, 'comebacks' => 0, 'tiebreaks' => 0, 'tiebreaks_played' => 1, 'deuce' => 1, 'sets_played' => 8, 'streak' => 2, 'sweeps' => 0])
        ->and($teams['Gwardia']['matches'])->toBe(1);

    $leaders = $stats->leaders();
    expect($leaders['comebacks']['rows']->pluck('name')->all())->toBe(['Orzeł'])
        ->and($leaders['points']['rows']->first())->toMatchArray(['position' => 1, 'name' => 'Sparta', 'value' => 179])
        ->and($leaders['tiebreaks']['rows']->first()['display'])->toBe('1/1')
        ->and($leaders->has('avg_lost'))->toBeTrue()
        ->and($leaders['avg_lost']['rows']->pluck('name')->all())->toBe(['Sparta']);

    expect($stats->totals())->toMatchArray(['matches' => 2, 'sets' => 8, 'tiebreaks' => 1, 'deuce' => 1, 'comebacks' => 1, 'big_comebacks' => 1])
        ->and($stats->records()->pluck('value', 'label')->all())->toMatchArray(['Najwyższa wygrana w secie' => '25:10', 'Najdłuższy set' => '26:24']);
});

test('the stats tab switches between a group, the category and the whole tournament', function () {
    ($this->play)($this->sparta, $this->orzel, [[25, 20], [25, 18]], $this->groupA);
    ($this->play)($this->wisla, $this->lech, [[18, 25], [25, 20], [15, 9]], $this->groupB);
    $other = $this->tournament->categories()->create(['name' => 'Młodziczki', 'display_order' => 1]);
    $x = $other->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => 'Polonia'])->id]);
    $y = $other->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => 'Stal'])->id]);
    $other->matches()->create(['stage' => 'group', 'home_participant_id' => $x->id, 'away_participant_id' => $y->id, 'sets_score' => [[25, 5], [25, 7]], 'home_sets' => 2, 'away_sets' => 0]);

    $url = route('tournaments.show', $this->tournament);
    $this->get($url.'?tab=stats')->assertOk()->assertSee('Statystyki')->assertSee('Cała kategoria')->assertSee('Cały turniej')
        ->assertSee('Wisła')->assertSee('Odwrócone losy meczu')->assertDontSee('Polonia');
    $teamsIn = fn (string $scope) => Livewire::test(TournamentPage::class, ['tournament' => $this->tournament])->set('tab', 'stats')->set('scope', $scope)
        ->viewData('stats')['leaders']->flatMap(fn ($stat) => $stat['rows']->pluck('name'))->unique()->sort()->values()->all();
    expect($teamsIn('grupa-'.$this->groupA->id))->toBe(['Orzeł', 'Sparta'])
        ->and($teamsIn(''))->toBe(['Lech', 'Orzeł', 'Sparta', 'Wisła'])
        ->and($teamsIn('turniej'))->toContain('Polonia · Młodziczki', 'Wisła · Junior młodszy');
    $this->get($url.'?tab=stats&zakres=turniej')->assertOk()->assertSee('Polonia · Młodziczki')->assertSee('25:5');
});

test('an admin chooses which stats to show and hiding all of them hides the tab', function () {
    ($this->play)($this->sparta, $this->orzel, [[25, 20], [25, 18]], $this->groupA);
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])
        ->assertSet('statsShown', array_keys(TournamentStats::options()))
        ->set('statsShown', ['points', 'totals']);

    expect(TournamentStats::enabled($this->tournament))->toBe(['points', 'totals']);
    $this->get(route('tournaments.show', $this->tournament).'?tab=stats')->assertSee('Najwięcej zdobytych punktów')->assertDontSee('Najdłuższa seria setów')->assertDontSee('Rekordy');

    Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])->set('statsShown', []);
    $this->get(route('tournaments.show', $this->tournament).'?tab=stats')->assertDontSee('Najwięcej zdobytych punktów');
});

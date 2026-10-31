<?php

use App\Livewire\Admin\TournamentScorers;
use App\Livewire\TournamentScorer;
use App\Models\AuditLog;
use App\Models\RivalTeam;
use App\Models\Tournament;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Carbon;
use Illuminate\Support\Once;
use Livewire\Livewire;

beforeEach(function () {
    Carbon::setTestNow('2026-11-15 09:00');
    $this->tournament = Tournament::create(['name' => 'Memoriał', 'start_date' => '2026-11-15', 'has_page' => true]);
    $category = $this->tournament->categories()->create(['name' => 'Kadetki', 'display_order' => 0]);
    $team = fn (string $name) => $category->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => $name])->id]);
    [$a, $b, $c, $d] = [$team('Sparta'), $team('Orzeł'), $team('Gwardia'), $team('Wisła')];
    $match = fn ($home, $away, $time) => $category->matches()->create(['stage' => 'group', 'home_participant_id' => $home->id, 'away_participant_id' => $away->id, 'starts_at' => '2026-11-15 '.$time, 'court' => 'Boisko 1']);
    $this->first = $match($a, $b, '10:00');
    $this->second = $match($c, $d, '11:00');
    $this->third = $match($a, $c, '12:00');
    $this->scorer = User::factory()->create(['role' => UserRole::Fan, 'name' => 'Sędzia Stolikowy']);
});

afterEach(fn () => Carbon::setTestNow());

test('an admin assigns a scorer who can score only around the tournament days', function () {
    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(TournamentScorers::class, ['tournamentId' => $this->tournament->id])->set('userId', (string) $this->scorer->id)->assertSee('Sędzia Stolikowy');

    expect($this->tournament->canBeScoredBy($this->scorer))->toBeTrue()
        ->and(AuditLog::where('action', 'permissions.scorer')->exists())->toBeTrue();

    $this->actingAs($this->scorer)->get(route('tournaments.show', $this->tournament))->assertSee('Wpisz wyniki');
    $this->get(route('tournaments.score', $this->tournament))->assertOk()->assertSee('Sparta');

    Carbon::setTestNow('2026-11-17 09:00');
    expect($this->tournament->fresh()->canBeScoredBy($this->scorer))->toBeFalse();
    $this->get(route('tournaments.score', $this->tournament))->assertForbidden();

    $this->actingAs(User::factory()->create(['role' => UserRole::Fan]))->get(route('tournaments.score', $this->tournament))->assertForbidden();
});

test('a late start keeps the schedule and shows a possible delay on later matches on that court', function () {
    $this->tournament->scorers()->attach($this->scorer);
    $this->actingAs($this->scorer);

    $screen = Livewire::test(TournamentScorer::class, ['tournament' => $this->tournament])
        ->assertSet('rows.'.$this->first->id.'.start', '10:00')
        ->set('rows.'.$this->first->id.'.start', '10:25')
        ->call('start', $this->first->id);

    $first = $this->first->fresh();
    expect($first->starts_at->format('H:i'))->toBe('10:25')
        ->and($first->planned_starts_at->format('H:i'))->toBe('10:00')
        ->and($first->is_live)->toBeTrue()
        ->and($this->second->fresh()->starts_at->format('H:i'))->toBe('11:00');

    Once::flush();
    expect($this->second->fresh()->possibleDelay())->toBe(25)
        ->and($this->third->fresh()->possibleDelay())->toBe(25);

    $this->get(route('tournaments.show', [$this->tournament, 'tab' => 'matches']))->assertSee('możliwe opóźnienie ok. 25 min');

    $screen->set('rows.'.$this->first->id.'.sets', '25:20, 25:22')->call('save', $this->first->id, true);
    expect($this->first->fresh()->isFinished())->toBeTrue()->and($this->first->fresh()->home_sets)->toBe(2);

    // Turniej nadrabia: drugi mecz rusza o czasie, więc trzeci nie ma już opóźnienia.
    $screen->call('start', $this->second->id);
    Once::flush();
    expect($this->third->fresh()->possibleDelay())->toBeNull();
});

test('finishing a match needs a winner', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(TournamentScorer::class, ['tournament' => $this->tournament])
        ->call('start', $this->first->id)
        ->set('rows.'.$this->first->id.'.sets', '25:20, 20:25')
        ->call('save', $this->first->id, true)
        ->assertHasErrors('rows.'.$this->first->id.'.sets');

    expect($this->first->fresh()->is_live)->toBeTrue();
});

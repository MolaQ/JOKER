<?php

use App\Livewire\Admin\TournamentEditor;
use App\Livewire\Admin\TournamentScorers;
use App\Livewire\TournamentScorer;
use App\Models\AuditLog;
use App\Models\RivalTeam;
use App\Models\Tournament;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Carbon;
use Illuminate\Support\Once;
use Illuminate\Validation\ValidationException;
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

test('a match result can carry an optional highlight like MVP', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(TournamentScorer::class, ['tournament' => $this->tournament])
        ->set("rows.{$this->first->id}.sets", '25:20, 25:18')
        ->set("rows.{$this->first->id}.mvp", 'MVP: Zofia Łęcka')
        ->call('save', $this->first->id, true);

    expect($this->first->fresh()->mvp)->toBe('MVP: Zofia Łęcka');
    $this->get(route('tournaments.matches.show', [$this->tournament, $this->first->number]))->assertSee('MVP: Zofia Łęcka');
});

test('a match started by mistake can be put back to not started', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(TournamentScorer::class, ['tournament' => $this->tournament])
        ->set('rows.'.$this->first->id.'.start', '10:20')
        ->call('start', $this->first->id)
        ->set('rows.'.$this->first->id.'.sets', '25:20')
        ->call('save', $this->first->id)
        ->call('cancelStart', $this->first->id)
        ->assertSee('Rozpocznij mecz')
        ->assertSet('rows.'.$this->first->id.'.start', '10:00');

    $first = $this->first->fresh();
    expect($first->is_live)->toBeFalse()
        ->and($first->starts_at->format('H:i'))->toBe('10:00')
        ->and($first->sets_score)->toBeNull()
        ->and($first->home_sets)->toBeNull();
});

test('pressing start late with the planned time left unchanged keeps the planned time', function () {
    $this->tournament->scorers()->attach($this->scorer);
    $this->actingAs($this->scorer);

    Carbon::setTestNow('2026-11-15 11:42');
    Livewire::test(TournamentScorer::class, ['tournament' => $this->tournament])->call('start', $this->second->id);
    expect($this->second->fresh()->starts_at->format('H:i'))->toBe('11:00')
        ->and($this->second->fresh()->planned_starts_at->format('H:i'))->toBe('11:00');

    Once::flush();
    expect($this->third->fresh()->possibleDelay())->toBeNull();
});

test('sets can be typed without a colon on a phone number keypad', function () {
    expect(TournamentEditor::parseSets('25 20 23 25 15 12'))->toBe([[25, 20], [23, 25], [15, 12]])
        ->and(TournamentEditor::parseSets('25,20,23,25'))->toBe([[25, 20], [23, 25]])
        ->and(TournamentEditor::parseSets('25-20 25.18'))->toBe([[25, 20], [25, 18]])
        ->and(TournamentEditor::parseSets('25:20, 23:25'))->toBe([[25, 20], [23, 25]])
        ->and(fn () => TournamentEditor::parseSets('25 20 23'))->toThrow(ValidationException::class);
});

test('a scorer can show only one category when the same clubs play in several', function () {
    $this->tournament->scorers()->attach($this->scorer);
    $this->actingAs($this->scorer);
    $juniors = $this->tournament->categories()->create(['name' => 'Juniorki', 'display_order' => 1]);
    $home = $juniors->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => 'Polonia'])->id]);
    $away = $juniors->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => 'Legia'])->id]);
    $juniors->matches()->create(['stage' => 'group', 'home_participant_id' => $home->id, 'away_participant_id' => $away->id, 'starts_at' => '2026-11-15 10:00', 'court' => 'Boisko 2']);

    Livewire::test(TournamentScorer::class, ['tournament' => $this->tournament])
        ->assertSee('Wszystkie kategorie')->assertSee('Polonia')->assertSee('Sparta')
        ->set('category', (string) $juniors->id)
        ->assertSee('Polonia')->assertDontSee('Sparta');
});

test('liczy się wpisana godzina rozpoczęcia, nie moment kliknięcia, i w trwającym meczu można ją poprawić', function () {
    $this->actingAs(User::factory()->admin()->create());
    Carbon::setTestNow('2026-11-15 10:17');

    // Wynik wpisywany po fakcie: mecz zaczął się o czasie, więc bez opóźnienia przy kolejnych meczach.
    $screen = Livewire::test(TournamentScorer::class, ['tournament' => $this->tournament])
        ->assertSee('Teraz')
        ->call('start', $this->first->id);

    expect($this->first->fresh()->starts_at->format('H:i'))->toBe('10:00');
    Once::flush();
    expect($this->second->fresh()->possibleDelay())->toBeNull();

    // Jednak zaczęli później: poprawka godziny w trwającym meczu.
    $screen->set('rows.'.$this->first->id.'.start', '10:17')->set('rows.'.$this->first->id.'.sets', '25:20')->call('save', $this->first->id);

    expect($this->first->fresh()->starts_at->format('H:i'))->toBe('10:17')->and($this->first->fresh()->is_live)->toBeTrue();
    Once::flush();
    expect($this->second->fresh()->possibleDelay())->toBe(17);
});

test('drugi telefon z nieodświeżonym ekranem nie cofa godziny rozpoczęcia wpisanej na pierwszym', function () {
    $this->actingAs(User::factory()->admin()->create());

    $stale = Livewire::test(TournamentScorer::class, ['tournament' => $this->tournament]);

    Livewire::test(TournamentScorer::class, ['tournament' => $this->tournament])
        ->set('rows.'.$this->first->id.'.start', '10:17')->call('start', $this->first->id);

    // Nieodświeżony ekran: „Rozpocznij” nie nadpisuje, a po przerysowaniu „Zapisz wynik” zostawia 10:17.
    $stale->call('start', $this->first->id);
    expect($this->first->fresh()->starts_at->format('H:i'))->toBe('10:17');

    $stale->set('category', '')->assertSet('rows.'.$this->first->id.'.start', '10:17')
        ->set('rows.'.$this->first->id.'.sets', '25:20')->call('save', $this->first->id);
    expect($this->first->fresh()->starts_at->format('H:i'))->toBe('10:17')->and($this->first->fresh()->home_sets)->toBe(1);
});

test('trwające mecze są na górze listy, a zakończone na dole', function () {
    $this->actingAs(User::factory()->admin()->create());
    Carbon::setTestNow('2026-11-15 11:05');
    $this->first->update(['home_sets' => 2, 'away_sets' => 0, 'sets_score' => [[25, 20], [25, 18]]]);

    $screen = Livewire::test(TournamentScorer::class, ['tournament' => $this->tournament])
        ->call('start', $this->third->id)
        ->assertSeeInOrder(['Mecz nr '.$this->third->number, 'Mecz nr '.$this->second->number, 'Mecz nr '.$this->first->number]);

    $screen->set('rows.'.$this->third->id.'.sets', '25:10, 25:12')->call('save', $this->third->id, true)
        ->assertSeeInOrder(['Mecz nr '.$this->second->number, 'Mecz nr '.$this->first->number, 'Mecz nr '.$this->third->number]);
});

test('zakończony mecz nie przenosi opóźnienia, jeśli boisko zwolniło się przed następnym meczem', function () {
    $this->actingAs(User::factory()->admin()->create());
    Carbon::setTestNow('2026-11-15 10:18');
    $screen = Livewire::test(TournamentScorer::class, ['tournament' => $this->tournament])
        ->set('rows.'.$this->first->id.'.start', '10:18')
        ->call('start', $this->first->id);

    Once::flush();
    expect($this->second->fresh()->possibleDelay())->toBe(18)->and($this->third->fresh()->possibleDelay())->toBe(18);

    // Skończyli o 10:52, przed meczem o 11:00: bez opóźnienia.
    Carbon::setTestNow('2026-11-15 10:52');
    $screen->set('rows.'.$this->first->id.'.sets', '25:20, 25:22')->call('save', $this->first->id, true);

    Once::flush();
    expect($this->second->fresh()->possibleDelay())->toBeNull()->and($this->third->fresh()->possibleDelay())->toBeNull();

    // Gdyby skończyli o 11:09, następny mecz może zacząć się 9 minut później.
    Carbon::setTestNow('2026-11-15 11:09');
    $this->first->fresh()->touch();

    Once::flush();
    expect($this->second->fresh()->possibleDelay())->toBe(9)->and($this->third->fresh()->possibleDelay())->toBe(9);
});

test('w trwającym meczu ostatni wpisany set to stan bieżący dla kibiców, a po zakończeniu meczu liczą się wszystkie sety', function () {
    $this->actingAs(User::factory()->admin()->create());
    Carbon::setTestNow('2026-11-15 10:30');
    $row = 'rows.'.$this->first->id.'.sets';
    $screen = Livewire::test(TournamentScorer::class, ['tournament' => $this->tournament])->call('start', $this->first->id);

    // Początek seta może być remisem.
    $screen->set($row, '0:0')->call('save', $this->first->id)->assertHasNoErrors();
    expect($this->first->fresh()->currentSetLabel())->toBe('1. set 0:0');

    $screen->set($row, '25:23, 13:10')->call('save', $this->first->id)->assertHasNoErrors()->assertSee('Kibice widzą: 1:0 · 2. set 13:10');
    $match = $this->first->fresh();
    expect([$match->home_sets, $match->away_sets])->toBe([1, 0])
        ->and($match->setsLabel())->toBe('25:23')
        ->and($match->currentSetLabel())->toBe('2. set 13:10')
        ->and($match->isFinished())->toBeFalse();

    $this->get(route('home'))->assertSee('2. set 13:10');
    $this->get(route('tournaments.show', ['tournament' => $this->tournament, 'tab' => 'matches']))->assertSee('2. set 13:10');

    // Set do 25 z przewagą 2 pkt jest skończony od razu.
    $screen->set($row, '25:23, 25:20')->call('save', $this->first->id);
    expect($this->first->fresh()->currentSet())->toBeNull()->and($this->first->fresh()->home_sets)->toBe(2);

    // Tie-break do 15: trwa, dopóki mecz się nie skończy; po zakończeniu liczy się do wyniku.
    $screen->set($row, '25:23, 20:25, 15:12')->call('save', $this->first->id);
    expect($this->first->fresh()->currentSetLabel())->toBe('3. set 15:12')->and($this->first->fresh()->home_sets)->toBe(1);

    $screen->call('save', $this->first->id, true)->assertHasNoErrors();
    $match = $this->first->fresh();
    expect([$match->home_sets, $match->away_sets])->toBe([2, 1])
        ->and($match->isFinished())->toBeTrue()
        ->and($match->currentSet())->toBeNull()
        ->and($match->setsLabel())->toBe('25:23, 20:25, 15:12');

    // Remis w secie przy kończeniu meczu to nadal błąd.
    $screen->call('reopen', $this->first->id)->set($row, '25:23, 12:12')->call('save', $this->first->id, true)->assertHasErrors($row);
});

test('pasek na żywo pobiera się na nowo bez przeładowania strony', function () {
    $this->first->update(['is_live' => true, 'sets_score' => [[25, 23], [13, 10]], 'home_sets' => 1, 'away_sets' => 0]);

    $this->get(route('live-bar', ['from' => '/']))->assertOk()->assertSee('data-live-bar', false)
        ->assertSeeInOrder(['Sparta', '1:0', 'Orzeł', '2. set 13:10'])->assertDontSee('live-bar fixed', false);
    $this->get(route('live-bar', ['from' => parse_url(route('tournaments.show', $this->tournament), PHP_URL_PATH)]))->assertOk()->assertDontSee('data-live-bar', false);
});

test('godzina zmieniona w panelu przed meczem to nowy plan, a opóźnienie pokazuje się w nawiasie przy godzinie z harmonogramu', function () {
    // Stary zapis z panelu (zamiana meczów w dniu turnieju): godzina 12:00, w „planie” jeszcze 10:45.
    $this->third->update(['planned_starts_at' => '2026-11-15 10:45']);
    $third = $this->third->fresh();
    expect($third->delayMinutes())->toBeNull()
        ->and($third->scheduledAt()->format('H:i'))->toBe('12:00')
        ->and($third->possibleDelay())->toBeNull();
    $this->get(route('tournaments.show', [$this->tournament, 'tab' => 'matches']))->assertDontSee('opóźnienie')->assertDontSee('wcześniej o');

    // Stolik rusza z meczem o 12:18: plan to 12:00, nie stara godzina.
    $this->tournament->scorers()->attach($this->scorer);
    $this->actingAs($this->scorer);
    Livewire::test(TournamentScorer::class, ['tournament' => $this->tournament])
        ->set('rows.'.$this->third->id.'.start', '12:18')->call('start', $this->third->id)
        ->set('rows.'.$this->third->id.'.sets', '25:20, 25:22')->call('save', $this->third->id, true);

    $third = $this->third->fresh();
    expect($third->planned_starts_at->format('H:i'))->toBe('12:00')
        ->and($third->delayMinutes())->toBe(18)
        ->and($third->scheduledAt()->format('H:i'))->toBe('12:00');

    $this->get(route('tournaments.show', [$this->tournament, 'tab' => 'matches']))->assertSee('(opóźnienie 18 min)');
    $this->get(route('tournaments.matches.show', [$this->tournament, $third]))->assertSee('12:00')->assertSee('(opóźnienie 18 min)');
});

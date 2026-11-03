<?php

use App\Livewire\Admin\TournamentEditor;
use App\Livewire\TournamentScorer;
use App\Models\RivalTeam;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Services\PushNotifier;
use App\Support\SiteSettings;
use App\Support\TournamentPractice;
use Livewire\Livewire;

beforeEach(function () {
    $this->tournament = Tournament::create(['name' => 'Memoriał', 'start_date' => now()->addDays(3)->toDateString(), 'end_date' => now()->addDays(5)->toDateString(), 'city' => 'Piła', 'is_organizer' => true, 'has_page' => true, 'halls' => [['name' => 'LOMS', 'courts' => ['Sektor I']]]]);
    $category = $this->tournament->categories()->create(['name' => 'Kadetki', 'schedule_days' => [['date' => now()->addDays(3)->toDateString(), 'time' => '09:00', 'from' => '']]]);
    $group = $category->groups()->create(['name' => 'Grupa A']);
    [$a, $b] = collect(['MKS Kalisz', 'Sparta Złotów'])->map(fn ($name) => $category->participants()->create(['tournament_group_id' => $group->id, 'competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => $name])->id]))->all();
    $this->match = $category->matches()->create(['tournament_group_id' => $group->id, 'stage' => 'group', 'number' => 1, 'home_participant_id' => $a->id, 'away_participant_id' => $b->id, 'starts_at' => now()->addDays(3)->setTime(9, 0), 'court' => 'Sektor I']);
    $this->final = $category->matches()->create(['stage' => 'placement', 'number' => 2, 'placement' => 1, 'home_source' => 'group:'.$group->id.':1', 'away_source' => 'winner:'.$this->match->id, 'starts_at' => now()->addDays(4)->setTime(12, 30)]);
    $this->scorer = User::factory()->create();
    $this->tournament->scorers()->attach($this->scorer);
});

test('a practice copy has the same matches today, no results and the same scorers', function () {
    $this->match->update(['home_sets' => 2, 'away_sets' => 0, 'sets_score' => [[25, 10], [25, 12]]]);

    $practice = TournamentPractice::create($this->tournament->fresh());
    $category = $practice->categories()->first();
    $group = $category->groups()->first();
    $first = $category->matches()->where('number', 1)->first();
    $final = $category->matches()->where('number', 2)->first();

    expect($practice->has_page)->toBeFalse()
        ->and($practice->practiceOf->is($this->tournament))->toBeTrue()
        ->and($practice->start_date->isToday())->toBeTrue()
        ->and($practice->end_date->toDateString())->toBe(today()->addDays(2)->toDateString())
        ->and($practice->halls)->toBe($this->tournament->halls)
        ->and($practice->scorers->modelKeys())->toBe([$this->scorer->id])
        ->and($category->schedule_days[0]['date'])->toBe(today()->toDateString())
        ->and($first->starts_at->format('Y-m-d H:i'))->toBe(today()->setTime(9, 0)->format('Y-m-d H:i'))
        ->and($first->home_sets)->toBeNull()
        ->and($first->home->tournament_category_id)->toBe($category->id)
        ->and($final->home_source)->toBe('group:'.$group->id.':1')
        ->and($final->away_source)->toBe('winner:'.$first->id)
        ->and($final->starts_at->format('Y-m-d H:i'))->toBe(today()->addDay()->setTime(12, 30)->format('Y-m-d H:i'))
        ->and($this->match->fresh()->home_sets)->toBe(2);
});

test('fans cannot see a practice tournament, its scorers can, and it sends no notifications', function () {
    $practice = TournamentPractice::create($this->tournament);

    $this->get(route('tournaments.show', $practice))->assertNotFound();
    $late = User::factory()->create();
    $this->tournament->scorers()->attach($late);
    expect($practice->canBeScoredBy($late))->toBeTrue()
        ->and($practice->canBeScoredBy(User::factory()->create()))->toBeFalse();
    $this->get(route('tournaments.tv', $practice))->assertNotFound();
    $this->actingAs($this->scorer)->get(route('tournaments.show', $practice))->assertOk()
        ->assertSee('Turniej ćwiczebny')->assertSee('noindex', false);

    $push = new class(app(SiteSettings::class)) extends PushNotifier
    {
        public int $sent = 0;

        public function send(string $title, string $body, string $url, ?string $tag = null, ?array $userIds = null, array $audience = []): int
        {
            return ++$this->sent;
        }
    };

    $match = TournamentMatch::whereIn('tournament_category_id', $practice->categories()->select('id'))->where('number', 1)->first();
    $match->update(['home_sets' => 2, 'away_sets' => 1, 'sets_score' => [[25, 10], [20, 25], [15, 10]]]);
    $push->tournamentMatchFinished($match->fresh());
    expect($push->upcoming($match->fresh(), 10))->toBe(0)
        ->and($push->sent)->toBe(0);
});

test('a scorer finishes a practice match and the organizer clears the practice', function () {
    $practice = TournamentPractice::create($this->tournament);
    $match = TournamentMatch::whereIn('tournament_category_id', $practice->categories()->select('id'))->where('number', 1)->first();

    Livewire::actingAs($this->scorer)->test(TournamentScorer::class, ['tournament' => $practice])
        ->call('start', $match->id)
        ->set("rows.{$match->id}.sets", '25:20, 25:18')
        ->call('save', $match->id, true)
        ->assertHasNoErrors();

    expect($match->fresh()->home_sets)->toBe(2)
        ->and(TournamentMatch::whereIn('tournament_category_id', $practice->categories()->select('id'))->where('number', 2)->first()->away_participant_id)->not->toBeNull();

    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)->test(TournamentEditor::class, ['tournament' => $practice->fresh()])->call('resetPractice');

    $fresh = $match->fresh();
    expect($fresh->home_sets)->toBeNull()
        ->and($fresh->is_live)->toBeFalse()
        ->and($fresh->starts_at->format('H:i'))->toBe('09:00')
        ->and(TournamentMatch::whereIn('tournament_category_id', $practice->categories()->select('id'))->where('number', 2)->first()->away_participant_id)->toBeNull()
        ->and($this->match->fresh()->home_sets)->toBeNull();
});

test('the organizer creates the practice from the scorers section once and deletes it', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)->test(TournamentEditor::class, ['tournament' => $this->tournament])
        ->set('section', 'wpisujacy')
        ->assertSee('Utwórz turniej ćwiczebny')
        ->call('createPractice')
        ->assertRedirect();

    $practice = $this->tournament->practices()->sole();

    Livewire::actingAs($admin)->test(TournamentEditor::class, ['tournament' => $this->tournament])->call('createPractice');
    expect($this->tournament->practices()->count())->toBe(1);

    Livewire::actingAs($admin)->test(TournamentEditor::class, ['tournament' => $practice])
        ->assertSee('Turniej ćwiczebny')
        ->call('deletePractice')
        ->assertRedirect();

    expect(Tournament::find($practice->id))->toBeNull()
        ->and($this->tournament->fresh())->not->toBeNull()
        ->and(TournamentMatch::count())->toBe(2);
});

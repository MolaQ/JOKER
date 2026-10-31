<?php

use App\Livewire\Admin\TournamentEditor;
use App\Livewire\TournamentPage;
use App\Models\RivalTeam;
use App\Models\Season;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentParticipant;
use App\Models\TrainingSession;
use App\Models\User;
use App\Support\TournamentBracket;
use App\Support\TournamentTable;
use Livewire\Livewire;

beforeEach(function () {
    Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $this->team = Team::create(['name' => 'Kadetki', 'slug' => 'kadetki', 'category' => 'youth']);
    $this->tournament = Tournament::create([
        'name' => 'Memoriał im. Włodzimierza Winklera', 'edition' => 'XII', 'start_date' => '2026-11-14', 'end_date' => '2026-11-15',
        'city' => 'Piła', 'is_organizer' => true, 'series' => Tournament::SERIES_MEMORIAL, 'has_page' => true,
        'guest_info' => '<p>Hala Lotnisko, parking od ul. Lotniczej</p>',
    ]);
});

/**
 * @return array{0: TournamentParticipant, 1: TournamentParticipant, 2: TournamentParticipant}
 */
function memorialGroup(Tournament $tournament, Team $team): array
{
    $category = $tournament->categories()->create(['name' => 'Kadetki', 'display_order' => 0]);
    $group = $category->groups()->create(['name' => 'Grupa A', 'display_order' => 0]);
    $joker = $category->participants()->create(['tournament_group_id' => $group->id, 'competitor_type' => 'team', 'competitor_id' => $team->id]);
    $kalisz = $category->participants()->create(['tournament_group_id' => $group->id, 'competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => 'UKS Dwójka Kalisz'])->id]);
    $poznan = $category->participants()->create(['tournament_group_id' => $group->id, 'competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => 'Energetyk Poznań'])->id]);

    return [$joker, $kalisz, $poznan];
}

test('group table gives 3 points for a clean win and 2:1 after a tie-break', function () {
    [$joker, $kalisz, $poznan] = memorialGroup($this->tournament, $this->team);
    $group = $joker->group;
    $base = ['tournament_category_id' => $group->tournament_category_id, 'stage' => TournamentMatch::STAGE_GROUP];
    $group->matches()->create($base + ['home_participant_id' => $joker->id, 'away_participant_id' => $kalisz->id, 'home_sets' => 2, 'away_sets' => 0, 'sets_score' => [[25, 20], [25, 18]]]);
    $group->matches()->create($base + ['home_participant_id' => $poznan->id, 'away_participant_id' => $joker->id, 'home_sets' => 2, 'away_sets' => 1, 'sets_score' => [[25, 20], [20, 25], [15, 12]]]);
    $group->matches()->create($base + ['home_participant_id' => $kalisz->id, 'away_participant_id' => $poznan->id]);

    $table = $group->fresh()->table()->keyBy(fn (array $row) => $row['participant']->id);

    expect($table[$joker->id])->toMatchArray(['points' => 4, 'won' => 1, 'lost' => 1, 'sets_won' => 3, 'sets_lost' => 2, 'position' => 1])
        ->and($table[$poznan->id])->toMatchArray(['points' => 2, 'played' => 1, 'position' => 2])
        ->and($table[$kalisz->id])->toMatchArray(['points' => 0, 'position' => 3]);
});

test('final standings come from placement matches and manual positions win', function () {
    [$joker, $kalisz, $poznan] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $category->matches()->create(['stage' => TournamentMatch::STAGE_PLACEMENT, 'placement' => 1, 'home_participant_id' => $kalisz->id, 'away_participant_id' => $joker->id, 'home_sets' => 1, 'away_sets' => 2]);
    $poznan->update(['final_position' => 3]);

    $standings = TournamentTable::finalStandings($category->fresh(['matches', 'participants']))
        ->mapWithKeys(fn (array $row) => [$row['participant']->id => $row['position']]);

    expect($standings->all())->toBe([$joker->id => 1, $kalisz->id => 2, $poznan->id => 3]);
});

test('memorial tab opens the newest edition with categories and tabs', function () {
    [$joker] = memorialGroup($this->tournament, $this->team);
    $this->tournament->categories()->create(['name' => 'Młodziczki', 'display_order' => 1]);
    Tournament::create(['name' => 'Memoriał im. Włodzimierza Winklera', 'edition' => 'XI', 'start_date' => '2025-11-15', 'series' => Tournament::SERIES_MEMORIAL, 'has_page' => true]);

    $this->get('/memorial')->assertRedirect(route('tournaments.show', $this->tournament));

    $this->get(route('tournaments.show', $this->tournament))
        ->assertOk()
        ->assertSee('Memoriał im. Włodzimierza Winklera')
        ->assertSee('Młodziczki')
        ->assertSee('UKS Dwójka Kalisz')
        ->assertSee('Poprzednie edycje');

    Livewire::test(TournamentPage::class, ['tournament' => $this->tournament])
        ->call('selectTab', 'groups')
        ->assertSee('Grupa A')
        ->call('selectTab', 'info')
        ->assertSee('Hala Lotnisko');

    Livewire::test(TournamentPage::class, ['tournament' => $this->tournament, 'categoryId' => $this->tournament->categories()->where('name', 'Młodziczki')->value('id')])
        ->assertDontSee('UKS Dwójka Kalisz');
});

test('tournament and age category have readable addresses and old ones redirect', function () {
    memorialGroup($this->tournament, $this->team);
    $tournament = $this->tournament->fresh();
    $second = $tournament->categories()->create(['name' => 'Młodziczki', 'display_order' => 1]);
    $base = route('tournaments.show', $tournament);

    expect($base)->toEndWith('/tournaments/'.$tournament->id.'/'.preg_replace('/^\d+-/', '', $tournament->slug))
        ->and(route('tournaments.show', ['tournament' => $tournament, 'category' => 'mlodziczki']))->toBe($base.'/mlodziczki');

    $this->get($base)->assertOk()->assertSee($base.'/mlodziczki', false);
    $this->get($base.'/mlodziczki')->assertOk()->assertSee('<title>'.$tournament->fullName().' - Młodziczki', false);
    $this->get('/tournaments/'.$tournament->slug.'?category='.$second->id)->assertRedirect($base.'?category='.$second->id);
    $this->get($base.'?category='.$second->id.'&tab=groups')->assertRedirect($base.'/mlodziczki?tab=groups');
    $this->get($base.'/nie-ma-takiej')->assertRedirect($base);
    $this->get($base.'/'.$tournament->categories()->first()->slug())->assertRedirect($base);
});

test('memorial tab without a published edition shows a coming soon page', function () {
    $this->tournament->update(['has_page' => false]);

    $this->get('/memorial')->assertOk()->assertSee('Strona najbliższej edycji pojawi się tu wkrótce');
    $this->get(route('tournaments.show', $this->tournament))->assertNotFound();
});

test('admin builds a category with teams, a group, round robin matches and results', function () {
    $this->actingAs(User::factory()->admin()->create());
    $rival = RivalTeam::create(['full_name' => 'MKS Kalisz']);

    $this->get(route('admin.tournaments.edit', $this->tournament))->assertOk()->assertSee('Strona i informator');

    $editor = Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])
        ->set('newCategory', 'Kadetki')->call('addCategory')
        ->set('newGroup', 'Grupa A')->call('addGroup')
        ->set('newParticipant', 'team:'.$this->team->id)->call('addParticipant')
        ->set('newParticipant', 'rival:'.$rival->id)->call('addParticipant')
        ->set('newParticipantName', 'Sparta Złotów')->call('addParticipant')
        ->assertHasNoErrors();

    $category = $this->tournament->categories()->sole();
    $group = $category->groups()->sole();
    $participants = $category->participants()->get();
    expect($participants)->toHaveCount(3)
        ->and(RivalTeam::where('full_name', 'Sparta Złotów')->exists())->toBeTrue();

    foreach ($participants as $participant) {
        $editor->set("participants.{$participant->id}.group", (string) $group->id);
    }
    $editor->set("participants.{$participants[0]->id}.roster", "Anna Nowak\nOla Kowalska")
        ->call('saveParticipants')
        ->call('generateGroupMatches', $group->id)
        ->call('generateGroupMatches', $group->id);

    expect($group->matches()->count())->toBe(3)
        ->and($participants[0]->fresh()->rosterLines())->toBe(['Anna Nowak', 'Ola Kowalska']);

    $match = $group->matches()->first();
    $editor->set("results.{$match->id}.sets", '25:20, 23:25 15:12')->call('saveResult', $match->id)->assertHasNoErrors();
    expect($match->fresh())->home_sets->toBe(2)->away_sets->toBe(1)->sets_score->toBe([[25, 20], [23, 25], [15, 12]]);

    $editor->set("results.{$match->id}.sets", '25:25')->call('saveResult', $match->id)->assertHasErrors("results.{$match->id}.sets");

    $editor->set('match.stage', 'placement')->set('match.placement', 7)
        ->set('match.home_placeholder', '4. gr. A')->set('match.away_placeholder', '4. gr. B')
        ->call('addMatch')->assertHasNoErrors();
    expect($category->matches()->where('stage', 'placement')->sole()->title())->toContain('7');
});

test('coaches cannot open the tournament editor', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.tournaments.edit', $this->tournament))->assertForbidden();
});

test('a second bracket for places 5-8 gets its own heading and feeds the final standings', function () {
    [$joker, $kalisz, $poznan] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $fourth = $category->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => 'Sparta Złotów'])->id]);

    $category->matches()->create(['stage' => 'playoff', 'bracket_round' => 1, 'home_participant_id' => $joker->id, 'away_participant_id' => $kalisz->id, 'home_sets' => 2, 'away_sets' => 0, 'starts_at' => '2026-11-15 10:00']);
    $category->matches()->create(['stage' => 'playoff', 'bracket_round' => 2, 'home_participant_id' => $joker->id, 'away_participant_id' => $poznan->id, 'home_sets' => 2, 'away_sets' => 1, 'starts_at' => '2026-11-15 15:30']);
    $category->matches()->create(['stage' => 'playoff', 'placement' => 5, 'bracket_round' => 1, 'home_participant_id' => $fourth->id, 'away_participant_id' => $kalisz->id]);
    $category->matches()->create(['stage' => 'playoff', 'placement' => 5, 'bracket_round' => 1, 'home_placeholder' => '4. gr. A', 'away_placeholder' => '4. gr. B']);

    $standings = TournamentTable::finalStandings($category->fresh(['matches', 'participants']))
        ->mapWithKeys(fn (array $row) => [$row['participant']->id => $row['position']]);
    expect($standings->all())->toBe([$joker->id => 1, $poznan->id => 2]);

    Livewire::test(TournamentPage::class, ['tournament' => $this->tournament])
        ->call('selectTab', 'bracket')
        ->assertSee('Play-off o miejsca od 5.')
        ->assertSeeText('15.11')->assertSeeText('15:30');
});

test('match time typed in the panel is saved without pressing save', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker, $kalisz] = memorialGroup($this->tournament, $this->team);
    $match = $joker->category->matches()->create(['stage' => 'placement', 'placement' => 3, 'home_participant_id' => $joker->id, 'away_participant_id' => $kalisz->id, 'starts_at' => '2026-11-14 15:40']);

    Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])
        ->set("results.{$match->id}.starts_at", '2026-11-15T16:40');

    expect($match->fresh()->starts_at->format('Y-m-d H:i'))->toBe('2026-11-15 16:40');
});

test('organizer names bracket phases and puts the third place match under the final', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker, $kalisz, $poznan] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $fourth = $category->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => 'Sparta Złotów'])->id]);

    $editor = Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])->set('section', 'mecze');
    foreach ([[$joker, $fourth], [$kalisz, $poznan]] as $slot => [$home, $away]) {
        $editor->set('match.stage', 'playoff')->set('match.bracket_round', 1)->set('match.bracket_slot', $slot + 1)
            ->set('match.phase_name', 'Półfinał')->set('match.home', $home->id)->set('match.away', $away->id)
            ->call('addMatch')->assertHasNoErrors();
    }
    $editor->set('match.stage', 'playoff')->set('match.bracket_round', 2)->set('match.bracket_slot', 1)->set('match.phase_name', 'Finały')
        ->set('match.decides_place', 1)->set('match.home_placeholder', 'Zwycięzca SF1')->set('match.away_placeholder', 'Zwycięzca SF2')
        ->call('addMatch')->assertHasNoErrors()
        ->set('match.stage', 'playoff')->set('match.bracket_round', 2)->set('match.bracket_slot', 2)
        ->set('match.decides_place', 3)->set('match.label', 'Mecz o trzecie miejsce')
        ->call('addMatch')->assertHasNoErrors();

    $final = $category->matches()->where('decides_place', 1)->sole();
    $editor->call('startMatchEdit', $final->id)
        ->set('editMatch.home', (string) $joker->id)->set('editMatch.away', (string) $kalisz->id)
        ->call('saveMatchEdit')->assertHasNoErrors();
    $final->refresh()->update(['home_sets' => 2, 'away_sets' => 0]);
    $category->matches()->where('decides_place', 3)->sole()->update(['home_participant_id' => $fourth->id, 'away_participant_id' => $poznan->id, 'home_sets' => 0, 'away_sets' => 2]);

    $standings = TournamentTable::finalStandings($category->fresh(['matches', 'participants']))
        ->mapWithKeys(fn (array $row) => [$row['participant']->id => $row['position']]);
    expect($standings->all())->toBe([$joker->id => 1, $kalisz->id => 2, $poznan->id => 3, $fourth->id => 4]);

    Livewire::withQueryParams(['tab' => 'bracket'])->test(TournamentPage::class, ['tournament' => $this->tournament])
        ->assertSeeInOrder(['Półfinał', 'Finały', 'LOMS Joker Piła', 'Mecz o trzecie miejsce']);
});

test('bracket matches stand in their column, and any match can be edited', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker, $kalisz] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $category->matches()->create(['stage' => 'playoff', 'bracket_round' => 1, 'bracket_slot' => 1, 'home_placeholder' => '1. gr. A', 'away_placeholder' => '2. gr. B']);
    $fifth = $category->matches()->create(['stage' => 'playoff', 'bracket_round' => 3, 'bracket_slot' => 3, 'decides_place' => 5, 'home_placeholder' => 'Przegrany 1', 'away_placeholder' => 'Przegrany 2']);
    $group = $category->matches()->create(['stage' => 'group', 'tournament_group_id' => $joker->tournament_group_id, 'home_participant_id' => $joker->id, 'away_participant_id' => $kalisz->id]);

    Livewire::withQueryParams(['tab' => 'bracket'])->test(TournamentPage::class, ['tournament' => $this->tournament])
        ->assertSeeInOrder(['Runda 1', '1. gr. A', 'Finały', 'Mecz o 5. miejsce']);

    Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])
        ->call('startMatchEdit', $group->id)
        ->set('editMatch.starts_at', '2026-11-14T09:30')
        ->set('editMatch.court', 'Boisko 3')
        ->call('saveMatchEdit')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect($group->fresh())->court->toBe('Boisko 3')
        ->and($group->fresh()->starts_at->format('H:i'))->toBe('09:30')
        ->and($fifth->fresh()->title())->toBe('Mecz o 5. miejsce');
});

test('bracket slots follow group places and earlier results: forecast while the group plays, then confirmed', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker, $kalisz, $poznan] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $groupA = $joker->group;
    $groupB = $category->groups()->create(['name' => 'Grupa B', 'display_order' => 1]);
    [$sparta, $orzel, $gwardia] = collect(['Sparta Złotów', 'Orzeł Trzcianka', 'Gwardia Wrocław'])
        ->map(fn (string $name) => $category->participants()->create(['tournament_group_id' => $groupB->id, 'competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => $name])->id]))
        ->all();

    $editor = Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])->set('section', 'mecze')
        ->call('generateGroupMatches', $groupA->id)
        ->call('generateGroupMatches', $groupB->id);

    $editor->set('match.stage', 'playoff')->set('match.bracket_round', 1)->set('match.label', 'Półfinał 1')
        ->set('match.home_source', "group:{$groupA->id}:1")->set('match.away_source', "group:{$groupB->id}:2")
        ->call('addMatch')->assertHasNoErrors();
    $semi = $category->matches()->where('label', 'Półfinał 1')->sole();
    expect($semi->number)->toBe(7);

    $editor->set('match.stage', 'playoff')->set('match.bracket_round', 2)->set('match.decides_place', 1)
        ->set('match.home_source', "winner:{$semi->id}")->set('match.away_source', "loser:{$semi->id}")
        ->call('addMatch')->assertHasNoErrors();
    $final = $category->matches()->where('decides_place', 1)->sole();

    $play = function (TournamentParticipant $home, TournamentParticipant $away, string $sets) use ($editor, $category) {
        $match = $category->matches()->where('home_participant_id', $home->id)->where('away_participant_id', $away->id)->sole();
        $editor->set("results.{$match->id}.sets", $sets)->call('saveResult', $match->id)->assertHasNoErrors();
    };

    // Grupa A rozegrana w całości, w grupie B jeden mecz: Orzeł chwilowo drugi.
    $play($joker, $kalisz, '25:10, 25:10');
    $play($joker, $poznan, '25:10, 25:10');
    $play($kalisz, $poznan, '25:10, 25:10');
    $play($sparta, $orzel, '10:25, 10:25');

    expect($semi->fresh())->home_participant_id->toBe($joker->id)->away_participant_id->toBeNull();

    Livewire::withQueryParams(['tab' => 'bracket'])->test(TournamentPage::class, ['tournament' => $this->tournament])
        ->assertSeeInOrder(['#7', '1A', 'LOMS', '2B', 'Sparta Złotów', 'prognoza', 'zwycięzca', '→ #8'])
        ->assertSee('1 z 3 meczów')
        ->assertSeeInOrder(['Zwycięzca meczu #7', 'Przegrany meczu #7']);

    $play($sparta, $gwardia, '25:10, 25:10');
    $play($orzel, $gwardia, '25:10, 25:10');
    expect($semi->fresh()->away_participant_id)->toBe($sparta->id);

    $editor->set("results.{$semi->id}.sets", '25:20, 25:20')->call('saveResult', $semi->id);
    expect($final->fresh())->home_participant_id->toBe($joker->id)->away_participant_id->toBe($sparta->id);

    Livewire::withQueryParams(['tab' => 'ranking'])->test(TournamentPage::class, ['tournament' => $this->tournament])
        ->assertSeeInOrder(['Przegrany meczu #8', '🏆', 'Zwycięzca meczu #8']);

    $editor->set('match.stage', 'playoff')->set('match.bracket_round', 2)->set('match.home_source', 'winner:999999')
        ->call('addMatch')->assertHasErrors('match.home_source');
});

test('a final group is built from group places, autosaves and its table decides the final standings', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker, $kalisz, $poznan] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $groupA = $joker->group;
    $groupB = $category->groups()->create(['name' => 'Grupa B', 'display_order' => 1]);
    [$sparta, $orzel, $gwardia] = collect(['Sparta Złotów', 'Orzeł Trzcianka', 'Gwardia Wrocław'])
        ->map(fn (string $name) => $category->participants()->create(['tournament_group_id' => $groupB->id, 'competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => $name])->id]))
        ->all();

    $editor = Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])->set('section', 'grupy')
        ->call('generateGroupMatches', $groupA->id)
        ->call('generateGroupMatches', $groupB->id)
        ->set('newGroup', 'Grupa finałowa')->call('addGroup');
    $final = $category->groups()->where('name', 'Grupa finałowa')->sole();

    foreach (["group:{$groupA->id}:1", "group:{$groupA->id}:2", "group:{$groupB->id}:1", "group:{$groupB->id}:2"] as $index => $slot) {
        $editor->call('addGroupSlot', $final->id)->set("groupSlots.{$final->id}.{$index}", $slot);
    }
    $editor->set("groupPlaces.{$final->id}.1", '1')->set("groupPlaces.{$final->id}.2", '2')
        ->set("groupNames.{$final->id}", 'Finał czwórki')
        ->set("participants.{$poznan->id}.roster", 'Ola Nowak')
        ->call('generateGroupMatches', $final->id)
        ->assertHasNoErrors();

    $final->refresh();
    expect($final->name)->toBe('Finał czwórki')
        ->and($final->slots)->toHaveCount(4)
        ->and($final->final_places)->toBe(['1' => 1, '2' => 2])
        ->and($final->matches()->count())->toBe(6)
        ->and($final->matches()->whereNotNull('home_source')->count())->toBe(6)
        ->and($poznan->fresh()->roster)->toBe('Ola Nowak');

    Livewire::withQueryParams(['tab' => 'ranking'])->test(TournamentPage::class, ['tournament' => $this->tournament])
        ->assertSeeInOrder(['2. miejsce – Finał czwórki', '1. miejsce – Finał czwórki']);

    $play = function (TournamentParticipant $home, TournamentParticipant $away, string $sets, ?int $groupId = null) use ($editor, $category) {
        $match = $category->matches()->where('tournament_group_id', $groupId ?? $home->tournament_group_id)
            ->whereIn('home_participant_id', [$home->id, $away->id])->whereIn('away_participant_id', [$home->id, $away->id])->sole();
        $flip = $match->home_participant_id !== $home->id;
        $editor->set("results.{$match->id}.sets", $flip ? collect(explode(', ', $sets))->map(fn ($set) => implode(':', array_reverse(explode(':', $set))))->implode(', ') : $sets);
    };
    $play($joker, $kalisz, '25:10, 25:10');
    $play($joker, $poznan, '25:10, 25:10');
    $play($kalisz, $poznan, '25:10, 25:10');
    $play($sparta, $orzel, '25:10, 25:10');
    $play($sparta, $gwardia, '25:10, 25:10');
    $play($orzel, $gwardia, '25:10, 25:10');

    // Grupa finałowa: drużyny wpisały się same, Joker wygrywa wszystko, Sparta drugie miejsce.
    expect($final->matches()->whereNull('home_participant_id')->count())->toBe(0);
    $play($joker, $kalisz, '25:10, 25:10', $final->id);
    $play($joker, $sparta, '25:10, 25:10', $final->id);
    $play($joker, $orzel, '25:10, 25:10', $final->id);
    $play($kalisz, $sparta, '10:25, 10:25', $final->id);
    $play($kalisz, $orzel, '25:10, 25:10', $final->id);
    $play($sparta, $orzel, '25:10, 25:10', $final->id);

    Livewire::withQueryParams(['tab' => 'ranking'])->test(TournamentPage::class, ['tournament' => $this->tournament])
        ->assertSeeInOrder(['Sparta Złotów', '🏆', 'LOMS Joker Piła']);
});

test('group points follow the tournament scoring, and a group can override it', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker, $kalisz, $poznan] = memorialGroup($this->tournament, $this->team);
    $group = $joker->group;
    $category = $joker->category;
    $category->matches()->create(['stage' => 'group', 'tournament_group_id' => $group->id, 'home_participant_id' => $joker->id, 'away_participant_id' => $kalisz->id, 'home_sets' => 2, 'away_sets' => 1, 'sets_score' => [[25, 20], [20, 25], [15, 10]]]);

    $points = fn () => TournamentBracket::for($category->fresh(['groups.participants', 'participants', 'matches', 'tournament']))->groupTable($group->fresh())->mapWithKeys(fn (array $row) => [$row['participant']->id => $row['points']]);
    expect($points()->all())->toMatchArray([$joker->id => 2, $kalisz->id => 1]);

    $editor = Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])
        ->set('scoring.win_tiebreak', '3')->set('scoring.loss_tiebreak', '0')->assertHasNoErrors();
    expect($this->tournament->fresh()->scoring)->toMatchArray(['win' => 3, 'win_tiebreak' => 3, 'loss_tiebreak' => 0])
        ->and($points()->all())->toMatchArray([$joker->id => 3, $kalisz->id => 0]);

    $editor->set('section', 'grupy')
        ->set("groupScoring.{$group->id}.custom", true)
        ->set("groupScoring.{$group->id}.per_set", '1');
    expect($points()->all())->toMatchArray([$joker->id => 5, $kalisz->id => 1]);

    Livewire::withQueryParams(['tab' => 'groups'])->test(TournamentPage::class, ['tournament' => $this->tournament])
        ->assertSeeText('plus 1 pkt za każdy wygrany set');

    $editor->set("groupScoring.{$group->id}.custom", false);
    expect($group->fresh()->scoring)->toBeNull()->and($points()->all())->toMatchArray([$joker->id => 3, $kalisz->id => 0]);
});

test('teams level on points are ordered by the chosen criteria, including the head-to-head mini table', function () {
    [$joker, $kalisz, $poznan] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $group = $joker->group;
    $pila = $category->participants()->create(['tournament_group_id' => $group->id, 'competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => 'MKS Piła'])->id]);
    $play = fn ($home, $away, int $homeSets, int $awaySets) => $category->matches()->create(['stage' => 'group', 'tournament_group_id' => $group->id, 'home_participant_id' => $home->id, 'away_participant_id' => $away->id, 'home_sets' => $homeSets, 'away_sets' => $awaySets]);
    // Joker i Kalisz po 4 pkt: Kalisz ma lepszy stosunek setów, ale Joker wygrał mecz bezpośredni.
    $play($joker, $kalisz, 2, 1);
    $play($joker, $poznan, 0, 2);
    $play($joker, $pila, 2, 1);
    $play($kalisz, $poznan, 2, 0);

    $order = fn (array $criteria, array $manual = []) => TournamentTable::for($group->participants()->get(), $category->matches()->get(), ['criteria' => $criteria], $manual)
        ->map(fn (array $row) => $row['participant']->id)->all();

    expect($order(['points', 'sets']))->toBe([$kalisz->id, $joker->id, $poznan->id, $pila->id])
        ->and($order(['points', 'h2h', 'sets']))->toBe([$joker->id, $kalisz->id, $poznan->id, $pila->id])
        ->and($order(['points', 'sets'], [$pila->id]))->toBe([$pila->id, $kalisz->id, $joker->id, $poznan->id]);
});

test('organizer sets the criteria order and moves teams by hand, and the matches tab explains it', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker, $kalisz, $poznan] = memorialGroup($this->tournament, $this->team);
    $group = $joker->group;
    $category = $joker->category;
    $category->matches()->create(['stage' => 'group', 'tournament_group_id' => $group->id, 'home_participant_id' => $joker->id, 'away_participant_id' => $kalisz->id, 'home_sets' => 2, 'away_sets' => 0, 'starts_at' => '2026-11-14 10:00']);

    $editor = Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])
        ->call('toggleCriterion', 'tournament', 'h2h')
        ->call('moveCriterion', 'tournament', 'h2h', -1)
        ->call('toggleCriterion', 'tournament', 'wins');
    expect($this->tournament->fresh()->scoring['criteria'])->toBe(['points', 'sets', 'h2h', 'small']);

    $table = fn () => TournamentBracket::for($category->fresh(['groups.participants', 'participants', 'matches', 'tournament']))->groupTable($group->fresh())
        ->map(fn (array $row) => $row['participant']->id)->all();
    expect($table()[0])->toBe($joker->id);

    $editor->set('section', 'grupy')->set('categoryId', $category->id)->call('moveTableRow', $group->id, $kalisz->id, -1);
    expect($table()[0])->toBe($kalisz->id)->and($group->fresh()->manual_order)->toHaveCount(3);

    Livewire::withQueryParams(['tab' => 'matches'])->test(TournamentPage::class, ['tournament' => $this->tournament->fresh()])
        ->assertSeeText('Zasady tabel grup')
        ->assertSeeText('Przy równej liczbie punktów: stosunek setów, mecze bezpośrednie (mała tabela), stosunek małych punktów.')
        ->assertSeeText('Kolejność ustalona przez organizatora');

    $editor->call('resetTableOrder', $group->id);
    expect($group->fresh()->manual_order)->toBeNull()->and($table()[0])->toBe($joker->id);
});

test('organizer marks a match as in progress by hand; its running score does not count until it is switched off', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker, $kalisz] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $match = $category->matches()->create(['stage' => 'group', 'tournament_group_id' => $joker->group->id, 'home_participant_id' => $joker->id, 'away_participant_id' => $kalisz->id, 'starts_at' => now()->subMinutes(10), 'streams' => [['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'label' => '']]]);
    $page = fn () => Livewire::withQueryParams(['tab' => 'matches'])->test(TournamentPage::class, ['tournament' => $this->tournament->fresh()]);

    $page()->assertDontSeeText('Trwa')->assertSeeText('Transmisja');

    $editor = Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])->set('section', 'mecze')->set('categoryId', $category->id)
        ->set("results.{$match->id}.live", true)
        ->set("results.{$match->id}.sets", '25:20, 20:25');
    expect($match->fresh()->is_live)->toBeTrue()->and($match->fresh()->isFinished())->toBeFalse()->and($match->fresh()->home_sets)->toBe(1);
    $page()->assertSeeText('Trwa');

    $editor->set("results.{$match->id}.sets", '25:20, 20:25, 15:10')->set("results.{$match->id}.live", false);
    expect($match->fresh()->isFinished())->toBeTrue()->and($match->fresh()->winner()->id)->toBe($joker->id);
});

test('closing the match editor while a field is still being saved does not lose the change or break the panel', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker, $kalisz, $poznan] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $first = $category->matches()->create(['stage' => 'group', 'tournament_group_id' => $joker->group->id, 'home_participant_id' => $joker->id, 'away_participant_id' => $kalisz->id]);
    $second = $category->matches()->create(['stage' => 'group', 'tournament_group_id' => $joker->group->id, 'home_participant_id' => $joker->id, 'away_participant_id' => $poznan->id]);

    Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])->set('section', 'mecze')->set('categoryId', $category->id)
        ->call('startMatchEdit', $first->id)
        ->set('editMatch.court', 'Boisko 2')
        ->call('finishMatchEdit')
        ->assertSet('editingMatchId', null)
        ->call('startMatchEdit', $second->id)
        ->assertSet('editingMatchId', $second->id)
        ->assertSee('Transmisja i zdjęcia');

    expect($first->fresh()->court)->toBe('Boisko 2');
});

test('organizer numbers matches by the schedule once, and later time changes keep the numbers', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker, $kalisz, $poznan] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $make = fn ($home, $away, ?string $time, ?string $court) => $category->matches()->create(['stage' => 'group', 'tournament_group_id' => $joker->group->id, 'home_participant_id' => $home->id, 'away_participant_id' => $away->id, 'starts_at' => $time, 'court' => $court]);
    $late = $make($joker, $kalisz, '2026-11-14 11:00', 'Boisko 1');
    $noTime = $make($kalisz, $poznan, null, null);
    $courtTwo = $make($joker, $poznan, '2026-11-14 09:00', 'Boisko 2');
    $courtOne = $make($poznan, $kalisz, '2026-11-14 09:00', 'Boisko 1');

    $editor = Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])->set('section', 'mecze')->set('categoryId', $category->id)
        ->call('renumberMatchesBySchedule');

    expect([$courtOne->fresh()->number, $courtTwo->fresh()->number, $late->fresh()->number, $noTime->fresh()->number])->toBe([1, 2, 3, 4]);

    $editor->set("results.{$courtOne->id}.starts_at", '2026-11-14T12:30');
    expect($courtOne->fresh()->number)->toBe(1);
});

test('organizer reorders group matches by dragging or arrows, and only that stage is renumbered', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker, $kalisz, $poznan] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $category->matches()->delete();
    $group = $category->groups()->value('id');
    $a = $category->matches()->create(['stage' => 'group', 'tournament_group_id' => $group, 'number' => 1, 'home_participant_id' => $joker->id, 'away_participant_id' => $kalisz->id]);
    $b = $category->matches()->create(['stage' => 'group', 'tournament_group_id' => $group, 'number' => 2, 'home_participant_id' => $joker->id, 'away_participant_id' => $poznan->id]);
    $c = $category->matches()->create(['stage' => 'group', 'tournament_group_id' => $group, 'number' => 3, 'home_participant_id' => $kalisz->id, 'away_participant_id' => $poznan->id]);
    $final = $category->matches()->create(['stage' => 'placement', 'placement' => 1, 'number' => 4]);

    $editor = Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])->set('section', 'mecze')
        ->call('moveMatch', (string) $c->id, 0);

    expect([$c->fresh()->number, $a->fresh()->number, $b->fresh()->number, $final->fresh()->number])->toBe([1, 2, 3, 4]);

    $editor->call('moveMatchBy', $c->id, 1);

    expect([$a->fresh()->number, $c->fresh()->number, $b->fresh()->number])->toBe([1, 2, 3]);
});

test('court is picked with the hall symbol and named courts get the symbol on the page', function () {
    expect(TrainingSession::venueForCourt('Boisko 1'))->toBe('court1')
        ->and(TrainingSession::venueForCourt(' boisko 3 '))->toBe('court3')
        ->and(TrainingSession::venueForCourt('Główne boisko'))->toBe('main')
        ->and(TrainingSession::venueForCourt('Sektor II'))->toBe('court2')
        ->and(TrainingSession::courtLabel('Boisko 3'))->toBe('Sektor III')
        ->and(TrainingSession::venueForCourt('Hala Lotnisko'))->toBeNull();

    $this->actingAs(User::factory()->admin()->create());
    [$joker, $kalisz] = memorialGroup($this->tournament, $this->team);
    $match = $joker->category->matches()->create(['stage' => 'group', 'tournament_group_id' => $joker->tournament_group_id, 'home_participant_id' => $joker->id, 'away_participant_id' => $kalisz->id]);

    Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])->set('section', 'mecze')
        ->assertSeeHtml('aria-label="Boisko"')
        ->set("results.{$match->id}.court", 'Sektor I');

    expect($match->fresh()->court)->toBe('Sektor I');
});

test('organizer swaps home and away in a match together with the set scores', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker, $kalisz] = memorialGroup($this->tournament, $this->team);
    $match = $joker->category->matches()->create(['stage' => 'group', 'tournament_group_id' => $joker->tournament_group_id, 'home_participant_id' => $joker->id, 'away_participant_id' => $kalisz->id, 'sets_score' => [[25, 20], [23, 25], [15, 12]], 'home_sets' => 2, 'away_sets' => 1]);

    Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])->set('section', 'mecze')
        ->call('swapMatchSides', $match->id)
        ->assertSet("results.{$match->id}.sets", '20:25, 25:23, 12:15');

    $match->refresh();
    expect([$match->home_participant_id, $match->away_participant_id, $match->home_sets, $match->away_sets])->toBe([$kalisz->id, $joker->id, 1, 2])
        ->and($match->sets_score)->toBe([[20, 25], [25, 23], [12, 15]]);
});

test('placement matches outside the bracket stand above it, and the bracket can be read in order', function () {
    [$joker, $kalisz, $poznan] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $category->matches()->create(['stage' => 'playoff', 'bracket_round' => 1, 'number' => 22, 'label' => 'Ćwierćfinał 1', 'home_participant_id' => $joker->id, 'away_participant_id' => $kalisz->id, 'court' => 'Sektor I', 'starts_at' => '2026-11-15 09:30']);
    $category->matches()->create(['stage' => 'placement', 'placement' => 9, 'number' => 21, 'home_placeholder' => '5. miejsce A', 'away_placeholder' => '5. miejsce B', 'court' => 'Sektor III', 'starts_at' => '2026-11-15 09:30']);

    Livewire::test(TournamentPage::class, ['tournament' => $this->tournament])
        ->call('selectTab', 'bracket')
        ->assertSeeHtml('Mecze o dalsze miejsca')->assertSeeHtml('id="mecz-21"')->assertSee('Ćwierćfinał 1')
        ->assertSee('Po kolei')
        ->assertSee('Sektory');
});

test('match times are arranged per sector every interval and a delay shifts the next matches on that sector', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker, $kalisz, $poznan] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $other = $this->tournament->categories()->create(['name' => 'Juniorki', 'display_order' => 1]);
    $base = ['stage' => 'group', 'tournament_group_id' => $joker->tournament_group_id, 'home_participant_id' => $joker->id, 'away_participant_id' => $kalisz->id];
    $first = $category->matches()->create($base + ['number' => 1, 'court' => 'Sektor III']);
    $second = $category->matches()->create($base + ['number' => 2, 'court' => 'Sektor II']);
    $third = $category->matches()->create($base + ['number' => 3, 'court' => 'Sektor III']);
    $fourth = $category->matches()->create($base + ['number' => 4, 'court' => 'Sektor III']);
    $finished = $category->matches()->create($base + ['number' => 5, 'court' => 'Sektor III', 'home_sets' => 2, 'away_sets' => 0]);
    $foreign = $other->matches()->create(['stage' => 'placement', 'placement' => 1, 'court' => 'Sektor III', 'starts_at' => '2026-11-14 15:00']);

    $editor = Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])->set('section', 'mecze')
        ->assertSet('scheduleDays', [['date' => '2026-11-14', 'time' => '09:00', 'from' => '1'], ['date' => '2026-11-15', 'time' => '09:00', 'from' => '']])
        ->assertSet('matchInterval', '1:30')
        ->set('matchInterval', '1:15')
        ->call('arrangeSchedule')
        ->assertSee('Cofnij');

    expect($category->fresh()->match_interval)->toBe(75);
    expect(collect([$first, $second, $third, $fourth])->map(fn ($m) => $m->fresh()->starts_at->format('H:i'))->all())->toBe(['09:00', '09:00', '10:15', '11:30'])
        ->and($finished->fresh()->starts_at)->toBeNull();

    // W dniu turnieju mecz #3 zaczyna się 20 minut później: #4 i mecz innej kategorii na Sektorze III też, Sektor II bez zmian.
    $this->travelTo('2026-11-14 10:00');
    $editor->set("results.{$third->id}.starts_at", '2026-11-14T10:35')
        ->assertSee('Sektor III: przesunięto 2 kolejne mecze o +20 min');

    expect($fourth->fresh()->starts_at->format('H:i'))->toBe('11:50')
        ->and($foreign->fresh()->starts_at->format('H:i'))->toBe('15:20')
        ->and($second->fresh()->starts_at->format('H:i'))->toBe('09:00')
        ->and($third->fresh()->delayMinutes())->toBe(20)
        ->and($fourth->fresh()->planned_starts_at->format('H:i'))->toBe('11:30');

    Livewire::test(TournamentPage::class, ['tournament' => $this->tournament])->call('selectTab', 'matches')->assertSee('opóźnienie 20 min');

    $editor->call('undoSchedule');
    expect($third->fresh()->starts_at->format('H:i'))->toBe('10:15')
        ->and($fourth->fresh()->starts_at->format('H:i'))->toBe('11:30')
        ->and($foreign->fresh()->starts_at->format('H:i'))->toBe('15:00')
        ->and($third->fresh()->delayMinutes())->toBeNull();

    // Bez zaznaczonego przesuwania zmienia się tylko jeden mecz.
    $editor->set('shiftFollowing', false)->set("results.{$third->id}.starts_at", '2026-11-14T10:05');
    expect($fourth->fresh()->starts_at->format('H:i'))->toBe('11:30');
});

test('changing a match time before the tournament day changes the plan without a delay', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker, $kalisz] = memorialGroup($this->tournament, $this->team);
    $match = $joker->category->matches()->create(['stage' => 'group', 'tournament_group_id' => $joker->tournament_group_id, 'home_participant_id' => $joker->id, 'away_participant_id' => $kalisz->id, 'court' => 'Sektor I', 'starts_at' => '2026-11-14 09:00']);
    $next = $joker->category->matches()->create(['stage' => 'group', 'tournament_group_id' => $joker->tournament_group_id, 'court' => 'Sektor I', 'starts_at' => '2026-11-14 10:30']);

    $this->travelTo('2026-11-01 12:00');
    Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])->set('section', 'mecze')
        ->set("results.{$match->id}.starts_at", '2026-11-14T09:30');

    expect($match->fresh()->delayMinutes())->toBeNull()
        ->and($next->fresh()->starts_at->format('H:i'))->toBe('11:00')
        ->and($next->fresh()->delayMinutes())->toBeNull();
});

test('each tournament day starts again from its first hour, and moving a match to another day shifts nothing', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker, $kalisz] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $base = ['stage' => 'group', 'tournament_group_id' => $joker->tournament_group_id, 'home_participant_id' => $joker->id, 'away_participant_id' => $kalisz->id];
    $matches = collect(range(1, 4))->map(fn ($number) => $category->matches()->create($base + ['number' => $number, 'court' => 'Sektor I']));

    $editor = Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])->set('section', 'mecze')
        ->set('scheduleDays.1.from', '3')
        ->set('scheduleDays.1.time', '10:00')
        ->call('arrangeSchedule');

    expect($matches->map(fn ($m) => $m->fresh()->starts_at->format('d.m H:i'))->all())->toBe(['14.11 09:00', '14.11 10:30', '15.11 10:00', '15.11 11:30'])
        ->and($category->fresh()->schedule_days[1])->toBe(['date' => '2026-11-15', 'time' => '10:00', 'from' => '3']);

    $editor->set("results.{$matches[1]->id}.starts_at", '2026-11-15T13:00');
    expect($matches[2]->fresh()->starts_at->format('d.m H:i'))->toBe('15.11 10:00');
});

test('a placement match can be moved before the bracket matches', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $quarter = $category->matches()->create(['stage' => 'playoff', 'bracket_round' => 1, 'number' => 21]);
    $ninth = $category->matches()->create(['stage' => 'placement', 'placement' => 9, 'number' => 22]);

    Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])->set('section', 'mecze')
        ->assertSee('Drabinka i mecze o miejsca')
        ->call('moveMatchBy', $ninth->id, -1);

    expect([$ninth->fresh()->number, $quarter->fresh()->number])->toBe([21, 22]);
});

test('bracket columns keep the match order from the panel', function () {
    [$joker] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $final = $category->matches()->create(['stage' => 'playoff', 'bracket_round' => 3, 'bracket_slot' => 1, 'decides_place' => 1, 'number' => 33]);
    $seventh = $category->matches()->create(['stage' => 'playoff', 'bracket_round' => 3, 'bracket_slot' => 4, 'decides_place' => 7, 'number' => 30]);

    expect(TournamentPage::boardColumns($category->matches()->get())->first()['matches']->pluck('number')->all())->toBe([30, 33]);
});

test('groups get default sectors, other matches go to the first free sector and nothing overlaps with other categories', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker, $kalisz] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $groupA = $joker->group;
    $groupB = $category->groups()->create(['name' => 'Grupa B', 'display_order' => 1]);
    $base = ['stage' => 'group', 'home_participant_id' => $joker->id, 'away_participant_id' => $kalisz->id];
    $a1 = $category->matches()->create($base + ['number' => 1, 'tournament_group_id' => $groupA->id]);
    $b1 = $category->matches()->create($base + ['number' => 2, 'tournament_group_id' => $groupB->id]);
    $a2 = $category->matches()->create($base + ['number' => 3, 'tournament_group_id' => $groupA->id]);
    $final = $category->matches()->create(['stage' => 'placement', 'placement' => 1, 'number' => 4]);
    $juniors = $this->tournament->categories()->create(['name' => 'Juniorki', 'display_order' => 1]);
    $busy = $juniors->matches()->create(['stage' => 'placement', 'placement' => 1, 'court' => 'Sektor I', 'starts_at' => '2026-11-14 09:00']);

    $editor = Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])->set('section', 'mecze')
        ->set("groupCourts.{$groupA->id}", 'Sektor III')
        ->set("groupCourts.{$groupB->id}", 'Sektor I')
        ->call('arrangeSchedule');

    $at = fn ($match) => $match->fresh()->court.' '.$match->fresh()->starts_at->format('H:i');
    expect($at($a1))->toBe('Sektor III 09:00')
        ->and($at($b1))->toBe('Sektor I 10:30')   // Sektor I zajęty do 10:30 przez juniorki
        ->and($at($a2))->toBe('Sektor III 10:30')
        ->and($at($final))->toBe('Sektor II 09:00'); // bez grupy: najwcześniej wolny sektor

    // Mecz #3 przechodzi na wolny Sektor II: jego miejsce na Sektorze III zwalnia się, a na Sektorze II finał ustępuje.
    $later = $category->matches()->create($base + ['number' => 5, 'tournament_group_id' => $groupA->id, 'court' => 'Sektor III', 'starts_at' => '2026-11-14 12:00']);
    $editor->set("results.{$a2->id}.court", 'Sektor II')->assertSee('przeszedł na Sektor II');
    expect($at($later))->toBe('Sektor III 10:30')
        ->and($at($final))->toBe('Sektor II 09:00');

    $editor->call('undoSchedule');
    expect($at($a2))->toBe('Sektor III 10:30')->and($at($later))->toBe('Sektor III 12:00');

    $editor->call('resetSchedule');
    expect($a1->fresh()->starts_at)->toBeNull()->and($a1->fresh()->court)->toBe('Sektor III')->and($busy->fresh()->starts_at)->not->toBeNull();

    $editor->call('undoSchedule');
    $this->get(route('admin.tournaments.edit', ['tournament' => $this->tournament, 'section' => 'mecze']))->assertOk()->assertSee('Obłożenie sektorów')->assertSee('nakłada się');
});

test('category colours are configurable and the sector occupancy switches between days', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $category->matches()->create(['stage' => 'placement', 'placement' => 1, 'court' => 'Sektor I', 'starts_at' => '2026-11-14 09:00']);
    $category->matches()->create(['stage' => 'placement', 'placement' => 3, 'court' => 'Sektor II', 'starts_at' => '2026-11-15 10:00']);

    expect($category->color())->toBe('#45a0e0');

    Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])->set('section', 'kategorie')
        ->call('setCategoryColor', $category->id, '#FF0000')
        ->call('setCategoryColor', $category->id, 'red;');

    expect($category->fresh()->color())->toBe('#ff0000');

    $this->get(route('admin.tournaments.edit', ['tournament' => $this->tournament, 'section' => 'mecze']))->assertOk()
        ->assertSee('#ff0000', false)
        ->assertSee('14.11')->assertSee('15.11')->assertSee('role="tablist"', false);
});

test('group matches skip a slot pointing to a deleted team', function () {
    $this->actingAs(User::factory()->admin()->create());
    $category = $this->tournament->categories()->create(['name' => 'Kadetki']);
    $group = $category->groups()->create(['name' => 'Grupa A']);
    $a = $category->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => 'MKS Kalisz'])->id]);
    $b = $category->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => 'Sparta Złotów'])->id]);
    $group->update(['slots' => ["participant:{$a->id}", "participant:{$b->id}", 'participant:99999']]);

    Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])
        ->call('generateGroupMatches', $group->id)
        ->assertHasNoErrors();

    expect($group->matches()->count())->toBe(1);

    Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])->call('deleteParticipant', $b->id);
    expect($group->fresh()->slots)->toBe(["participant:{$a->id}", 'participant:99999']);
});

test('swapping a team keeps its group, matches and times', function () {
    $this->actingAs(User::factory()->admin()->create());
    $category = $this->tournament->categories()->create(['name' => 'Kadetki']);
    $group = $category->groups()->create(['name' => 'Grupa A']);
    $old = $category->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => 'MKS Kalisz'])->id, 'tournament_group_id' => $group->id, 'roster' => 'Anna']);
    $other = $category->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => 'Sparta Złotów'])->id, 'tournament_group_id' => $group->id]);
    $match = $group->matches()->create(['tournament_category_id' => $category->id, 'stage' => 'group', 'home_participant_id' => $old->id, 'away_participant_id' => $other->id, 'starts_at' => '2026-11-06 10:00:00', 'court' => 'court1']);
    $new = RivalTeam::create(['full_name' => 'Orzeł Trzcianka']);

    Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])
        ->call('startSwap', $old->id)
        ->set('swapWith', 'rival:'.$new->id)
        ->call('swapTeam')
        ->assertHasNoErrors();

    expect($old->fresh())->competitor_id->toBe($new->id)->tournament_group_id->toBe($group->id)->roster->toBeNull()
        ->and($old->fresh()->name())->toBe('Orzeł Trzcianka')
        ->and($match->fresh())->home_participant_id->toBe($old->id)->court->toBe('court1')
        ->and($match->fresh()->starts_at->format('Y-m-d H:i'))->toBe('2026-11-06 10:00');
});

test('a corrected result clears the earlier set format error', function () {
    $this->actingAs(User::factory()->admin()->create());
    $category = $this->tournament->categories()->create(['name' => 'Kadetki']);
    $match = $category->matches()->create(['stage' => 'placement', 'placement' => 1]);

    Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])
        ->set("results.{$match->id}.sets", '25:21, 21:25, 25:15, 1')
        ->assertHasErrors("results.{$match->id}.sets")
        ->set("results.{$match->id}.sets", '25:21, 21:25, 25:15, 15:12')
        ->assertHasNoErrors();

    expect($match->fresh())->home_sets->toBe(3)->away_sets->toBe(1);
});

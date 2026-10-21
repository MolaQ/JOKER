<?php

use App\Livewire\Admin\TournamentEditor;
use App\Livewire\TournamentPage;
use App\Models\RivalTeam;
use App\Models\Season;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentParticipant;
use App\Models\User;
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
        ->call('selectTab', 'grupy')
        ->assertSee('Grupa A')
        ->call('selectTab', 'informator')
        ->assertSee('Hala Lotnisko')
        ->call('selectCategory', $this->tournament->categories()->where('name', 'Młodziczki')->value('id'))
        ->assertDontSee('UKS Dwójka Kalisz');
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
        ->call('selectTab', 'drabinka')
        ->assertSee('Drabinka o miejsca 5–8')
        ->assertSeeText('15.11, 15:30');
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

    Livewire::withQueryParams(['zakladka' => 'drabinka'])->test(TournamentPage::class, ['tournament' => $this->tournament])
        ->assertSeeInOrder(['Półfinał', 'Finały', 'LOMS Joker Piła', 'Mecz o trzecie miejsce']);
});

test('bracket matches stand in the chosen column and row, and any match can be edited', function () {
    $this->actingAs(User::factory()->admin()->create());
    [$joker, $kalisz] = memorialGroup($this->tournament, $this->team);
    $category = $joker->category;
    $category->matches()->create(['stage' => 'playoff', 'bracket_round' => 1, 'bracket_slot' => 1, 'home_placeholder' => '1. gr. A', 'away_placeholder' => '2. gr. B']);
    $fifth = $category->matches()->create(['stage' => 'playoff', 'bracket_round' => 3, 'bracket_slot' => 3, 'decides_place' => 5, 'home_placeholder' => 'Przegrany 1', 'away_placeholder' => 'Przegrany 2']);
    $group = $category->matches()->create(['stage' => 'group', 'tournament_group_id' => $joker->tournament_group_id, 'home_participant_id' => $joker->id, 'away_participant_id' => $kalisz->id]);

    Livewire::withQueryParams(['zakladka' => 'drabinka'])->test(TournamentPage::class, ['tournament' => $this->tournament])
        ->assertSee('grid-column: 2; grid-row: 4', false)
        ->assertSee('Mecz o 5. miejsce');

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

<?php

use App\Livewire\Admin\TournamentEditor;
use App\Models\RivalTeam;
use App\Models\Season;
use App\Models\Tournament;
use App\Models\TournamentTemplate;
use App\Models\User;
use App\Support\SiteSettings;
use Livewire\Livewire;

beforeEach(function () {
    Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $this->tournament = Tournament::create([
        'name' => 'Memoriał', 'start_date' => '2026-11-14', 'end_date' => '2026-11-15',
        'city' => 'Piła', 'is_organizer' => true, 'series' => Tournament::SERIES_MEMORIAL, 'has_page' => true,
    ]);
});

function templateRival(string $name): int
{
    return RivalTeam::create(['full_name' => $name])->id;
}

test('a category saved as a template is rebuilt in a new category and fills in as teams join groups', function () {
    $this->actingAs(User::factory()->admin()->create());
    $source = $this->tournament->categories()->create(['name' => 'Kadetki', 'match_interval' => 75]);
    $group = $source->groups()->create(['name' => 'Grupa A', 'court' => 'court2']);
    [$a, $b, $c] = collect(['MKS Kalisz', 'Sparta Złotów', 'Orzeł Trzcianka'])->map(fn ($name) => $source->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => templateRival($name), 'tournament_group_id' => $group->id]))->all();
    $m1 = $group->matches()->create(['tournament_category_id' => $source->id, 'stage' => 'group', 'home_participant_id' => $a->id, 'away_participant_id' => $b->id, 'starts_at' => '2026-11-14 10:00', 'court' => 'court2', 'home_sets' => 2, 'away_sets' => 0]);
    $group->matches()->create(['tournament_category_id' => $source->id, 'stage' => 'group', 'home_participant_id' => $b->id, 'away_participant_id' => $c->id, 'starts_at' => '2026-11-14 11:15']);
    $source->matches()->create(['stage' => 'placement', 'placement' => 1, 'home_source' => "group:{$group->id}:1", 'away_source' => "winner:{$m1->id}", 'starts_at' => '2026-11-15 12:00', 'court' => 'main']);

    $editor = Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])
        ->call('startTemplate', $source->id)
        ->set('templateName', '3 drużyny + finał')
        ->set('templateDescription', 'Jedna grupa i mecz o 1. miejsce')
        ->call('saveTemplate')
        ->assertHasNoErrors();

    $template = TournamentTemplate::sole();
    expect($template->summary())->toBe('1 grupa · 3 mecze · 2 dni');

    $editor->set('newCategory', 'Kadeci')->set('newCategoryTemplate', (string) $template->id)->call('addCategory');

    $copy = $this->tournament->categories()->where('name', 'Kadeci')->sole();
    $newGroup = $copy->groups()->sole();
    $matches = $copy->matches()->orderBy('number')->get();

    expect($copy->match_interval)->toBe(75)
        ->and($newGroup->court)->toBe('court2')
        ->and($matches)->toHaveCount(3)
        ->and($matches[0]->home_source)->toBe("slot:{$newGroup->id}:1")
        ->and($matches[0]->home_sets)->toBeNull()
        ->and($matches[0]->starts_at->format('Y-m-d H:i'))->toBe('2026-11-14 10:00')
        ->and($matches[2]->home_source)->toBe("group:{$newGroup->id}:1")
        ->and($matches[2]->away_source)->toBe("winner:{$matches[0]->id}")
        ->and($matches[2]->starts_at->format('Y-m-d H:i'))->toBe('2026-11-15 12:00');

    $x = $copy->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => templateRival('Gwardia Wrocław')]);
    $y = $copy->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => templateRival('Chemik Police')]);
    $editor->set('categoryId', $copy->id)
        ->set("participants.{$x->id}.group", (string) $newGroup->id)
        ->set("participants.{$y->id}.group", (string) $newGroup->id);

    $first = $matches[0]->fresh();
    expect($first->home_participant_id)->toBe($x->id)
        ->and($first->away_participant_id)->toBe($y->id)
        ->and($first->home_source)->toBeNull()
        ->and($matches[1]->fresh()->home_participant_id)->toBe($y->id)
        ->and($matches[1]->fresh()->away_source)->toBe("slot:{$newGroup->id}:3");

    $editor->call('generateGroupMatches', $newGroup->id);
    expect($copy->matches()->where('tournament_group_id', $newGroup->id)->count())->toBe(2);
});

test('the tv screen rotates through category slides and links to the next one', function () {
    $category = $this->tournament->categories()->create(['name' => 'Kadetki']);
    $group = $category->groups()->create(['name' => 'Grupa A']);
    $a = $category->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => templateRival('MKS Kalisz'), 'tournament_group_id' => $group->id]);
    $b = $category->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => templateRival('Sparta Złotów'), 'tournament_group_id' => $group->id]);
    $group->matches()->create(['tournament_category_id' => $category->id, 'stage' => 'group', 'home_participant_id' => $a->id, 'away_participant_id' => $b->id, 'starts_at' => '2026-11-14 10:00', 'is_live' => true, 'home_sets' => 1, 'away_sets' => 0]);

    $this->get(route('tournaments.tv', $this->tournament))->assertOk()
        ->assertSee('Tabela')->assertSee('MKS Kalisz')->assertSee('Teraz gramy')->assertSee('plansza 1/2');

    $this->get(route('tournaments.tv', [$this->tournament, 's' => 1]))->assertOk()->assertSee('Najbliższe mecze · Grupa A');

    app(SiteSettings::class)->set(['tv_show_tables' => '0']);
    $this->get(route('tournaments.tv', $this->tournament))->assertOk()->assertSee('Najbliższe mecze')->assertSee('plansza 1/1');
});

test('a template can keep the teams and seats them in the same groups and matches', function () {
    $this->actingAs(User::factory()->admin()->create());
    $source = $this->tournament->categories()->create(['name' => 'Kadetki']);
    $group = $source->groups()->create(['name' => 'Grupa A']);
    [$a, $b] = collect(['MKS Kalisz', 'Sparta Złotów'])->map(fn ($name) => $source->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => templateRival($name), 'tournament_group_id' => $group->id]))->all();
    $group->matches()->create(['tournament_category_id' => $source->id, 'stage' => 'group', 'home_participant_id' => $b->id, 'away_participant_id' => $a->id]);

    $editor = Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])
        ->call('startTemplate', $source->id)
        ->set('templateName', 'Stały skład')
        ->set('templateTeams', true)
        ->call('saveTemplate');

    $template = TournamentTemplate::sole();
    expect($template->summary())->toBe('1 grupa · 1 mecz · 2 dni · 2 drużyny');

    $editor->set('newCategory', 'Kadetki 2027')->set('newCategoryTemplate', (string) $template->id)->call('addCategory');

    $copy = $this->tournament->categories()->where('name', 'Kadetki 2027')->sole();
    $match = $copy->matches()->sole();

    expect($copy->participants)->toHaveCount(2)
        ->and($copy->participants->pluck('tournament_group_id')->unique()->all())->toBe([$copy->groups()->sole()->id])
        ->and($match->home->competitor_id)->toBe($b->competitor_id)
        ->and($match->away->competitor_id)->toBe($a->competitor_id);
});

test('clearing results needs the category name, keeps the matches and can be restored', function () {
    $this->actingAs(User::factory()->admin()->create());
    $category = $this->tournament->categories()->create(['name' => 'Kadetki']);
    $group = $category->groups()->create(['name' => 'Grupa A']);
    [$a, $b] = collect(['MKS Kalisz', 'Sparta Złotów'])->map(fn ($name) => $category->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => templateRival($name), 'tournament_group_id' => $group->id, 'final_position' => 1]))->all();
    $match = $group->matches()->create(['tournament_category_id' => $category->id, 'stage' => 'group', 'home_participant_id' => $a->id, 'away_participant_id' => $b->id, 'starts_at' => '2026-11-14 10:00', 'sets_score' => [[25, 20], [25, 18]], 'home_sets' => 2, 'away_sets' => 0]);

    $editor = Livewire::test(TournamentEditor::class, ['tournament' => $this->tournament])
        ->set('categoryId', $category->id)
        ->set('clearConfirm', 'Kadeci')
        ->call('clearResults')
        ->assertHasErrors('clearConfirm');
    expect($match->fresh()->home_sets)->toBe(2);

    $editor->set('clearConfirm', 'kadetki ')->call('clearResults')->assertHasNoErrors();
    $fresh = $match->fresh();
    expect($fresh->sets_score)->toBeNull()
        ->and($fresh->home_sets)->toBeNull()
        ->and($fresh->home_participant_id)->toBe($a->id)
        ->and($fresh->starts_at->format('H:i'))->toBe('10:00')
        ->and($a->fresh()->final_position)->toBeNull();

    $backup = $category->resultBackups()->sole();
    expect($backup->results_count)->toBe(1);

    $editor->call('restoreResults', $backup->id);
    expect($match->fresh()->sets_score)->toBe([[25, 20], [25, 18]])
        ->and($match->fresh()->home_sets)->toBe(2)
        ->and($a->fresh()->final_position)->toBe(1);

    $editor->call('deleteResultBackup', $backup->id);
    expect($category->resultBackups()->count())->toBe(0);
});

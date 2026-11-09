<?php

use App\Livewire\Admin\DuplicatePlayerReview;
use App\Livewire\Admin\PlayerManager;
use App\Models\Like;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TrashItem;
use App\Models\User;
use App\Support\Approvals;
use App\Support\PlayerMerge;
use Livewire\Livewire;

test('dwa wpisy tej samej osoby scalają się w jeden profil z nagrodami, drużynami i serduszkami', function () {
    $season = Season::factory()->create(['is_current' => true]);
    $seniors = Team::factory()->create(['name' => 'Seniorzy']);
    $juniors = Team::factory()->create(['name' => 'Juniorzy II']);
    $keep = Player::create(['first_name' => 'Dawid', 'last_name' => 'Lisowski', 'team_id' => $seniors->id, 'jersey_number' => 71]);
    $keep->forceFill(['views_count' => 10])->save();
    $keep->teams()->attach($seniors->id, ['season_id' => $season->id, 'jersey_number' => 71]);
    $duplicate = Player::create(['first_name' => 'Dawid', 'last_name' => 'Lisowski ', 'team_id' => $juniors->id, 'jersey_number' => 5, 'height' => 190]);
    $duplicate->forceFill(['views_count' => 13])->save();
    $duplicate->teams()->attach($juniors->id, ['season_id' => $season->id, 'jersey_number' => 5]);

    $tournament = Tournament::create(['name' => 'Memoriał', 'start_date' => '2026-11-07', 'city' => 'Piła']);
    $award = $tournament->categories()->create(['name' => 'Junior'])->awards()->create(['name' => 'MVP turnieju', 'player_id' => $duplicate->id]);
    $award->syncAchievement();
    $fan = User::factory()->create();
    Like::create(['user_id' => $fan->id, 'likeable_type' => Player::class, 'likeable_id' => $duplicate->id]);
    Like::create(['user_id' => $fan->id, 'likeable_type' => Player::class, 'likeable_id' => $keep->id]);
    $oldUrl = route('players.show', $duplicate);

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(PlayerManager::class)
        ->set('search', 'Lisow')->assertSee('duplikat?')
        ->call('openEditModal', $keep->id)->assertSee('Ta sama osoba jest wpisana jeszcze raz')
        ->call('mergeDuplicate', $duplicate->id)
        ->assertDontSee('Ta sama osoba jest wpisana jeszcze raz');

    $keep->refresh();
    expect(Player::find($duplicate->id))->toBeNull()
        ->and(TrashItem::where('type', 'player')->where('item_id', $duplicate->id)->exists())->toBeTrue()
        ->and($keep->teams()->wherePivot('season_id', $season->id)->pluck('teams.name')->sort()->values()->all())->toBe(['Juniorzy II', 'Seniorzy'])
        ->and($keep->teams()->where('teams.id', $juniors->id)->first()->pivot->jersey_number)->toBe(5)
        ->and($keep->awards()->pluck('name')->all())->toBe(['MVP turnieju'])
        ->and($award->fresh()->player_id)->toBe($keep->id)
        ->and($keep->height)->toBe(190)
        ->and($keep->views_count)->toBe(23)
        ->and(Like::where('likeable_type', Player::class)->where('likeable_id', $keep->id)->count())->toBe(1);

    $this->get(route('players.show', $keep))->assertOk()->assertSee('MVP turnieju');
    // Stary adres duplikatu prowadzi do scalonego profilu.
    $response = $this->get($oldUrl);
    $response->isRedirect() ? $response->assertRedirect(route('players.show', $keep)) : $response->assertOk()->assertSee('MVP turnieju');
});

test('możliwe duble zawodników są w „Do zatwierdzenia”: zostaw jeden albo oznacz jako różne osoby', function () {
    $admin = User::factory()->admin()->create();
    $a = Player::create(['first_name' => 'Dawid', 'last_name' => 'Lisowski']);
    $b = Player::create(['first_name' => 'dawid', 'last_name' => 'Lisowski']);
    $x = Player::create(['first_name' => 'Jan', 'last_name' => 'Kowalski']);
    $y = Player::create(['first_name' => 'Jan', 'last_name' => 'Kowalski']);

    expect(Approvals::sectionsFor($admin)->get('duble-zawodnikow')['count'])->toBe(2);
    $this->actingAs($admin)->get(route('admin.duplicate-players.index'))->assertOk()->assertSee('Możliwe duble zawodników')->assertSee('Lisowski');

    Livewire::test(DuplicatePlayerReview::class)
        ->call('keep', $a->id.','.$b->id, $a->id)
        ->call('dismiss', $x->id.','.$y->id)
        ->assertSee('Nie ma możliwych dubli');

    expect(Player::find($b->id))->toBeNull()->and(Player::whereKey([$x->id, $y->id])->count())->toBe(2)
        ->and(PlayerMerge::groups())->toBeEmpty();
});

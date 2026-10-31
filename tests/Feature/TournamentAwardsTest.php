<?php

use App\Livewire\Admin\PlayerManager;
use App\Livewire\Admin\TournamentAwards;
use App\Models\Player;
use App\Models\PlayerAchievement;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tournament = Tournament::factory()->create(['name' => 'Memoriał Winklera', 'edition' => null, 'season_id' => null, 'start_date' => '2026-11-14', 'end_date' => '2026-11-15', 'has_page' => true]);
    $this->category = $this->tournament->categories()->create(['name' => 'Kadetki']);
    $this->team = Team::factory()->create();
    $this->player = Player::factory()->create(['team_id' => $this->team->id, 'first_name' => 'Ola', 'last_name' => 'Nowak']);
    $this->actingAs(User::factory()->admin()->create());
});

test('an award for a club player lands in her achievements and disappears with the award', function () {
    Livewire::test(TournamentAwards::class, ['categoryId' => $this->category->id])
        ->set('award.name', 'MVP turnieju')->set('award.player', (string) $this->player->id)
        ->call('save')->assertHasNoErrors();

    $achievement = PlayerAchievement::sole();
    expect($achievement->player_id)->toBe($this->player->id)
        ->and($achievement->title)->toContain('MVP turnieju')->toContain('Memoriał Winklera')
        ->and($achievement->season)->toBe('2026/27');

    $this->get(route('players.show', $this->player))->assertSee('MVP turnieju');
    $this->get(route('tournaments.show', ['tournament' => $this->tournament, 'tab' => 'ranking']))->assertSee('Wyróżnienia')->assertSee('Ola Nowak');

    Livewire::test(TournamentAwards::class, ['categoryId' => $this->category->id])->call('delete', $this->category->awards()->sole()->id);
    expect(PlayerAchievement::count())->toBe(0);
});

test('an award for a rival player is only a name and needs one', function () {
    Livewire::test(TournamentAwards::class, ['categoryId' => $this->category->id])
        ->set('award.name', '__custom')->set('award.custom', 'Najlepsza debiutantka')->call('save')->assertHasErrors('award.person')
        ->set('award.person', 'Anna Kowalska')->call('save')->assertHasNoErrors();

    expect($this->category->awards()->sole()->personLabel())->toBe('Anna Kowalska')->and(PlayerAchievement::count())->toBe(0);
});

test('saving a player in the panel keeps achievements that came from tournaments', function () {
    Livewire::test(TournamentAwards::class, ['categoryId' => $this->category->id])
        ->set('award.name', 'MVP turnieju')->set('award.player', (string) $this->player->id)->call('save');

    Livewire::test(PlayerManager::class)->call('openEditModal', $this->player->id)
        ->call('addAchievement')->set('achievements.0.title', 'Mistrzostwo Wielkopolski')->call('save');

    expect($this->player->achievements()->pluck('title')->all())->toHaveCount(2);
});

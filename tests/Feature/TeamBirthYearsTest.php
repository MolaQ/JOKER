<?php

use App\Livewire\Admin\TeamList;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Support\DataImport;
use Livewire\Livewire;

test('drużyna może mieć kilka roczników', function () {
    $team = Team::create(['name' => 'Juniorzy młodsi', 'slug' => 'juniorzy-mlodsi', 'category' => 'junior_mlodszy']);
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(TeamList::class)
        ->call('openEditModal', $team->id)
        ->set('birth_year', 2010)->set('birth_year_to', 2009)
        ->call('save')->assertHasErrors('birth_year_to')
        ->set('birth_year_to', 2011)
        ->call('save')->assertHasNoErrors();

    $team->refresh();
    expect($team->birthYears())->toBe('2010–2011')
        ->and($team->birthYearsLabel())->toBe('Roczniki 2010–2011');

    $this->get(route('teams.show', $team))->assertSee('Roczniki 2010–2011');

    $team->update(['birth_year_to' => null]);
    expect($team->birthYearsLabel())->toBe('Rocznik 2010');
});

test('import przyjmuje zakres roczników', function () {
    $rows = DataImport::parse('teams', "nazwa;kategoria;rocznik\nKadeci;juniorzy młodsi;2010-2011\nMłodzicy;młodzicy;dwa tysiące\n");

    expect($rows[0]['errors'])->toBe([])->and($rows[1]['errors'])->not->toBe([]);
});

test('zawodnik z młodszego rocznika jest oznaczony w juniorach, ale nie w seniorach', function () {
    $season = Season::factory()->create(['is_current' => true]);
    $cadets = Team::factory()->create(['category' => 'junior_mlodszy', 'birth_year' => 2011]);
    $juniors = Team::factory()->create(['category' => 'junior', 'birth_year' => 2009]);
    $seniors = Team::factory()->create(['category' => 'senior', 'birth_year' => null]);
    $player = Player::factory()->create(['team_id' => $cadets->id, 'is_active' => true]);
    $juniors->seasonRoster($season->id)->attach($player->id);
    $seniors->seasonRoster($season->id)->attach($player->id);

    $this->get(route('teams.show', $juniors))->assertSee('z młodszego rocznika');
    $this->get(route('teams.show', $seniors))->assertSee($player->last_name)->assertDontSee('z młodszego rocznika');
});

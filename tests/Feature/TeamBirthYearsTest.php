<?php

use App\Livewire\Admin\TeamList;
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

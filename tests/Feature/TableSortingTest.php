<?php

use App\Livewire\Admin\PlayerManager;
use App\Livewire\Admin\UserManager;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

test('kliknięcie nagłówka sortuje listę w panelu, drugie odwraca, trzecie wraca do domyślnej kolejności', function () {
    $this->actingAs(User::factory()->admin()->create(['name' => 'Admin Zet']));
    User::factory()->create(['name' => 'Adam Alfa', 'email' => 'zz@example.com']);
    User::factory()->create(['name' => 'Bartek Beta', 'email' => 'aa@example.com']);

    Livewire::test(UserManager::class)
        ->assertSeeInOrder(['Adam Alfa', 'Bartek Beta'])
        ->assertSeeHtml('wire:click="sortBy(\'email\')"')
        ->call('sortBy', 'email')
        ->assertSet('sortField', 'email')
        ->assertSet('sortDirection', 'asc')
        ->assertSeeInOrder(['Bartek Beta', 'Adam Alfa'])
        ->call('sortBy', 'email')
        ->assertSet('sortDirection', 'desc')
        ->assertSeeInOrder(['Adam Alfa', 'Bartek Beta'])
        ->call('sortBy', 'email')
        ->assertSet('sortField', '')
        ->call('sortBy', 'haslo')
        ->assertSet('sortField', '');
});

test('zawodników można posortować po drużynie, nazwisku i numerze', function () {
    $this->actingAs(User::factory()->admin()->create());
    $first = Team::factory()->create(['name' => 'Kadeci', 'display_order' => 1]);
    $second = Team::factory()->create(['name' => 'Juniorzy', 'display_order' => 2]);
    Player::factory()->create(['team_id' => $second->id, 'last_name' => 'Abacki', 'jersey_number' => 3]);
    Player::factory()->create(['team_id' => $first->id, 'last_name' => 'Zawada', 'jersey_number' => 12]);

    Livewire::test(PlayerManager::class)
        ->call('sortBy', 'nazwisko')->assertSeeInOrder(['Abacki', 'Zawada'])
        ->call('sortBy', 'druzyna')->assertSeeInOrder(['Zawada', 'Abacki'])
        ->call('sortBy', 'numer')->assertSeeInOrder(['Abacki', 'Zawada']);
});

<?php

use App\Livewire\Admin\PlayerManager;
use App\Models\Player;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->full = Player::create(['first_name' => 'Pełny', 'last_name' => 'Profilowy', 'birth_date' => '2008-11-21', 'height' => 190, 'weight' => 80, 'bio' => '<p>Opis</p>', 'is_active' => true]);
    $this->full->awards()->create(['name' => 'MVP', 'event' => 'Memoriał', 'display_order' => 0]);
    $this->empty = Player::create(['first_name' => 'Pusty', 'last_name' => 'Brakowy', 'birth_year' => 2011, 'bio' => '', 'is_active' => false]);
});

test('admins filter players by missing data, awards, birthday month and birth year', function () {
    $component = Livewire::actingAs($this->admin)->test(PlayerManager::class);

    $component->set('filters.waga', 'nie')->assertSee('Brakowy')->assertDontSee('Profilowy')
        ->set('filters.waga', 'tak')->assertSee('Profilowy')->assertDontSee('Brakowy')
        ->call('clearFilters')
        ->set('filters.nagrody', 'tak')->assertSee('Profilowy')->assertDontSee('Brakowy')
        ->set('filters.nagrody', '')->set('filters.urodzenie', 'nie')->assertSee('Brakowy')->assertDontSee('Profilowy')
        ->set('filters.urodzenie', '')->set('filters.opis', 'nie')->assertSee('Brakowy')->assertDontSee('Profilowy')
        ->set('filters.opis', '')->set('filters.aktywny', 'nie')->assertSee('Brakowy')->assertDontSee('Profilowy')
        ->call('clearFilters')
        ->set('birthMonth', '11')->assertSee('Profilowy')->assertSee('21.11.2008')->assertDontSee('Brakowy')
        ->set('birthMonth', '')->set('yearFrom', '2010')->assertSee('Brakowy')->assertDontSee('Profilowy')
        ->set('yearTo', '2010')->assertDontSee('Brakowy')
        ->call('clearYears')->set('filters.rocznik', 'nie')->assertDontSee('Brakowy')->assertDontSee('Profilowy');

    expect($component->instance()->activeFiltersCount())->toBe(1);
});

test('the filter panel shows how many players miss each field', function () {
    Livewire::actingAs($this->admin)->test(PlayerManager::class)
        ->set('showFilters', true)
        ->assertSee('Zasięg w bloku')->assertSee('Nagrody indywidualne')
        ->assertViewHas('missingCounts', fn ($counts) => $counts['waga'] >= 1 && $counts['nagrody'] >= 1);
});

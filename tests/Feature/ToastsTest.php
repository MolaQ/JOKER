<?php

use App\Livewire\Admin\SeasonManager;
use App\Livewire\Admin\SiteSettingsManager;
use App\Livewire\Admin\TeamList;
use App\Livewire\Admin\UserManager;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Support\SiteSettings;
use App\UserRole;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

test('adding a season shows a success toast instead of a banner', function () {
    $this->actingAs($this->admin);

    Livewire::test(SeasonManager::class)
        ->call('openCreateModal')
        ->set('name', '2026/2027')
        ->set('start_date', '2026-09-01')
        ->set('end_date', '2027-06-30')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Sezon został dodany.', type: 'success')
        ->assertDontSee('Sezon został dodany.');

    expect(Season::where('name', '2026/2027')->exists())->toBeTrue();
});

test('deleting a team with players shows an error toast', function () {
    $this->actingAs($this->admin);

    $team = Team::factory()->create();
    Player::factory()->create(['team_id' => $team->id]);

    Livewire::test(TeamList::class)
        ->call('delete', $team->id)
        ->assertDispatched('toast', message: 'Nie można usunąć drużyny, która ma przypisanych zawodników.', type: 'error');

    expect($team->fresh())->not->toBeNull();
});

test('admin cannot delete own account and gets an error toast', function () {
    $this->actingAs($this->admin);

    Livewire::test(UserManager::class)
        ->call('delete', $this->admin->id)
        ->assertDispatched('toast', type: 'error', message: 'Nie możesz usunąć własnego konta.');
});

test('the admin layout shows session messages as toasts and includes the confirm modal', function () {
    $this->actingAs($this->admin)
        ->withSession(['success' => 'Import zakończony powodzeniem'])
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-toast-stack', false)
        ->assertSee('data-confirm-modal', false)
        ->assertSee('Import zakończony powodzeniem');
});

test('the public layout shows session errors as toasts', function () {
    $this->withSession(['error' => 'Komentarze są wyłączone'])
        ->get('/')
        ->assertOk()
        ->assertSee('data-toast-stack', false)
        ->assertSee('data-confirm-modal', false)
        ->assertSee('Komentarze są wyłączone')
        ->assertSee('&quot;type&quot;:&quot;error&quot;', false);
});

test('the notifications settings group is shown, saved and used by the toasts', function () {
    $this->actingAs($this->admin);

    Livewire::test(SiteSettingsManager::class)
        ->call('selectTab', 'powiadomienia')
        ->assertSee('Powiadomienia (toasty)')
        ->assertSee('Pokaż przykładowy toast')
        ->set('values.toast_success_bg', '#123456')
        ->set('values.toast_success_title', 'Super!')
        ->set('values.toast_position', 'bottom-center')
        ->set('values.toast_seconds', '7')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-config', position: 'bottom-center', seconds: 7)
        ->assertDispatched('toast', message: 'Zapisano. Zmiany są już widoczne na stronie.');

    expect(app(SiteSettings::class)->get('toast_success_bg'))->toBe('#123456');

    $this->get('/')
        ->assertSee('#123456')
        ->assertSee('Super!')
        ->assertSee('bottom-center');
});

test('an unknown toast position is rejected', function () {
    $this->actingAs($this->admin);

    Livewire::test(SiteSettingsManager::class)
        ->set('values.toast_position', 'middle')
        ->call('save')
        ->assertHasErrors(['values.toast_position'])
        ->assertNotDispatched('toast');
});

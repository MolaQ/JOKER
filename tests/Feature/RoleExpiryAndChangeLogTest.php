<?php

use App\Livewire\Admin\AuditLogViewer;
use App\Livewire\Admin\RoleManager;
use App\Livewire\Admin\SiteSettingsManager;
use App\Livewire\Admin\UserManager;
use App\Models\AuditLog;
use App\Models\PermissionOverride;
use App\Models\RoleAssignment;
use App\Models\Sponsor;
use App\Models\User;
use App\Permission;
use App\UserRole;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(fn () => Carbon::setTestNow('2026-10-28 12:00'));
afterEach(fn () => Carbon::setTestNow());

test('rola z datą „ważne do” działa do końca tego dnia, potem znika', function () {
    $this->actingAs(User::factory()->admin()->create());
    $helper = User::factory()->create(['role' => [UserRole::Parent, UserRole::Trainer]]);

    $helper->setRoleExpiry(UserRole::Trainer, Carbon::parse('2026-11-30'));

    expect($helper->fresh()->hasRole(UserRole::Trainer))->toBeTrue()
        ->and($helper->fresh()->roleExpiries()['trainer']->format('d.m.Y H:i'))->toBe('30.11.2026 23:59')
        ->and(AuditLog::where('action', 'roles.expiry')->value('description'))->toContain('ważna do 30.11.2026');

    Carbon::setTestNow('2026-12-01 00:05');
    $after = User::find($helper->id);

    expect($after->roles()->all())->toBe([UserRole::Parent])
        ->and($after->hasPermission(Permission::Teams))->toBeFalse()
        ->and(User::withRole(UserRole::Trainer)->whereKey($helper->id)->exists())->toBeFalse();

    $this->artisan('joker:expire-roles')->assertSuccessful();

    expect(RoleAssignment::where('user_id', $helper->id)->count())->toBe(1)
        ->and(AuditLog::where('action', 'roles.expired')->value('description'))->toContain('Wygasła rola Trener');

    // Wygasłą rolę można nadać od nowa.
    $after->assignRole(UserRole::Trainer);
    expect($after->fresh()->hasRole(UserRole::Trainer))->toBeTrue();
});

test('w edycji konta ustawia się, zmienia i usuwa datę „ważne do” roli', function () {
    $this->actingAs(User::factory()->admin()->create());
    $helper = User::factory()->create(['role' => UserRole::Parent]);

    Livewire::test(UserManager::class)
        ->call('openEditModal', $helper->id)
        ->set('selectedRoles', ['parent', 'staff'])
        ->set('roleExpiries.staff', '2026-12-31')
        ->call('save')
        ->assertHasNoErrors();

    expect($helper->fresh()->roleExpiries())->toHaveKey('staff')->not->toHaveKey('parent');

    Livewire::test(UserManager::class)
        ->call('openEditModal', $helper->id)
        ->assertSet('roleExpiries.staff', '2026-12-31')
        ->set('roleExpiries.staff', '')
        ->call('save');

    expect($helper->fresh()->roleExpiries())->toBe([])
        ->and($helper->fresh()->hasRole(UserRole::Staff))->toBeTrue();

    Livewire::test(UserManager::class)
        ->call('openEditModal', $helper->id)
        ->set('roleExpiries.staff', '2020-01-01')
        ->call('save')
        ->assertHasErrors('roleExpiries.staff');
});

test('uprawnienie nadane osobno może mieć datę „ważne do”', function () {
    $this->actingAs(User::factory()->admin()->create());
    $parent = User::factory()->create(['role' => UserRole::Parent]);

    Livewire::test(RoleManager::class)
        ->call('selectUser', $parent->id)
        ->call('setOverride', Permission::Teams->value, 'grant')
        ->call('setOverrideExpiry', Permission::Teams->value, '2026-11-15');

    expect($parent->fresh()->hasPermission(Permission::Teams))->toBeTrue()
        ->and(PermissionOverride::where('user_id', $parent->id)->value('expires_at')->toDateString())->toBe('2026-11-15');

    Carbon::setTestNow('2026-11-16 08:00');
    expect(User::find($parent->id)->hasPermission(Permission::Teams))->toBeFalse();

    $this->artisan('joker:expire-roles')->assertSuccessful();
    expect(PermissionOverride::where('user_id', $parent->id)->exists())->toBeFalse();
});

test('dziennik zapisuje, kto dodał, zmienił i usunął treść, z wartościami przed i po', function () {
    $admin = User::factory()->admin()->create(['name' => 'Ala Admin']);
    $this->actingAs($admin);

    $sponsor = Sponsor::create(['name' => 'Piekarnia', 'tier' => 'gold', 'is_active' => true]);
    $sponsor->update(['name' => 'Piekarnia Kowalski', 'tier' => 'platinum']);
    $sponsor->delete();

    $entries = AuditLog::where('subject_type', $sponsor->getMorphClass())->orderBy('id')->get();

    expect($entries->pluck('action')->all())->toBe(['model.created', 'model.updated', 'model.deleted'])
        ->and($entries->every(fn (AuditLog $entry) => $entry->actor_id === $admin->id))->toBeTrue()
        ->and($entries[1]->description)->toBe('Zmieniono: sponsor „Piekarnia Kowalski”')
        ->and($entries[1]->changes())->toBe(['name' => ['Piekarnia', 'Piekarnia Kowalski'], 'tier' => ['gold', 'platinum']]);
});

test('zmiany bez zalogowanej osoby i hasła nie trafiają do dziennika jawnie', function () {
    Sponsor::create(['name' => 'Bez osoby', 'tier' => 'gold']);
    expect(AuditLog::count())->toBe(0);

    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->create();
    AuditLog::query()->delete();
    $user->update(['password' => 'nowe-haslo-123']);

    $changes = AuditLog::sole()->changes();
    expect($changes['password'])->toBe(['••••', '•••• (nowe)'])
        ->and(json_encode(AuditLog::sole()->details))->not->toContain('nowe-haslo');
});

test('dziennik widzi admin i prezes, inni nie; filtry działają', function () {
    $boss = User::factory()->boss()->create(['name' => 'Prezes']);
    $editor = User::factory()->editor()->create(['name' => 'Redaktorka']);

    $this->actingAs($editor)->get('/panel/audit-log')->assertForbidden();
    $this->actingAs($boss)->get('/panel/audit-log')->assertOk()->assertSee('Dziennik zmian');

    $this->actingAs($editor);
    Sponsor::create(['name' => 'Sklep Sportowy', 'tier' => 'silver']);
    $this->actingAs($boss);
    Sponsor::create(['name' => 'Hurtownia', 'tier' => 'gold']);
    $boss->fresh()->syncRoles([UserRole::Boss, UserRole::Parent]);

    Livewire::test(AuditLogViewer::class)
        ->assertSee('Sklep Sportowy')->assertSee('Hurtownia')
        ->set('actor', (string) $editor->id)
        ->assertSee('Sklep Sportowy')->assertDontSee('Hurtownia')
        ->call('resetFilters')
        ->set('search', 'hurtow')
        ->assertSee('Hurtownia')->assertDontSee('Sklep Sportowy')
        ->call('resetFilters')
        ->set('type', 'grupa:role')
        ->assertSee('Zmiana ról konta Prezes')->assertDontSee('Hurtownia')
        ->call('resetFilters')
        ->set('type', Sponsor::class)->set('action', 'model.created')
        ->assertSee('Hurtownia')->assertDontSee('Zmiana ról konta')
        ->set('from', now()->addDay()->toDateString())
        ->assertSee('Nic nie pasuje do filtrów.');
});

test('zmiana koloru w ustawieniach zapisuje się w dzienniku bez tekstu w kolumnie numeru rekordu', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(SiteSettingsManager::class)
        ->set('values.color_accent', '#ff0000')
        ->call('save')
        ->assertHasNoErrors();

    $log = AuditLog::where('description', 'like', '%color_accent%')->latest('id')->firstOrFail();

    expect($log->subject_id)->toBeNull()
        ->and($log->details['klucz'])->toBe('color_accent')
        ->and($log->changes())->toHaveKey('value');

    Livewire::test(AuditLogViewer::class)->assertOk();
});

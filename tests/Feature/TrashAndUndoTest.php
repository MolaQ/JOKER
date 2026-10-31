<?php

use App\Livewire\Admin\AuditLogViewer;
use App\Livewire\Admin\GalleryManager;
use App\Livewire\Admin\TournamentManager;
use App\Livewire\Admin\TrashBin;
use App\Livewire\Admin\UserManager;
use App\Models\AccessSnapshot;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\Gallery;
use App\Models\PermissionOverride;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TrashItem;
use App\Models\User;
use App\Permission;
use App\Support\SiteSettings;
use App\UserRole;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('a deleted account goes to the trash with its roles and comes back with them', function () {
    $user = User::factory()->create(['name' => 'Anna Pomyłka', 'role' => [UserRole::Trainer, UserRole::Parent]]);
    $team = Team::factory()->create(['trainer_id' => $user->id]);
    $article = Article::factory()->create(['author_id' => $user->id]);

    Livewire::test(UserManager::class)->call('delete', $user->id)->assertDispatched('toast', message: 'Konto trafiło do kosza. Przywrócisz je w: System → Kosz.');

    expect(User::find($user->id))->toBeNull()
        ->and(Article::find($article->id))->toBeNull()
        ->and($team->fresh()->trainer_id)->toBeNull();

    $item = TrashItem::sole();
    $this->get(route('admin.trash.index'))->assertOk()->assertSee('Anna Pomyłka');

    Livewire::test(TrashBin::class)->call('restore', $item->id);

    $back = User::find($user->id);
    expect($back->roles()->map->value->sort()->values()->all())->toBe(['parent', 'trainer'])
        ->and(Article::find($article->id))->not->toBeNull()
        ->and($team->fresh()->trainer_id)->toBe($user->id)
        ->and(TrashItem::count())->toBe(0)
        ->and(AuditLog::where('action', 'trash.restored')->exists())->toBeTrue();
});

test('a tournament comes back with its categories and the deleted record can be restored from the change log', function () {
    $tournament = Tournament::factory()->create(['name' => 'Memoriał']);
    $category = $tournament->categories()->create(['name' => 'Kadetki']);
    $category->groups()->create(['name' => 'A']);

    Livewire::test(TournamentManager::class)->call('delete', $tournament->id);
    expect(Tournament::count())->toBe(0);

    $entry = AuditLog::where('action', 'model.deleted')->where('subject_id', $tournament->id)->latest('id')->first();
    Livewire::test(AuditLogViewer::class)->assertSee('Przywróć z kosza')->call('restoreDeleted', TrashItem::sole()->id);

    expect(Tournament::find($tournament->id)->categories()->sole()->groups()->count())->toBe(1)
        ->and($entry)->not->toBeNull();
});

test('gallery photos stay on disk while in the trash and go away when the trash is emptied', function () {
    Storage::fake('public');
    $gallery = Gallery::create(['title' => 'Turniej w Pile', 'is_published' => true, 'event_date' => now()->toDateString()]);
    $photo = $gallery->photos()->create(['path' => 'galleries/x.jpg']);
    Storage::disk('public')->put('galleries/x.jpg', 'x');

    Livewire::test(GalleryManager::class)->call('deleteGallery', $gallery->id);
    Storage::disk('public')->assertExists('galleries/x.jpg');

    app(SiteSettings::class)->set(['trash_days' => '7']);
    $this->travel(8)->days();
    $this->artisan('model:prune', ['--model' => [TrashItem::class]])->assertSuccessful();

    expect(TrashItem::count())->toBe(0);
    Storage::disk('public')->assertMissing('galleries/x.jpg');
    expect($photo->fresh())->toBeNull();
});

test('restoring fails with a clear message when the address is already taken', function () {
    $user = User::factory()->create(['email' => 'jan@example.test']);
    Livewire::test(UserManager::class)->call('delete', $user->id);
    User::factory()->create(['email' => 'jan@example.test']);

    Livewire::test(TrashBin::class)->call('restore', TrashItem::sole()->id)->assertDispatched('toast', type: 'error');
    expect(TrashItem::count())->toBe(1);
});

test('the trash shows only the kinds the person manages', function () {
    $user = User::factory()->create(['name' => 'Konto Ukryte']);
    Livewire::test(UserManager::class)->call('delete', $user->id);

    $this->actingAs(User::factory()->editor()->create());
    Livewire::test(TrashBin::class)->assertDontSee('Konto Ukryte')->call('restore', TrashItem::sole()->id)->assertForbidden();
});

test('a change in the log can be undone while nobody changed it later', function () {
    $team = Team::factory()->create(['name' => 'Kadeci']);
    $team->update(['name' => 'Kadeci 2']);
    $entry = AuditLog::where('action', 'model.updated')->latest('id')->first();

    Livewire::test(AuditLogViewer::class)->assertSee('Cofnij')->call('undo', $entry->id);
    expect($team->fresh()->name)->toBe('Kadeci')
        ->and(AuditLog::where('action', 'model.reverted')->exists())->toBeTrue();

    // Ponowne cofnięcie tego samego wpisu już nie przejdzie: pola mają inne wartości niż „po”.
    Livewire::test(AuditLogViewer::class)->call('undo', $entry->id)->assertDispatched('toast', type: 'error');
    expect($team->fresh()->name)->toBe('Kadeci');
});

test('account permission history restores roles and overrides', function () {
    $user = User::factory()->create(['role' => UserRole::Trainer]);

    $manager = Livewire::test(UserManager::class)->call('openEditModal', $user->id);
    $manager->call('setOverride', Permission::Articles->value, 'grant');
    $manager->set('selectedRoles', [UserRole::Fan->value])->call('save');

    expect($user->fresh()->roles()->map->value->all())->toBe(['fan'])
        ->and(AccessSnapshot::where('user_id', $user->id)->count())->toBe(2);

    $first = AccessSnapshot::where('user_id', $user->id)->oldest('id')->first();
    Livewire::test(UserManager::class)->call('openEditModal', $user->id)->set('modalTab', 'uprawnienia')
        ->assertSee('Historia uprawnień')->call('restoreAccess', $first->id);

    $user = User::find($user->id);
    expect($user->roles()->map->value->all())->toBe(['trainer'])
        ->and(PermissionOverride::where('user_id', $user->id)->count())->toBe(0)
        ->and(AuditLog::where('action', 'permissions.restored')->exists())->toBeTrue();
});

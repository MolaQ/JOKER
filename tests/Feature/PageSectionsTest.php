<?php

use App\Livewire\Admin\PageManager;
use App\Models\Page;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->about = Page::factory()->create(['title' => 'O klubie', 'slug' => 'o-klubie', 'is_published' => true, 'display_order' => 1]);
    $this->group = Page::factory()->create(['title' => 'Programy i projekty', 'slug' => 'programy-i-projekty', 'is_published' => false, 'display_order' => 2]);
    $this->program = Page::factory()->create([
        'title' => 'KLUB PRO 2026', 'slug' => 'klub-pro-2026', 'parent_id' => $this->group->id, 'is_published' => false,
        'footer_link' => true, 'theme_primary' => '#1e2a8a', 'theme_accent' => '#ff6b1a',
        'highlights' => [['value' => '110 000 zł', 'label' => 'dofinansowania w edycji 2026']],
    ]);
});

test('a hidden page and its footer link are visible only to admin and boss', function () {
    $this->get('/page/klub-pro-2026')->assertNotFound();
    $this->get('/')->assertDontSee('KLUB PRO 2026');

    $fan = User::factory()->create();
    $this->actingAs($fan)->get('/page/klub-pro-2026')->assertNotFound();

    $boss = User::factory()->create(['role' => [UserRole::Boss]]);
    DB::table('role_permissions')->insertOrIgnore(['role' => 'boss', 'permission' => 'pages.preview']);
    cache()->forget('role_permissions');

    $this->actingAs($boss)->get('/page/klub-pro-2026')->assertOk()
        ->assertSee('Projekt przed publikacją')->assertSee('Zapisz jako PDF')->assertSee('110 000 zł')
        ->assertSee('--page-accent: #ff6b1a', false)->assertSee('noindex', false);
    $this->actingAs($boss)->get('/')->assertSee('KLUB PRO 2026');

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get('/page/programy-i-projekty')->assertOk()->assertSee('KLUB PRO 2026');
});

test('after publishing the group and the program everyone sees them in the section menu and footer', function () {
    $this->group->update(['is_published' => true]);
    $this->get('/page/klub-pro-2026')->assertNotFound();

    $this->program->update(['is_published' => true]);

    $this->get('/page/klub-pro-2026')->assertOk()->assertDontSee('Projekt przed publikacją')->assertSee('W tym dziale');
    $this->get('/page/o-klubie')->assertOk()->assertSee('Programy i projekty');
    $this->get('/')->assertSee('KLUB PRO 2026');
});

test('a published child of a hidden group stays hidden from visitors', function () {
    $this->program->update(['is_published' => true]);

    $this->get('/page/klub-pro-2026')->assertNotFound();
    expect(Page::footerLinksFor(null))->toBeEmpty();
});

test('the panel orders pages with arrows, sets the group, colours and highlights', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(PageManager::class)
        ->call('move', $this->group->id, -1)
        ->call('openCreateModal')
        ->set('title', 'Certyfikacja')
        ->set('parent_id', $this->group->id)
        ->call('useTheme', 'klub-pro')
        ->call('addHighlight')
        ->set('highlights.0.value', 'Złoty')
        ->set('highlights.0.label', 'certyfikat')
        ->set('is_published', false)
        ->call('save')
        ->assertHasNoErrors();

    $page = Page::where('slug', 'certyfikacja')->first();
    expect($this->group->fresh()->display_order)->toBeLessThan($this->about->fresh()->display_order)
        ->and($page->parent_id)->toBe($this->group->id)
        ->and($page->theme_primary)->toBe('#1e2a8a')
        ->and($page->highlights)->toBe([['value' => 'Złoty', 'label' => 'certyfikat']]);

    Livewire::test(PageManager::class)->call('openEditModal', $this->group->id)->set('parent_id', $this->about->id)->call('save')->assertHasErrors('parent_id');
    Livewire::test(PageManager::class)->call('togglePublished', $this->group->id);
    expect($this->group->fresh()->is_published)->toBeTrue();
});

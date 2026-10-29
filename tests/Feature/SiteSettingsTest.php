<?php

use App\Livewire\Admin\PageManager;
use App\Livewire\Admin\SiteSettingsManager;
use App\Models\Page;
use App\Models\User;
use App\Support\SearchIndex;
use App\Support\SiteSettings;
use App\UserRole;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

test('the top bar shows its default links, search and the highlighted menu button', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Dla rodziców')
        ->assertSee('--color-topbar-bg: #ffd600', false)
        ->assertSee('Dołącz');
});

test('admin saves colours, texts and links and the site shows them', function () {
    $this->actingAs($this->admin);

    Livewire::test(SiteSettingsManager::class)
        ->set('values.color_topbar_bg', '#E30613')
        ->set('values.club_name', 'Joker Piła Test')
        ->set('values.topbar_links_left', [['label' => 'Bilety', 'url' => '/page/bilety']])
        ->set('values.topbar_enabled', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->get('/')
        ->assertSee('--color-topbar-bg: #e30613', false)
        ->assertSee('Joker Piła Test')
        ->assertSee('Bilety')
        ->assertDontSee('Dla rodziców');
});

test('the top bar can be switched off', function () {
    app(SiteSettings::class)->set(['topbar_enabled' => false]);

    $this->get('/')->assertDontSee('aria-label="Na skróty"', false);
});

test('invalid colours and link addresses are rejected', function () {
    $this->actingAs($this->admin);

    Livewire::test(SiteSettingsManager::class)
        ->set('values.color_accent', 'red; background: url(x)')
        ->set('values.nav_items', [['label' => 'Zły', 'url' => 'javascript:alert(1)']])
        ->call('save')
        ->assertHasErrors(['values.color_accent', 'values.nav_items.0.url']);
});

test('only admins can open site settings', function () {
    $trainer = User::factory()->create(['role' => UserRole::Trainer]);

    $this->actingAs($trainer)->get(route('admin.settings.index'))->assertForbidden();
    $this->actingAs($this->admin)->get(route('admin.settings.index'))->assertOk()->assertSee('Górny pasek');
});

test('admin creates a sub-page that is public and searchable', function () {
    $this->actingAs($this->admin);

    Livewire::test(PageManager::class)
        ->call('openCreateModal')
        ->set('title', 'Klub Kibica')
        ->set('content', '<p>Wyjazdy na mecze autokarem</p>')
        ->call('save')
        ->assertHasNoErrors();

    $page = Page::firstWhere('slug', 'klub-kibica');

    $this->get(route('pages.show', $page))->assertOk()->assertSee('Wyjazdy na mecze autokarem');
    expect(collect(SearchIndex::search('autokarem'))->pluck('title'))->toContain('Klub Kibica');
});

test('hidden sub-pages are not public', function () {
    $page = Page::factory()->create(['is_published' => false]);

    $this->get(route('pages.show', $page))->assertNotFound();
});

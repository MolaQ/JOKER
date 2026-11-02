<?php

use App\Models\User;
use App\Support\SiteSettings;
use App\UserRole;

it('shows the admin bottom menu with shortcuts for the admin role', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();
    $nav = str($html)->after('data-admin-bottom-nav')->before('</nav>');

    expect((string) $nav)
        ->toContain(route('admin.dashboard'))
        ->toContain(route('admin.approvals.index'))
        ->toContain(route('admin.games.index'))
        ->toContain(route('admin.articles.index'))
        ->toContain('Menu')
        ->not->toContain(route('admin.trainings.index'));
});

it('shows trainers their trainings, matches and players in the bottom menu', function () {
    $trainer = User::factory()->create(['role' => UserRole::Trainer]);

    $html = $this->actingAs($trainer)->get(route('admin.dashboard'))->assertOk()->getContent();
    $nav = (string) str($html)->after('data-admin-bottom-nav')->before('</nav>');

    expect($nav)
        ->toContain(route('admin.trainings.index'))
        ->toContain(route('admin.games.index'))
        ->toContain(route('admin.players.index'))
        ->not->toContain(route('admin.articles.index'));
});

it('shows the add button on admin lists as a floating button on phones', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get(route('admin.articles.index'))
        ->assertOk()
        ->assertSee('admin-fab', false);
});

it('serves a panel manifest so the panel opens like an app from the home screen', function () {
    $this->get(route('admin.manifest'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json')
        ->assertJsonPath('display', 'standalone')
        ->assertJsonPath('start_url', route('admin.dashboard', absolute: false));
});

it('offers the light, dark and auto theme switch in the panel and on the site', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-theme-set="light"', false)
        ->assertSee('data-theme-set="dark"', false)
        ->assertSee('data-theme-set="auto"', false)
        ->assertSee(route('admin.manifest'), false);

    $this->get(route('home'))->assertOk()->assertSee('data-theme-set="auto"', false);
});

it('colours the panel with the club colour from settings and keeps the default untouched', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertDontSee('--color-sky-600:', false);

    app(SiteSettings::class)->set(['color_panel' => '#e30613', 'theme_default' => 'auto']);

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('--color-sky-600: #e30613', false)
        ->assertSee('"auto"', false);
});

<?php

use App\Models\User;
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

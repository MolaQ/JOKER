<?php

use App\Livewire\Admin\ArticleManager;
use App\Models\Article;
use App\Models\User;
use Livewire\Livewire;

it('shows and toggles the pin before the title on the article list', function () {
    $admin = User::factory()->admin()->create();
    $old = Article::create(['title' => str_repeat('Bardzo długi tytuł artykułu ', 5), 'slug' => 'stary', 'content' => 'x', 'status' => 'published', 'published_at' => now()->subDays(12), 'is_featured' => true, 'author_id' => $admin->id]);
    $new = Article::create(['title' => 'Podsumowanie dnia', 'slug' => 'nowy', 'content' => 'x', 'status' => 'published', 'published_at' => now()->subHour(), 'author_id' => $admin->id]);

    $this->get(route('articles.index'))->assertSeeInOrder([$old->title, 'Podsumowanie dnia']);

    Livewire::actingAs($admin)->test(ArticleManager::class)
        ->assertSeeInOrder(['Przypięty', e($old->title)], false)
        ->call('toggleFeatured', $old->id);

    expect($old->fresh()->is_featured)->toBeFalse();
    $this->get(route('articles.index'))->assertSeeInOrder(['Podsumowanie dnia', $old->title]);
});

<?php

use App\Livewire\Admin\ArticleManager;
use App\Models\Article;
use App\Models\User;
use Livewire\Livewire;

it('builds the Facebook post text from the whole article', function () {
    $article = Article::factory()->for(User::factory(), 'author')->create([
        'title' => 'Kadeci wygrywają 3:1',
        'slug' => 'kadeci-wygrywaja',
        'content' => '<p>Pierwszy&nbsp;set &amp; emocje.</p><p>Drugi akapit<br>nowa linia</p><ul><li>Jan</li><li>Piotr</li></ul><iframe src="https://youtube.com/x"></iframe><script>alert(1)</script>',
    ]);

    expect($article->facebookText())->toBe(
        "Kadeci wygrywają 3:1\n\nPierwszy set & emocje.\n\nDrugi akapit\nnowa linia\n\n• Jan\n• Piotr\n\nCała relacja, zdjęcia i wideo: ".route('articles.show', $article)
    );
});

it('shows the Facebook button only for published articles', function () {
    $admin = User::factory()->admin()->create();
    $published = Article::create(['title' => 'Relacja', 'slug' => 'relacja', 'content' => '<p>Treść</p>', 'status' => 'published', 'published_at' => now()->subHour(), 'author_id' => $admin->id]);
    $draft = Article::create(['title' => 'Szkic', 'slug' => 'szkic', 'content' => '<p>Treść</p>', 'status' => 'draft', 'author_id' => $admin->id]);

    Livewire::actingAs($admin)->test(ArticleManager::class)
        ->assertSeeHtml('data-facebook-share="'.$published->id.'"')
        ->assertDontSeeHtml('data-facebook-share="'.$draft->id.'"');
});

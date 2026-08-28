<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = User::where('role', UserRole::Admin)->get();
        $trainers = User::where('role', UserRole::Trainer)->get();
        $authors = $admins->merge($trainers);
        $commenters = User::whereIn('role', ['player', 'fan', 'parent'])->get();

        // Utworzenie 30 artykułów
        Article::factory()
            ->count(30)
            ->create([
                'author_id' => $authors->random()->id,
            ])
            ->each(function ($article) use ($commenters) {
                // Dodaj 3-8 komentarzy do każdego opublikowanego artykułu
                if ($article->isPublished() && $article->allow_comments) {
                    Comment::factory()
                        ->count(fake()->numberBetween(3, 8))
                        ->create([
                            'article_id' => $article->id,
                            'user_id' => $commenters->random()->id,
                        ]);
                }
            });
    }
}

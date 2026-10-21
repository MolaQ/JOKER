<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Treści serwisu: artykuły (z wersją angielską i powiązanymi meczami), komentarze,
     * polubienia, dokumenty, sponsorzy, slajdy na stronie głównej, ustawienia,
     * podstrony, statystyki odwiedzin oraz galerie zdjęć.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->text('excerpt_en')->nullable();
            $table->longText('content');
            $table->longText('content_en')->nullable();
            $table->string('featured_image')->nullable();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->integer('views_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('allow_comments')->default(true);
            $table->timestamps();
        });

        Schema::create('article_game', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();

            $table->unique(['article_id', 'game_id']);
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });

        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('likeable_type');
            $table->unsignedBigInteger('likeable_id');
            $table->timestamps();

            $table->unique(['user_id', 'likeable_type', 'likeable_id'], 'likes_user_likeable_unique');
            $table->index(['likeable_type', 'likeable_id']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_type'); // pdf, doc, xls, etc.
            $table->integer('file_size'); // w bajtach
            $table->enum('category', ['regulations', 'forms', 'reports', 'other'])->default('other');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->integer('download_count')->default(0);
            $table->boolean('is_public')->default(true); // czy dostępny publicznie
            $table->timestamps();
        });

        Schema::create('sponsors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('website_url')->nullable();
            $table->enum('tier', ['platinum', 'gold', 'silver', 'bronze'])->default('bronze');
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('subtitle')->nullable();
            $table->string('image_path');
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('publish_from')->nullable();
            $table->timestamp('publish_to')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'publish_from', 'publish_to']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->string('slug')->unique();
            $table->string('lead')->nullable();
            $table->string('lead_en')->nullable();
            $table->longText('content')->nullable();
            $table->longText('content_en')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_view_id')->nullable()->constrained('page_views')->cascadeOnDelete();
            $table->string('kind', 8)->default('page');
            $table->string('path');
            $table->string('route_name', 100)->nullable();
            $table->string('title')->nullable();
            $table->string('label', 120)->nullable();
            $table->char('visitor_hash', 64);
            $table->string('referrer_host', 120)->nullable();
            $table->string('device', 8)->nullable();
            $table->string('locale', 5)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedTinyInteger('max_scroll')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['kind', 'created_at']);
            $table->index(['path', 'created_at']);
        });

        Schema::create('galleries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->morphs('match');
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('caption')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_photos');
        Schema::dropIfExists('galleries');
        Schema::dropIfExists('page_views');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('hero_slides');
        Schema::dropIfExists('sponsors');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('likes');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('article_game');
        Schema::dropIfExists('articles');
    }
};

@extends('layouts.public')

@section('title', 'Aktualności - Joker Piła')

@section('content')
<!-- Page Header -->
<div class="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white py-16">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">Aktualności</h1>
        <p class="text-xl text-blue-100">
            Najnowsze wiadomości z życia klubu
        </p>
    </div>
</div>

<!-- Featured Article -->
@if($featured)
    <section class="container mx-auto px-4 py-12">
        <article class="bg-white rounded-lg shadow-xl overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-0">
                @if($featured->featured_image)
                    <img src="{{ Storage::url($featured->featured_image) }}" alt="{{ $featured->title }}" class="w-full h-96 object-cover">
                @else
                    <x-placeholder-image label="Wyróżniony artykuł" height="h-96" class="rounded-none" />
                @endif

                <div class="p-8 flex flex-col justify-center">
                    <div class="inline-block bg-blue-900 text-white text-xs font-semibold px-3 py-1 rounded mb-4 w-fit">
                        WYRÓŻNIONE
                    </div>
                    <div class="text-sm text-gray-500 mb-3">
                        {{ $featured->published_at?->format('d.m.Y') }}
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-4 hover:text-blue-900 transition">
                        <a href="{{ route('articles.show', $featured) }}">
                            {{ $featured->title }}
                        </a>
                    </h2>
                    <p class="text-gray-600 mb-6 leading-relaxed">
                        {{ $featured->excerpt }}
                    </p>
                    <div class="mb-4 flex items-center gap-3 text-xs text-gray-500">
                        <span>👁 {{ $featured->views_count }}</span>
                        <span>💬 {{ $featured->approved_comments_count }}</span>
                        <span>❤️ {{ $featured->likes_count }}</span>
                    </div>
                    <a href="{{ route('articles.show', $featured) }}" class="inline-block bg-blue-900 text-white px-6 py-3 rounded-lg hover:bg-blue-800 transition font-semibold">
                        Czytaj więcej →
                    </a>
                </div>
            </div>
        </article>
    </section>
@endif

<!-- Articles Grid -->
<section class="container mx-auto px-4 py-12">
    @if($articles->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($articles as $article)
                <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition">
                    @if($article->featured_image)
                        <img src="{{ Storage::url($article->featured_image) }}" alt="{{ $article->title }}" class="w-full h-48 object-cover">
                    @else
                        <x-placeholder-image label="Aktualność" height="h-48" class="rounded-none" />
                    @endif

                    <div class="p-6">
                        <div class="flex items-center space-x-3 mb-3">
                            <div class="text-sm text-gray-500">
                                {{ $article->published_at?->format('d.m.Y') }}
                            </div>
                            @if($article->is_featured)
                                <span class="bg-blue-100 text-blue-900 text-xs font-semibold px-2 py-1 rounded">
                                    Wyróżnione
                                </span>
                            @endif
                        </div>

                        <h3 class="text-xl font-bold text-gray-900 mb-3 hover:text-blue-900 transition">
                            <a href="{{ route('articles.show', $article) }}">
                                {{ $article->title }}
                            </a>
                        </h3>

                        <p class="text-gray-600 mb-4">
                            {{ Str::limit(strip_tags((string) $article->excerpt), 120) }}
                        </p>

                        <div class="flex items-center justify-between">
                            <a href="{{ route('articles.show', $article) }}" class="text-blue-900 hover:text-blue-700 font-medium">
                                Czytaj więcej →
                            </a>
                            <div class="text-xs text-gray-500 text-right">
                                <div>👁 {{ $article->views_count }}</div>
                                <div>💬 {{ $article->approved_comments_count }}</div>
                                <div>❤️ {{ $article->likes_count }}</div>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-12">
            {{ $articles->links() }}
        </div>
    @else
        <div class="bg-gray-100 rounded-lg p-12 text-center">
            <p class="text-gray-600 text-lg">Brak aktualności do wyświetlenia.</p>
        </div>
    @endif
</section>
@endsection

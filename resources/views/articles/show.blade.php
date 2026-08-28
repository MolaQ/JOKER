@extends('layouts.public')

@section('title', $article->title . ' - Joker Piła')

@section('content')
<!-- Article Header -->
<div class="bg-white py-8 border-b">
    <div class="container mx-auto px-4">
        <nav class="text-sm text-gray-500 mb-4">
            <a href="{{ route('home') }}" class="hover:text-gray-700">Strona główna</a>
            <span class="mx-2">›</span>
            <a href="{{ route('articles.index') }}" class="hover:text-gray-700">Aktualności</a>
            <span class="mx-2">›</span>
            <span class="text-gray-900">{{ Str::limit($article->title, 50) }}</span>
        </nav>

        <div class="max-w-4xl">
            <div class="flex items-center space-x-3 mb-4">
                <div class="text-sm text-gray-500">
                    {{ $article->published_at?->format('d.m.Y H:i') }}
                </div>
                @if($article->is_featured)
                    <span class="bg-blue-100 text-blue-900 text-xs font-semibold px-2 py-1 rounded">
                        Wyróżnione
                    </span>
                @endif
            </div>

            <h1 class="text-4xl md:text-5xl font-bold text-gray-900">
                {{ $article->title }}
            </h1>
        </div>
    </div>
</div>

<!-- Article Content -->
<article class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto">
        @if($article->featured_image)
            <img src="{{ Storage::url($article->featured_image) }}" alt="{{ $article->title }}" class="w-full rounded-lg shadow-lg mb-8">
        @endif

        @if($article->excerpt)
            <div class="text-xl text-gray-700 leading-relaxed mb-8 font-medium border-l-4 border-blue-900 pl-6 py-2">
                {!! $article->excerpt !!}
            </div>
        @endif

        <div class="prose prose-lg max-w-none">
            {!! $article->content !!}
        </div>

        <div class="mt-8 pt-6 border-t flex items-center justify-between">
            <div class="text-sm text-gray-600">
                <span class="font-semibold">Autor:</span> {{ $article->author->name }}
            </div>
            <div class="flex items-center gap-3">
                <div class="text-sm text-gray-600">
                    👁 {{ $article->views_count }} wyświetleń
                </div>
                @livewire('like-button', ['type' => 'article', 'id' => $article->id], key('article-like-'.$article->id))
            </div>
        </div>
    </div>
</article>

<!-- Comments Section -->
@if($article->allow_comments)
    <section class="bg-gray-50 py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    Komentarze ({{ $article->approvedComments->count() }})
                </h2>

                <!-- Comment Form -->
                @auth
                    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Dodaj komentarz</h3>

                        <form action="{{ route('articles.comments.store', $article) }}" method="POST">
                            @csrf
                            <textarea
                                name="content"
                                rows="4"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-900 focus:border-transparent"
                                placeholder="Twój komentarz..."
                                required
                            >{{ old('content') }}</textarea>

                            @error('content')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror

                            <div class="mt-4 flex justify-end">
                                <button type="submit" class="bg-blue-900 text-white px-6 py-2 rounded-lg hover:bg-blue-800 transition font-semibold">
                                    Wyślij komentarz
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                        <p class="text-blue-900">
                            <a href="{{ route('login') }}" class="font-semibold hover:underline">Zaloguj się</a>, aby dodać komentarz i polubienie.
                        </p>
                    </div>
                @endauth

                <!-- Comments List -->
                @if($article->approvedComments->count() > 0)
                    <div class="space-y-4">
                        @foreach($article->approvedComments->where('parent_id', null) as $comment)
                            <div class="bg-white rounded-lg shadow-md p-6">
                                <div class="flex items-start space-x-4">
                                    <div class="w-10 h-10 bg-blue-900 text-white rounded-full flex items-center justify-center font-semibold flex-shrink-0">
                                        {{ Str::substr($comment->user->name, 0, 1) }}
                                    </div>

                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <span class="font-semibold text-gray-900">{{ $comment->user->name }}</span>
                                            <span class="text-sm text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                                        </div>

                                        <p class="text-gray-700 leading-relaxed">
                                            {{ $comment->content }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Replies -->
                                @if($comment->replies->count() > 0)
                                    <div class="ml-14 mt-4 space-y-4">
                                        @foreach($comment->replies->where('is_approved', true) as $reply)
                                            <div class="bg-gray-50 rounded-lg p-4">
                                                <div class="flex items-start space-x-3">
                                                    <div class="w-8 h-8 bg-blue-700 text-white rounded-full flex items-center justify-center text-sm font-semibold flex-shrink-0">
                                                        {{ Str::substr($reply->user->name, 0, 1) }}
                                                    </div>

                                                    <div class="flex-1">
                                                        <div class="flex items-center space-x-2 mb-1">
                                                            <span class="font-semibold text-gray-900 text-sm">{{ $reply->user->name }}</span>
                                                            <span class="text-xs text-gray-500">{{ $reply->created_at->diffForHumans() }}</span>
                                                        </div>

                                                        <p class="text-gray-700 text-sm leading-relaxed">
                                                            {{ $reply->content }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-gray-100 rounded-lg p-8 text-center">
                        <p class="text-gray-600">Brak komentarzy. Bądź pierwszy!</p>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif

<!-- Related Articles -->
@if($related_articles->count() > 0)
    <section class="container mx-auto px-4 py-12">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Może Cię zainteresować</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($related_articles as $related)
                    <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition">
                        @if($related->featured_image)
                            <img src="{{ Storage::url($related->featured_image) }}" alt="{{ $related->title }}" class="w-full h-32 object-cover">
                        @else
                            <x-placeholder-image label="Artykuł" height="h-32" class="rounded-none" />
                        @endif

                        <div class="p-4">
                            <div class="text-xs text-gray-500 mb-2">
                                {{ $related->published_at?->format('d.m.Y') }}
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 hover:text-blue-900 transition">
                                <a href="{{ route('articles.show', $related) }}">
                                    {{ Str::limit($related->title, 60) }}
                                </a>
                            </h3>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif
@endsection

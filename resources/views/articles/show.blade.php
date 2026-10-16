@extends('layouts.public')

@section('title', $article->title . ' - Joker Piła')
@section('description', Str::limit(strip_tags((string) $article->excerpt), 155))

@php use App\Support\Media; @endphp

@section('content')
<article>
    <header class="relative isolate -mt-16 overflow-hidden bg-ink pt-16 text-white lg:-mt-20 lg:pt-20">
        <x-photo :src="Media::articleImage($article)" :alt="$article->title" eager sizes="100vw" width="1800" height="1000" class="absolute inset-0 -z-20 h-full w-full opacity-50" />
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink via-ink/60 to-ink/30"></div>
        <div class="container-site max-w-4xl pt-24 pb-14 lg:pt-36">
            <a href="{{ route('articles.index') }}" class="text-sm text-zinc-400 transition hover:text-white">← Aktualności</a>
            <p class="eyebrow mt-6">{{ $article->published_at?->translatedFormat('d F Y') }} · {{ $article->author?->name }}</p>
            <h1 class="mt-4 font-display text-4xl leading-[1.05] font-bold uppercase sm:text-6xl">{{ $article->title }}</h1>
        </div>
    </header>

    <div class="container-site max-w-3xl py-14">
        @if($article->excerpt)
            <div class="mb-10 border-l-4 border-sky-club pl-6 text-xl leading-relaxed font-medium text-zinc-800">
                {!! strip_tags($article->excerpt, '<strong><em><b><i><br>') !!}
            </div>
        @endif

        <div class="prose-club">
            {!! $article->content !!}
        </div>

        <div class="mt-12 flex flex-wrap items-center justify-between gap-4 border-t border-zinc-200 pt-6">
            <p class="text-sm text-zinc-500">{{ number_format($article->views_count, 0, ',', ' ') }} wyświetleń</p>
            @livewire('like-button', ['type' => 'article', 'id' => $article->id], key('article-like-'.$article->id))
        </div>
    </div>
</article>

@if($article->allow_comments)
<section class="container-site max-w-3xl">
    <h2 class="heading-md">Komentarze ({{ $article->approvedComments->count() }})</h2>

    @auth
        <form action="{{ route('articles.comments.store', $article) }}" method="POST" class="card mt-6 p-6">
            @csrf
            <label for="comment" class="text-sm font-semibold">Twój komentarz</label>
            <textarea id="comment" name="content" rows="4" required class="mt-2 w-full rounded-2xl border border-zinc-200 px-4 py-3 outline-none focus:border-sky-club focus:ring-2 focus:ring-sky-club/30">{{ old('content') }}</textarea>
            @error('content') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <div class="mt-4 flex justify-end"><button type="submit" class="btn-primary">Wyślij</button></div>
        </form>
    @else
        <p class="mt-6 rounded-2xl bg-sky-club/10 p-5 text-sm"><a href="{{ route('login') }}" class="font-semibold text-sky-club-dark underline">Zaloguj się</a>, aby dodać komentarz.</p>
    @endauth

    <div class="mt-8 space-y-4">
        @foreach($article->approvedComments->whereNull('parent_id') as $comment)
            <div class="card p-5">
                <div class="flex items-center gap-3">
                    <x-avatar :name="$comment->user?->name ?? '?'" class="h-9 w-9 rounded-full text-sm" />
                    <p class="text-sm"><span class="font-semibold">{{ $comment->user?->name }}</span> <span class="text-zinc-400">· {{ $comment->created_at->diffForHumans() }}</span></p>
                </div>
                <p class="mt-3 text-zinc-700">{{ $comment->content }}</p>

                @foreach($comment->replies->where('is_approved', true) as $reply)
                    <div class="mt-4 ml-6 border-l-2 border-sky-club/40 pl-4">
                        <p class="text-sm"><span class="font-semibold">{{ $reply->user?->name }}</span> <span class="text-zinc-400">· {{ $reply->created_at->diffForHumans() }}</span></p>
                        <p class="mt-1 text-zinc-700">{{ $reply->content }}</p>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</section>
@endif

@if($related_articles->isNotEmpty())
<section class="container-site mt-20">
    <h2 class="heading-md">Czytaj także</h2>
    <div class="mt-6 grid gap-6 sm:grid-cols-3">
        @foreach($related_articles as $related)
            <a href="{{ route('articles.show', $related) }}" class="card card-hover group overflow-hidden">
                <x-photo :src="Media::articleImage($related)" :alt="$related->title" sizes="(min-width: 640px) 33vw, 100vw" class="aspect-[16/10] w-full" />
                <div class="p-5">
                    <p class="text-xs font-semibold text-zinc-500">{{ $related->published_at?->translatedFormat('d F Y') }}</p>
                    <h3 class="mt-1 font-semibold group-hover:text-sky-club-dark">{{ $related->title }}</h3>
                </div>
            </a>
        @endforeach
    </div>
</section>
@endif
@endsection

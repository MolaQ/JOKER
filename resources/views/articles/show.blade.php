@extends('layouts.public')

@section('title', $article->localizedTitle())
@section('description', Str::limit(strip_tags((string) $article->localizedExcerpt()), 155))

@php use App\Support\Media; @endphp

@section('og_type', 'article')
@section('og_image', \App\Support\Media::articleImage($article))
@push('structured-data')
    {!! \App\Support\Seo::jsonLd(\App\Support\Seo::article($article)) !!}
@endpush

@section('content')
<article>
    <header class="relative isolate -mt-16 flex min-h-[26rem] flex-col justify-end overflow-hidden bg-ink pt-16 text-white sm:min-h-[32rem] lg:-mt-20 lg:min-h-[38rem] lg:pt-20">
        <x-photo :src="Media::articleImage($article)" :focus="$article->imageFocus()" :alt="$article->localizedTitle()" eager sizes="100vw" width="1800" height="1000" class="absolute inset-0 -z-20 h-full w-full opacity-70" />
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink via-ink/50 to-ink/10"></div>
        <div class="container-site max-w-4xl pt-24 pb-14 lg:pt-36">
            <a href="{{ route('articles.index') }}" class="text-sm text-zinc-400 transition hover:text-white">← {{ __('Aktualności') }}</a>
            <p class="eyebrow mt-6">{{ $article->published_at?->translatedFormat('d F Y') }} @if($article->authorName()) · @if($article->authorUrl())<a href="{{ $article->authorUrl() }}" class="hover:text-white">{{ $article->authorName() }}</a>@else{{ $article->authorName() }}@endif @endif</p>
            <h1 class="mt-4 font-display text-4xl leading-[1.05] font-bold uppercase sm:text-6xl">{{ $article->localizedTitle() }}</h1>
            @if($article->hasEnglish())
                <p class="mt-4 inline-flex items-center gap-2 text-sm text-zinc-300"><x-english-badge /> {{ __('Dostępna wersja angielska') }}</p>
            @endif
            <x-share-card :card="\App\Support\ShareCards::article($article)" dark class="mt-6" />
        </div>
    </header>

    <div class="container-site max-w-3xl py-14">
        @if(app()->getLocale() === 'en' && ! $article->hasEnglish())
            <p class="mb-8 rounded-2xl bg-zinc-100 px-5 py-4 text-sm text-zinc-600">{{ __('Ten wpis jest dostępny tylko po polsku.') }}</p>
        @endif

        @if($article->localizedExcerpt())
            <div class="mb-10 border-l-4 border-sky-club pl-6 text-xl leading-relaxed font-medium text-zinc-800">
                {!! strip_tags($article->localizedExcerpt(), '<strong><em><b><i><br>') !!}
            </div>
        @endif

        <div class="prose-club" @if(app()->getLocale() === 'en' && blank($article->content_en)) lang="pl" @endif>
            {!! \App\Support\DeadLinks::strip(\App\Support\Mentions::link($article->localizedContent())) !!}
        </div>

        <div class="mt-12 flex flex-wrap items-center justify-between gap-4 border-t border-zinc-200 pt-6">
            <p class="text-sm text-zinc-500">{{ app()->getLocale() === 'en' ? number_format($article->views_count).' '.($article->views_count === 1 ? 'view' : 'views') : number_format($article->views_count, 0, ',', ' ').' wyświetleń' }}</p>
            <div class="flex items-center gap-2">
                @if($article->allow_comments)
                    <a href="#komentarze"><x-comment-count :count="$article->approvedComments->count()" /></a>
                @endif
                @livewire('like-button', ['type' => 'article', 'id' => $article->id], key('article-like-'.$article->id))
            </div>
        </div>
    </div>
</article>

{{-- Media meczów, których dotyczy artykuł: transmisje i galerie --}}
@foreach($article->games as $game)
    @php
        $gameStreams = $game->normalizedStreams();
        $gameGalleries = $game->publishedGalleries->filter(fn ($gallery) => $gallery->photos->isNotEmpty());
        [$gameHome, $gameAway] = $game->sidesNames();
    @endphp
    @if(count($gameStreams) || $gameGalleries->isNotEmpty())
        <section class="container-site max-w-3xl space-y-10 pb-14" aria-label="{{ __('Transmisje i zdjęcia z meczu') }}">
            <div class="flex flex-wrap items-end justify-between gap-3 border-t border-zinc-200 pt-10">
                <div>
                    <p class="eyebrow">{{ __('Mecz') }} · {{ $game->kickoffLabel('d F Y') }}</p>
                    <h2 class="heading-md mt-2">{{ $gameHome }} – {{ $gameAway }}</h2>
                </div>
                <a href="{{ route('games.show', $game) }}" class="text-sm font-semibold text-sky-club-dark hover:underline">{{ __('Strona meczu') }} →</a>
            </div>
            @if(count($gameStreams))
                <div>
                    <h3 class="mb-4 text-sm font-semibold tracking-widest text-zinc-500 uppercase">▶ {{ __('Transmisja') }}</h3>
                    <x-match-streams :streams="$gameStreams" />
                </div>
            @endif
            @foreach($gameGalleries as $gallery)
                <x-match-gallery :gallery="$gallery" :limit="8" id="galeria-{{ $gallery->id }}" />
            @endforeach
        </section>
    @else
        <p class="container-site max-w-3xl pb-10 text-sm">
            <a href="{{ route('games.show', $game) }}" class="font-semibold text-sky-club-dark hover:underline">{{ __('Strona meczu') }}: {{ $gameHome }} – {{ $gameAway }} →</a>
        </p>
    @endif
@endforeach

@if($article->allow_comments)
    <x-comments-section :comments="$article->approvedComments" :action="route('articles.comments.store', $article)" class="container-site max-w-3xl" />
@endif

@if($related_articles->isNotEmpty())
<section class="container-site mt-20">
    <h2 class="heading-md">{{ __('Czytaj także') }}</h2>
    <div class="mt-6 grid gap-6 sm:grid-cols-3">
        @foreach($related_articles as $related)
            <a href="{{ route('articles.show', $related) }}" class="card card-hover group overflow-hidden">
                <x-photo :src="Media::articleImage($related)" :focus="$related->imageFocus()" :alt="$related->localizedTitle()" sizes="(min-width: 640px) 33vw, 100vw" class="aspect-[16/10] w-full" />
                <div class="p-5">
                    <p class="text-xs font-semibold text-zinc-500">{{ $related->published_at?->translatedFormat('d F Y') }}</p>
                    <h3 class="mt-1 font-semibold group-hover:text-sky-club-dark">{{ $related->localizedTitle() }}@if($related->hasEnglish()) <x-english-badge class="ml-1" />@endif</h3>
                </div>
            </a>
        @endforeach
    </div>
</section>
@endif
@endsection

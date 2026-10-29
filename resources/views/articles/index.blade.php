@extends('layouts.public')

@section('title', __('Aktualności'))
@section('description', __('Aktualności :club: relacje z meczów, sukcesy zawodników, turnieje i życie klubu.', ['club' => $site['club_name']]))

@php use App\Support\Media; @endphp

@section('content')
<section class="container-site pt-14 pb-10 lg:pt-20">
    <p class="eyebrow">{{ __('Z życia klubu') }}</p>
    <h1 class="heading-xl mt-4">{{ __('Aktualności') }}</h1>
</section>

<section class="container-site">
    @if($articles->isEmpty())
        <p class="rounded-3xl bg-zinc-100 p-8 text-zinc-600">{{ __('Jeszcze nic tu nie ma. Zajrzyj wkrótce!') }}</p>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($articles as $article)
                @if($loop->first && $articles->onFirstPage())
                    <a href="{{ route('articles.show', $article) }}" class="group relative isolate flex min-h-[26rem] flex-col justify-end overflow-hidden rounded-3xl p-8 text-white sm:col-span-2 lg:col-span-3 lg:min-h-[32rem]">
                        <x-photo :src="Media::articleImage($article)" :focus="$article->imageFocus()" :alt="$article->localizedTitle()" eager sizes="100vw" width="1800" height="900" class="absolute inset-0 -z-10 h-full w-full transition duration-700 group-hover:scale-105" />
                        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink via-ink/50 to-transparent"></div>
                        <p class="text-xs font-semibold tracking-widest text-sky-club-light uppercase">{{ $article->published_at?->translatedFormat('d F Y') }}</p>
                        <h2 class="mt-3 max-w-3xl font-display text-4xl leading-tight font-bold uppercase lg:text-5xl">{{ $article->localizedTitle() }}@if($article->hasEnglish()) <x-english-badge class="ml-1" />@endif</h2>
                        <p class="mt-3 line-clamp-2 max-w-2xl text-zinc-300">{{ Str::limit(strip_tags((string) $article->localizedExcerpt()), 200) }}</p>
                        <p class="mt-4 flex items-center gap-4 text-sm font-semibold text-zinc-300 tabular-nums">
                            <span title="{{ __('Polubienia') }}"><span aria-hidden="true">❤️</span> {{ $article->likes_count }}</span>
                            @if($article->allow_comments)<span title="{{ __('Komentarze') }}"><span aria-hidden="true">💬</span> {{ $article->approved_comments_count }}</span>@endif
                        </p>
                    </a>
                @else
                    <a href="{{ route('articles.show', $article) }}" class="card card-hover group flex flex-col overflow-hidden" data-reveal>
                        <x-photo :src="Media::articleImage($article)" :focus="$article->imageFocus()" :alt="$article->localizedTitle()" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" class="aspect-[16/10] w-full" />
                        <div class="flex flex-1 flex-col p-6">
                            <p class="text-xs font-semibold text-zinc-500">{{ $article->published_at?->translatedFormat('d F Y') }}</p>
                            <h2 class="mt-2 text-lg leading-snug font-semibold group-hover:text-sky-club-dark">{{ $article->localizedTitle() }}@if($article->hasEnglish()) <x-english-badge class="ml-1" />@endif</h2>
                            <p class="mt-2 line-clamp-3 text-sm text-zinc-500">{{ Str::limit(strip_tags((string) $article->localizedExcerpt()), 160) }}</p>
                            <p class="mt-auto flex items-center gap-4 pt-4 text-xs font-semibold text-zinc-500 tabular-nums">
                                <span title="{{ __('Polubienia') }}"><span aria-hidden="true">❤️</span> {{ $article->likes_count }}</span>
                                @if($article->allow_comments)<span title="{{ __('Komentarze') }}"><span aria-hidden="true">💬</span> {{ $article->approved_comments_count }}</span>@endif
                            </p>
                        </div>
                    </a>
                @endif
            @endforeach
        </div>

        <div class="mt-12">
            {{ $articles->links() }}
        </div>
    @endif
</section>
@endsection

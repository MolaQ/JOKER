@extends('layouts.public')

@php
    use App\Support\Media;

    [$homeName, $awayName] = $game->sidesNames();
    $ourLogo = $game->team?->logoPath();
    $opponentLogo = $game->opponentTeam?->logoPath();
    $sides = $game->is_home
        ? [[$homeName, $ourLogo, true], [$awayName, $opponentLogo, false]]
        : [[$homeName, $opponentLogo, false], [$awayName, $ourLogo, true]];
    $result = $game->ourSetsResult();
    $won = $game->isFinished() && $result ? $result[0] > $result[1] : null;
    $streams = $game->normalizedStreams();
    $galleries = $game->publishedGalleries->filter(fn ($gallery) => $gallery->photos->isNotEmpty());
    $competition = $game->competitionLabel();
@endphp

@section('title', $homeName.' – '.$awayName)
@section('description', trim(collect([$game->kickoffLabel('d F Y'), $competition, $game->venue])->filter()->implode(' · ').'. '.__('Wynik, transmisje, zdjęcia i relacje z meczu.')))

@push('structured-data')
    {!! \App\Support\Seo::jsonLd(\App\Support\Seo::game($game)) !!}
@endpush

@section('content')
<section class="relative isolate -mt-16 overflow-hidden bg-ink pt-16 text-white lg:-mt-20 lg:pt-20">
    <div class="net-pattern absolute inset-0 -z-10 opacity-40" aria-hidden="true"></div>
    <div class="absolute inset-0 -z-20 bg-gradient-to-br from-club-navy via-ink to-ink"></div>

    <div class="container-site max-w-5xl pt-24 pb-14 lg:pt-32 lg:pb-16">
        <a href="{{ route('schedule.index') }}" class="text-sm text-zinc-400 transition hover:text-white">← {{ __('Terminarz i wyniki') }}</a>
        <h1 class="sr-only">{{ $homeName }} – {{ $awayName }}</h1>
        <p class="eyebrow mt-6">{{ collect([$game->team?->name, $competition, $game->round_label])->filter()->implode(' · ') }}</p>
        <p class="mt-2 text-sm text-zinc-300">{{ $game->kickoffLabel('l, d F Y') }}@if($game->venue) · {{ $game->venue }}@endif</p>

        <div class="mt-10 grid grid-cols-[1fr_auto_1fr] items-center gap-4 sm:gap-8">
            @foreach($sides as $index => [$name, $logo, $isOurs])
                <div @class(['flex flex-col items-center gap-3 text-center', 'order-3' => $index === 1])>
                    <x-avatar :photo="$logo" :name="$name" class="h-20 w-20 rounded-full bg-white bg-none! text-2xl ring-4 ring-white/10 sm:h-28 sm:w-28 [&>img]:object-contain [&>img]:p-2" />
                    <p class="font-display text-lg leading-tight font-bold uppercase sm:text-3xl">
                        {{ $name }}
                        @if($isOurs)<span class="mt-1 block text-xs font-semibold tracking-widest text-sky-club-light">JOKER</span>@endif
                    </p>
                </div>
                @if($index === 0)
                    <div class="order-2 text-center">
                        @if($game->isFinished() && $game->home_score !== null)
                            <p class="font-display text-5xl font-bold tabular-nums sm:text-7xl">{{ $game->home_score }}<span class="mx-2 opacity-40">:</span>{{ $game->away_score }}</p>
                            @if($won !== null)
                                <p @class(['mt-2 inline-block rounded-full px-3 py-0.5 text-xs font-bold', 'bg-emerald-500/20 text-emerald-300' => $won, 'bg-red-500/20 text-red-300' => ! $won])>{{ $won ? __('Wygrana') : __('Porażka') }}</p>
                            @endif
                        @else
                            <p class="font-display text-3xl font-bold opacity-60">{{ __('vs') }}</p>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>

        @if($game->isFinished() && is_array($game->sets_score) && count($game->sets_score))
            <ol class="mt-8 flex flex-wrap justify-center gap-2 text-sm tabular-nums" aria-label="{{ __('Wyniki setów') }}">
                @foreach($game->sets_score as $set)
                    <li class="rounded-full bg-white/10 px-3 py-1">{{ implode(':', (array) $set) }}</li>
                @endforeach
            </ol>
        @endif

        <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
            @livewire('like-button', ['type' => 'game', 'id' => $game->id, 'dark' => true], key('game-like-'.$game->id))
            <a href="#komentarze" class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-sm font-medium text-white ring-1 ring-white/20 hover:bg-white/20" title="{{ __('Komentarze') }}">
                <span aria-hidden="true">💬</span> <span class="tabular-nums">{{ $game->approvedComments->count() }}</span>
            </a>
            @if($game->isUpcoming())
                <a href="{{ route('games.calendar', $game) }}" class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-sm font-medium text-white ring-1 ring-white/20 hover:bg-white/20">📅 {{ __('Do kalendarza') }}</a>
            @endif
        </div>
    </div>
</section>

@if(count($streams) || $galleries->isNotEmpty() || $articles->isNotEmpty())
    <nav class="sticky top-16 z-40 border-b border-zinc-200 bg-white/90 backdrop-blur lg:top-20" aria-label="{{ __('Sekcje meczu') }}">
        <div class="container-site scrollbar-none flex max-w-5xl gap-6 overflow-x-auto text-sm font-semibold">
            @if(count($streams))<a href="#transmisja" class="border-b-2 border-transparent py-4 whitespace-nowrap hover:border-sky-club">▶ {{ __('Transmisja') }}</a>@endif
            @if($galleries->isNotEmpty())<a href="#galeria" class="border-b-2 border-transparent py-4 whitespace-nowrap hover:border-sky-club">📷 {{ __('Zdjęcia') }}</a>@endif
            @if($articles->isNotEmpty())<a href="#relacje" class="border-b-2 border-transparent py-4 whitespace-nowrap hover:border-sky-club">📰 {{ __('Relacje') }}</a>@endif
            <a href="#komentarze" class="border-b-2 border-transparent py-4 whitespace-nowrap hover:border-sky-club">💬 {{ __('Komentarze') }}</a>
        </div>
    </nav>
@endif

<div class="container-site max-w-5xl space-y-16 py-14 lg:py-20">
    @if(count($streams))
        <section id="transmisja" class="scroll-mt-32">
            <p class="eyebrow">{{ __('Wideo') }}</p>
            <h2 class="heading-lg mt-3">{{ __('Transmisja') }}</h2>
            <x-match-streams :streams="$streams" class="mt-6" />
        </section>
    @endif

    @if($galleries->isNotEmpty())
        <section id="galeria" class="scroll-mt-32 space-y-12">
            <div>
                <p class="eyebrow">{{ __('Galeria') }}</p>
                <h2 class="heading-lg mt-3">{{ __('Zdjęcia z meczu') }}</h2>
            </div>
            @foreach($galleries as $gallery)
                <x-match-gallery :gallery="$gallery" :limit="8" id="galeria-{{ $gallery->id }}" />
            @endforeach
        </section>
    @endif

    @if(filled($game->match_report))
        <section>
            <p class="eyebrow">{{ __('Relacja') }}</p>
            @if($game->match_report !== strip_tags($game->match_report))
                <div class="prose-club mt-4 max-w-3xl">{!! strip_tags($game->match_report, '<p><br><strong><em><b><i><u><s><mark><blockquote><hr><ul><ol><li><h2><h3><a>') !!}</div>
            @else
                <div class="mt-4 max-w-3xl text-lg leading-relaxed whitespace-pre-line text-zinc-700">{{ $game->match_report }}</div>
            @endif
        </section>
    @endif

    @if($articles->isNotEmpty())
        <section id="relacje" class="scroll-mt-32">
            <p class="eyebrow">{{ __('Aktualności') }}</p>
            <h2 class="heading-lg mt-3">{{ __('Relacje z meczu') }}</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($articles as $article)
                    <a href="{{ route('articles.show', $article) }}" class="card card-hover group overflow-hidden">
                        <x-photo :src="Media::articleImage($article)" :focus="$article->imageFocus()" :alt="$article->localizedTitle()" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" class="aspect-[16/10] w-full" />
                        <div class="p-5">
                            <p class="text-xs font-semibold text-zinc-500">{{ $article->published_at?->translatedFormat('d F Y') }}</p>
                            <h3 class="mt-1 font-semibold group-hover:text-sky-club-dark">{{ $article->localizedTitle() }}</h3>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <x-comments-section :comments="$game->approvedComments" :action="route('games.comments.store', $game)" class="max-w-3xl" />
</div>
@endsection

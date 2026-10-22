@extends('layouts.public')

@php
    $streams = $match->normalizedStreams();
    $galleries = $match->publishedGalleries->filter(fn ($gallery) => $gallery->photos->isNotEmpty());
    $winnerId = $match->winner()?->id;
    $sides = [
        [$match->home, $match->homeName(), $match->home_sets],
        [$match->away, $match->awayName(), $match->away_sets],
    ];
@endphp

@section('title', $match->homeName().' – '.$match->awayName().' · '.$tournament->fullName())
@section('description', $match->mediaLabel())

@section('content')
<section class="relative isolate -mt-16 overflow-hidden bg-gradient-to-br from-club-navy via-club-navy to-sky-club-dark pt-16 text-white lg:-mt-20 lg:pt-20">
    <div class="net-pattern absolute inset-0 -z-10 opacity-60" aria-hidden="true"></div>
    <div class="container-site max-w-5xl pt-24 pb-14 lg:pt-32">
        <a href="{{ route('tournaments.show', $tournament) }}" class="text-sm text-white/70 transition hover:text-white">← {{ $tournament->fullName() }}</a>
        <p class="eyebrow mt-6 text-sky-club-light">{{ collect([$match->category?->name, $match->title()])->filter()->implode(' · ') }}</p>
        <h1 class="sr-only">{{ $match->homeName() }} – {{ $match->awayName() }}</h1>
        @if($match->starts_at || $match->court)
            <p class="mt-2 text-sm text-white/80">{{ $match->starts_at?->translatedFormat('l, d F Y · H:i') }}@if($match->court) · {{ $match->court }}@endif</p>
        @endif

        <div class="mt-10 grid grid-cols-[1fr_auto_1fr] items-center gap-4 sm:gap-8">
            @foreach($sides as $index => [$participant, $name, $sets])
                <div @class(['flex flex-col items-center gap-3 text-center', 'order-3' => $index === 1, 'opacity-60' => $winnerId && $participant?->id !== $winnerId])>
                    <x-avatar :photo="$participant?->logoPath()" :name="$name" class="h-20 w-20 rounded-full bg-white bg-none! text-2xl ring-4 ring-white/10 sm:h-28 sm:w-28 [&>img]:object-contain [&>img]:p-2" />
                    <p class="font-display text-lg leading-tight font-bold uppercase sm:text-2xl">{{ $name }}</p>
                </div>
                @if($index === 0)
                    <div class="order-2 text-center">
                        @if($match->home_sets !== null && $match->away_sets !== null)
                            <p class="font-display text-5xl font-bold tabular-nums sm:text-7xl">{{ $match->home_sets }}<span class="mx-2 opacity-40">:</span>{{ $match->away_sets }}</p>
                        @else
                            <p class="font-display text-3xl font-bold opacity-60">{{ __('vs') }}</p>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>

        @if($match->setsLabel())
            <p class="mt-6 text-center text-sm text-white/70 tabular-nums">({{ $match->setsLabel() }})</p>
        @endif

        <div class="mt-8 flex justify-center">
            @livewire('like-button', ['type' => 'tournament_match', 'id' => $match->id, 'dark' => true], key('tournament-match-like-'.$match->id))
        </div>
    </div>
</section>

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

    @if(! count($streams) && $galleries->isEmpty())
        <p class="rounded-3xl bg-zinc-100 p-8 text-zinc-600">{{ __('Nie ma jeszcze transmisji ani zdjęć z tego meczu.') }}</p>
    @endif

    <a href="{{ route('tournaments.show', $tournament) }}" class="btn-dark">← {{ __('Wróć do turnieju') }}</a>
</div>
@endsection

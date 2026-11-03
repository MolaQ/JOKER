@extends('layouts.public')

@section('title', trim($tournament->fullName().($category && $tournament->categories()->count() > 1 ? ' - '.$category->name : '')))
@section('description', trim($tournament->fullName().' - '.$tournament->dateRangeLabel().'.'.$tournament->start_date->format('Y').' '.($tournament->city ?? '').'. '.__('Drużyny, grupy, wyniki, drabinka i informator dla gości.')))

@section('content')
@php
    // W dniach turnieju kibic przychodzi po wyniki: na telefonie nagłówek jest niski, a zakładki z meczami widać od razu.
    $playing = today()->between($tournament->start_date->copy()->startOfDay(), ($tournament->end_date ?? $tournament->start_date)->copy()->endOfDay());
@endphp
<section class="relative isolate -mt-16 overflow-hidden bg-gradient-to-br from-club-navy via-club-navy to-sky-club-dark pt-16 text-white lg:-mt-20 lg:pt-20">
    @if($tournament->image_path)
        <img src="{{ \App\Support\Media::url($tournament->image_path) }}" alt="" class="absolute inset-0 -z-20 h-full w-full object-cover opacity-30" onerror="this.remove()">
    @endif
    <div class="net-pattern absolute inset-0 -z-10 opacity-60" aria-hidden="true"></div>

    <div @class(['container-site lg:pt-28 lg:pb-16', 'pt-20 pb-12' => ! $playing, 'pt-6 pb-6 sm:pt-20 sm:pb-12' => $playing])>
        <p class="eyebrow">🏆 {{ $tournament->dateRangeLabel() }}.{{ $tournament->start_date->format('Y') }}@if($tournament->city) · {{ $tournament->city }}@endif @if($tournament->venue) · {{ $tournament->venue }}@endif</p>
        @if($tournament->edition)
            <p @class(['font-display font-semibold text-sky-club-light uppercase', 'mt-4 text-2xl' => ! $playing, 'mt-2 text-lg sm:mt-4 sm:text-2xl' => $playing])>{{ $tournament->edition }} {{ __('edycja') }}</p>
        @endif
        <h1 @class(['mt-2 max-w-4xl', 'heading-xl' => ! $playing, 'font-display text-3xl leading-none font-bold uppercase sm:text-5xl lg:text-6xl' => $playing])>{{ $tournament->name }}</h1>
        @if($tournament->description)
            <p @class(['mt-5 max-w-2xl text-lg text-white/80', 'hidden sm:block' => $playing])>{{ $tournament->description }}</p>
        @endif
        <div @class(['flex flex-wrap items-center gap-3', 'mt-6' => ! $playing, 'mt-4 sm:mt-6' => $playing])>
            @if($tournament->canBeScoredBy(auth()->user()))
                <a href="{{ route('tournaments.score', $tournament) }}" class="btn-primary">✍️ {{ __('Wpisz wyniki') }}</a>
            @endif
            @livewire('like-button', ['type' => 'tournament', 'id' => $tournament->id, 'dark' => true], key('tournament-like-'.$tournament->id))
        </div>
    </div>
</section>

@livewire('tournament-page', ['tournament' => $tournament, 'categoryId' => $category?->id])

@php $editions = $tournament->otherEditions(); @endphp
@if($editions->isNotEmpty())
    <section class="container-site pb-16">
        <h2 class="heading-md">{{ __('Poprzednie edycje') }}</h2>
        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($editions as $edition)
                <a href="{{ route('tournaments.show', $edition) }}" class="card card-hover p-5">
                    <p class="text-sm text-zinc-500">{{ $edition->start_date->format('Y') }}</p>
                    <p class="mt-1 font-semibold text-zinc-900">{{ $edition->fullName() }}</p>
                </a>
            @endforeach
        </div>
    </section>
@endif
@endsection

@section('before_sponsors')
    <x-tournament-partners :tournament="$tournament" />
@endsection

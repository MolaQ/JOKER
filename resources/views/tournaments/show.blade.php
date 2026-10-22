@extends('layouts.public')

@section('title', $tournament->fullName())
@section('description', trim($tournament->fullName().' - '.$tournament->dateRangeLabel().'.'.$tournament->start_date->format('Y').' '.($tournament->city ?? '').'. '.__('Drużyny, grupy, wyniki, drabinka i informator dla gości.')))

@section('content')
<section class="relative isolate -mt-16 overflow-hidden bg-gradient-to-br from-club-navy via-club-navy to-sky-club-dark pt-16 text-white lg:-mt-20 lg:pt-20">
    @if($tournament->image_path)
        <img src="{{ \App\Support\Media::url($tournament->image_path) }}" alt="" class="absolute inset-0 -z-20 h-full w-full object-cover opacity-30" onerror="this.remove()">
    @endif
    <div class="net-pattern absolute inset-0 -z-10 opacity-60" aria-hidden="true"></div>

    <div class="container-site pt-20 pb-12 lg:pt-28 lg:pb-16">
        <p class="eyebrow">🏆 {{ $tournament->dateRangeLabel() }}.{{ $tournament->start_date->format('Y') }}@if($tournament->city) · {{ $tournament->city }}@endif @if($tournament->venue) · {{ $tournament->venue }}@endif</p>
        @if($tournament->edition)
            <p class="mt-4 font-display text-2xl font-semibold text-sky-club-light uppercase">{{ $tournament->edition }} {{ __('edycja') }}</p>
        @endif
        <h1 class="heading-xl mt-2 max-w-4xl">{{ $tournament->name }}</h1>
        @if($tournament->description)
            <p class="mt-5 max-w-2xl text-lg text-white/80">{{ $tournament->description }}</p>
        @endif
        <div class="mt-6">
            @livewire('like-button', ['type' => 'tournament', 'id' => $tournament->id, 'dark' => true], key('tournament-like-'.$tournament->id))
        </div>
    </div>
</section>

@livewire('tournament-page', ['tournament' => $tournament])

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

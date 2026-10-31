@extends('layouts.public')

@section('title', __('Wpisz wyniki').' · '.$tournament->fullName())
@section('robots', 'noindex, nofollow')

@section('content')
<section class="bg-club-navy pt-24 pb-6 text-white lg:pt-28">
    <div class="container-site">
        <a href="{{ route('tournaments.show', $tournament) }}" class="text-sm text-white/70 hover:text-white">← {{ $tournament->fullName() }}</a>
        <h1 class="heading-md mt-2">{{ __('Wpisz wyniki') }}</h1>
    </div>
</section>
<livewire:tournament-scorer :tournament="$tournament" />
@endsection

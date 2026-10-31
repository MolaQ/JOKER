@extends('layouts.public')

@section('title', __('Mecze'))
@section('description', __('Terminarz i wyniki meczów wszystkich drużyn :club: ligi seniorskie i rozgrywki młodzieżowe.', ['club' => $site['club_name']]))

@section('content')
<section class="container-site pt-14 pb-6 lg:pt-20">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">{{ __('Wszystkie drużyny') }} · {{ __('sezon') }} {{ $currentSeason?->name ?? date('Y') }}</p>
            <h1 class="heading-xl mt-4">{{ __('Mecze') }}</h1>
        </div>
        <a href="{{ route('standings') }}" class="link-underline pb-2 text-sm font-semibold">{{ __('Tabele ligowe') }} →</a>
    </div>
</section>

@livewire('games-browser')
@endsection

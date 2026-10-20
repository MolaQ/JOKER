@extends('layouts.public')

@section('title', __('Tabele ligowe'))
@section('description', __('Aktualne tabele rozgrywek, w których grają drużyny :club.', ['club' => $site['club_name']]))

@section('content')
<section class="container-site pt-14 pb-6 lg:pt-20">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">{{ __('Sezon') }} {{ $currentSeason?->name ?? date('Y') }}</p>
            <h1 class="heading-xl mt-4">{{ __('Tabele') }}</h1>
        </div>
        <a href="{{ route('schedule.index') }}" class="link-underline pb-2 text-sm font-semibold">{{ __('Wszystkie mecze') }} →</a>
    </div>
</section>

@livewire('standings-browser')
@endsection

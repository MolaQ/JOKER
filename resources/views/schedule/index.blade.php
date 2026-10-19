@extends('layouts.public')

@section('title', 'Mecze')

@section('content')
<section class="container-site pt-14 pb-6 lg:pt-20">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Wszystkie drużyny · sezon {{ $currentSeason?->name ?? date('Y') }}</p>
            <h1 class="heading-xl mt-4">Mecze</h1>
        </div>
        <a href="{{ route('standings') }}" class="link-underline pb-2 text-sm font-semibold">Tabele ligowe →</a>
    </div>
</section>

@livewire('games-browser')
@endsection

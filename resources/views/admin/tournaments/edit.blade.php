@extends('admin.layout')

@section('title', $tournament->fullName())

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('admin.tournaments.index') }}" class="text-sm text-sky-700 hover:underline">← Turnieje</a>
        <h1 class="mt-2 text-3xl font-bold text-gray-900">{{ $tournament->fullName() }}</h1>
        <p class="mt-2 text-sm text-gray-700">{{ $tournament->dateRangeLabel() }} · strona turnieju z kategoriami wiekowymi, grupami, drabinką i informatorem.</p>
    </div>

    {{-- Na telefonie panel od krawędzi do krawędzi: karty meczów i grup dostają całą szerokość ekranu --}}
    <div class="-mx-4 bg-white px-3 py-4 shadow ring-1 ring-zinc-200 sm:mx-0 sm:rounded-lg sm:p-6">
        @livewire('admin.tournament-editor', ['tournament' => $tournament])
    </div>
</div>
@endsection

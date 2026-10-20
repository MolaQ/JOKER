@extends('admin.layout')

@section('title', $tournament->fullName())

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('admin.tournaments.index') }}" class="text-sm text-blue-600 hover:underline">← Turnieje</a>
        <h1 class="mt-2 text-3xl font-bold text-gray-900">{{ $tournament->fullName() }}</h1>
        <p class="mt-2 text-sm text-gray-700">{{ $tournament->dateRangeLabel() }} · strona turnieju z kategoriami wiekowymi, grupami, drabinką i informatorem.</p>
    </div>

    <div class="rounded-lg bg-white p-4 shadow ring-1 ring-black/5 sm:p-6">
        @livewire('admin.tournament-editor', ['tournament' => $tournament])
    </div>
</div>
@endsection

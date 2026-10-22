@extends('admin.layout')

@section('title', 'Propozycje treści')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Propozycje treści</h1>
        <p class="mt-2 max-w-3xl text-sm text-gray-700">Artykuły, zdjęcia i transmisje zaproponowane przez rodziców i zawodników. Zatwierdzony artykuł od razu jest aktualnością, zdjęcia trafiają do galerii meczu, a transmisja do meczu.</p>
    </div>

    <div class="overflow-hidden rounded-lg shadow ring-1 ring-black/5">
        @livewire('admin.content-proposal-manager')
    </div>
</div>
@endsection

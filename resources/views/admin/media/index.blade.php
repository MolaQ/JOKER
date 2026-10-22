@extends('admin.layout')

@section('title', 'Transmisje i galerie')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center mb-6">
        <div class="sm:flex-auto">
            <h1 class="text-3xl font-bold text-gray-900">Transmisje i galerie</h1>
            <p class="mt-2 text-sm text-gray-700">Wybierz mecz ligowy albo turniejowy i dołącz do niego linki do transmisji (YouTube, Facebook) oraz galerie zdjęć.</p>
        </div>
    </div>

    @livewire('admin.match-media-manager')
</div>
@endsection

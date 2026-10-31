@extends('admin.layout')

@section('title', 'Minisiatkówka')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Minisiatkówka</h1>
        <p class="mt-2 max-w-3xl text-sm text-gray-700">Grupy (czwórki, trójki, dwójki, singielki), dzieci i wyjazdy na turnieje. Po turnieju otwórz wyjazd na telefonie i wpisz miejsce, skład i zdjęcie, a wynik od razu pojawi się na stronie.</p>
    </div>

    @livewire('admin.mini-volleyball-manager')
</div>
@endsection

@extends('admin.layout')

@section('title', 'Galerie')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Galerie</h1>
        <p class="mt-2 max-w-3xl text-sm text-gray-700">Galerie z meczów i wydarzeń. Na zdjęciach oznaczasz drużyny, zawodników i trenerów, a oznaczenia prowadzą do ich profili. Oznaczenia zawodników bez zgody na wizerunek są ukryte na stronie.</p>
    </div>

    <div class="mb-6">@livewire('admin.photo-tag-review')</div>
    @livewire('admin.gallery-manager')
</div>
@endsection

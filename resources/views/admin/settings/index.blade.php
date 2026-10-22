@extends('admin.layout')

@section('title', 'Wygląd i ustawienia')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Wygląd i ustawienia</h1>
        <p class="mt-2 text-sm text-gray-700">Kolory, górny pasek, menu, teksty na stronie głównej, kontakt i stopka. Zmiany widać na stronie od razu po zapisaniu.</p>
    </div>

    <div class="overflow-clip shadow ring-1 ring-zinc-200 sm:rounded-lg">
        @livewire('admin.site-settings-manager')
    </div>
</div>
@endsection

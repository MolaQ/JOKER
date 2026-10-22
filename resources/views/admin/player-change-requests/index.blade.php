@extends('admin.layout')

@section('title', 'Zmiany od zawodników')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Zmiany od zawodników</h1>
        <p class="mt-2 text-sm text-gray-700">Nowe zdjęcia i dane zaproponowane przez zawodników oraz ich rodziców. Zatwierdzone od razu pojawiają się w profilu.</p>
    </div>

    <div class="mb-6">@livewire('admin.photo-tag-review')</div>

    <div class="overflow-hidden rounded-lg shadow ring-1 ring-black/5">
        @livewire('admin.player-change-request-manager')
    </div>
</div>
@endsection

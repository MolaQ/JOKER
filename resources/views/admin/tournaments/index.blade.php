@extends('admin.layout')

@section('title', 'Turnieje')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Turnieje</h1>
        <p class="mt-2 text-sm text-gray-700">Turnieje w trakcie sezonu, np. Turniej im. Włodzimierza Winklera albo wyjazdy na turnieje innych klubów.</p>
    </div>

    <div class="overflow-hidden rounded-lg shadow ring-1 ring-black/5">
        @livewire('admin.tournament-manager')
    </div>
</div>
@endsection

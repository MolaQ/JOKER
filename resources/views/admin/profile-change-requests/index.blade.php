@extends('admin.layout')

@section('title', 'Wnioski zmian profili')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6 sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-3xl font-bold text-gray-900">Wnioski zmian profili</h1>
            <p class="mt-2 text-sm text-gray-700">Akceptowanie lub odrzucanie propozycji zmian zgłaszanych przez zawodników, rodziców i kibiców.</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg shadow ring-1 ring-black/5">
        @livewire('admin.profile-change-request-manager')
    </div>
</div>
@endsection

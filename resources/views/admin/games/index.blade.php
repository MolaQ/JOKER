@extends('admin.layout')

@section('title', 'Zarządzanie meczami')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center mb-6">
        <div class="sm:flex-auto">
            <h1 class="text-3xl font-bold text-gray-900">Mecze</h1>
            <p class="mt-2 text-sm text-gray-700">Zarządzaj harmonogramem i wynikami meczów.</p>
        </div>
    </div>

    <div class="flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                    @livewire('admin.game-manager')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

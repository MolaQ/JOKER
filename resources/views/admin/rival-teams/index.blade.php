@extends('admin.layout')

@section('title', 'Drużyny rywali')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center mb-6">
        <div class="sm:flex-auto">
            <h1 class="text-3xl font-bold text-gray-900">Drużyny rywali</h1>
            <p class="mt-2 text-sm text-gray-700">Baza drużyn przeciwników używana w terminarzu i tabeli ligowej.</p>
        </div>
    </div>

    <div class="admin-card">
        @livewire('admin.rival-team-manager')
    </div>
</div>
@endsection

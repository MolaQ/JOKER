@extends('admin.layout')

@section('title', 'Zarządzanie sezonami')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center mb-6">
        <div class="sm:flex-auto">
            <h1 class="text-3xl font-bold text-gray-900">Sezony</h1>
            <p class="mt-2 text-sm text-gray-700">Sezony rozgrywkowe klubu.</p>
        </div>
    </div>

    <div class="admin-card">
        @livewire('admin.season-manager')
    </div>
</div>
@endsection

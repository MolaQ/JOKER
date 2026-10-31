@extends('admin.layout')

@section('title', 'Zarządzanie sponsorami')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center mb-6">
        <div class="sm:flex-auto">
            <h1 class="text-3xl font-bold text-gray-900">Sponsorzy</h1>
            <p class="mt-2 text-sm text-gray-700">Zarządzaj partnerami i sponsorami klubu.</p>
        </div>
    </div>

    <div class="admin-card">
        @livewire('admin.sponsor-manager')
    </div>
</div>
@endsection

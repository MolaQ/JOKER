@extends('admin.layout')

@section('title', 'Wyróżnienia i certyfikaty')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Wyróżnienia i certyfikaty</h1>
        <p class="mt-2 text-sm text-gray-700">Pokazują się w stopce każdej strony (z logo, jeśli je dodasz) i trafiają do danych o klubie dla Google.</p>
    </div>

    @livewire('admin.award-manager')
</div>
@endsection

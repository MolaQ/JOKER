@extends('admin.layout')

@section('title', 'Kreator startowy')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mx-auto mb-6 max-w-3xl">
        <a href="{{ route('admin.settings.index') }}" class="text-sm font-semibold text-sky-700 hover:text-sky-900">← System</a>
        <h1 class="mt-2 text-3xl font-bold text-gray-900">Kreator startowy</h1>
        <p class="mt-2 text-sm text-gray-700">Najważniejsze ustawienia klubu w czterech krokach: nazwa, herb i kolory, kontakt, drużyny. Resztę zmienisz potem w System.</p>
    </div>

    @livewire('admin.setup-wizard')
</div>
@endsection

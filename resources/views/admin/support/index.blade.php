@extends('admin.layout')

@section('title', 'Wspieraj klub')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Wspieraj klub</h1>
        <p class="mt-2 max-w-3xl text-sm text-gray-700">Pakiety sponsorskie i gadżety na stronie „Wspieraj klub”. Numer KRS do 1,5% podatku i konto do darowizn wpiszesz w Wygląd i ustawienia → Wspieraj klub.</p>
    </div>

    @livewire('admin.support-manager')
</div>
@endsection

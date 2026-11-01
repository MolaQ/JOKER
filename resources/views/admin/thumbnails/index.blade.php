@extends('admin.layout')

@section('title', 'Miniatury transmisji')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Miniatury transmisji</h1>
        <p class="mt-2 text-sm text-gray-700">Para grafik na YouTube: zapowiedź meczu i podsumowanie z wynikiem. Wybierz mecz z bazy albo wpisz dane ręcznie, wygląd zapisz jako szablon rozgrywek.</p>
    </div>

    @livewire('admin.thumbnail-studio')
</div>
@endsection

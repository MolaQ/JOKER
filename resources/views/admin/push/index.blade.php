@extends('admin.layout')

@section('title', 'Powiadomienia')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Powiadomienia</h1>
        <p class="mt-2 max-w-3xl text-sm text-gray-700">Wiadomość, która wyskoczy na telefonach i komputerach kibiców z włączonymi powiadomieniami, np. o zmianie godziny meczu albo zaproszenie na turniej.</p>
    </div>

    @livewire('admin.push-composer')
</div>
@endsection

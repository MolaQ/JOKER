@extends('layouts.public')

@section('title', 'Mój profil - Joker Piła')

@section('content')
<div class="container mx-auto max-w-3xl px-4 py-12">
    <h1 class="mb-6 text-3xl font-bold text-gray-900">Mój profil</h1>

    <div class="rounded-xl bg-white p-6 shadow">
        <form method="POST" action="{{ route('profile.request-change') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700">Imię i nazwisko</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Instagram</label>
                <input type="text" name="instagram" value="{{ old('instagram', $user->instagram) }}" placeholder="https://instagram.com/..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Dodatkowe informacje</label>
                <x-rich-text-editor name="additional_info" :value="old('additional_info', (string) $user->additional_info)" placeholder="Opisz zmiany, które chcesz wprowadzić..." height="min-h-[180px]" />
            </div>

            <button type="submit" class="rounded-lg bg-blue-900 px-5 py-2 text-white hover:bg-blue-800">Wyślij propozycję zmian</button>
        </form>

        @if($latestRequest)
            <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm">
                Ostatni wniosek: <strong>{{ $latestRequest->status }}</strong>
                @if($latestRequest->review_note)
                    <div class="mt-1 text-gray-600">Notatka: {{ $latestRequest->review_note }}</div>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection

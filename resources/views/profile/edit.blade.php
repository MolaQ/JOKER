@extends('layouts.public')

@section('title', __('Mój profil'))

@section('robots', 'noindex, follow')

@section('content')
<div class="container mx-auto max-w-3xl px-4 py-12">
    <h1 class="mb-6 text-3xl font-bold text-zinc-900">{{ __('Mój profil') }}</h1>

    @if($players->isNotEmpty())
        <div class="mb-6 rounded-xl bg-white p-6 shadow">
            <h2 class="text-lg font-semibold text-zinc-900">{{ __('Profile zawodników') }}</h2>
            <p class="mt-1 text-sm text-zinc-500">{{ __('Na stronie zawodnika kliknij „Zaproponuj zmiany”, aby wysłać nowe zdjęcie lub dane do zatwierdzenia.') }}</p>
            <ul class="mt-4 divide-y divide-zinc-100">
                @foreach($players as $player)
                    <li class="flex items-center justify-between gap-3 py-3">
                        <span><span class="font-semibold">{{ $player->full_name }}</span> <span class="text-sm text-zinc-500">{{ $player->team?->name }}</span></span>
                        <a href="{{ route('players.show', $player) }}" class="text-sm font-semibold text-sky-club-dark hover:underline">{{ __('Otwórz profil') }} →</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl bg-white p-6 shadow">
        <form method="POST" action="{{ route('profile.request-change') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('Imię i nazwisko') }}</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700">Instagram</label>
                <input type="text" name="instagram" value="{{ old('instagram', $user->instagram) }}" placeholder="https://instagram.com/..." class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('Dodatkowe informacje') }}</label>
                <x-rich-text-editor name="additional_info" :value="old('additional_info', (string) $user->additional_info)" placeholder="{{ __('Opisz zmiany, które chcesz wprowadzić...') }}" height="min-h-[180px]" />
            </div>

            <button type="submit" class="rounded-lg bg-ink px-5 py-2 text-white hover:bg-graphite">{{ __('Wyślij propozycję zmian') }}</button>
        </form>

        @if($latestRequest)
            <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-sm">
                {{ __('Ostatni wniosek') }}: <strong>{{ __($latestRequest->status) }}</strong>
                @if($latestRequest->review_note)
                    <div class="mt-1 text-zinc-600">{{ __('Notatka') }}: {{ $latestRequest->review_note }}</div>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection

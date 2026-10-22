@extends('layouts.public')

@section('title', __('Mój profil'))

@section('robots', 'noindex, follow')

@section('content')
<div class="container mx-auto max-w-3xl px-4 py-12">
    <h1 class="mb-6 text-3xl font-bold text-zinc-900">{{ __('Mój profil') }}</h1>

    <div class="mb-6 rounded-xl bg-white p-6 shadow">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('Konto') }}</h2>
        @if(! $user->hasVerifiedEmail())
            <div class="mt-3 rounded-lg bg-amber-50 p-4 text-sm text-amber-900">
                {{ __('Potwierdź adres e-mail: kliknij link, który wysłaliśmy na :email.', ['email' => $user->email]) }}
                <form method="POST" action="{{ route('verification.send') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="font-semibold underline">{{ __('Wyślij link jeszcze raz') }}</button>
                </form>
            </div>
        @endif
        @if($user->is_active)
            <p class="mt-3 text-sm text-green-700">✓ {{ __('Konto aktywne') }}</p>
        @else
            <p class="mt-3 rounded-lg bg-zinc-50 p-4 text-sm text-zinc-700">
                @if($user->activated_at)
                    {{ __('Konto jest nieaktywne. Jeśli to pomyłka, napisz do klubu.') }}
                @elseif($user->requested_role === \App\UserRole::Fan)
                    {{ __('Konto kibica zacznie działać po potwierdzeniu adresu e-mail.') }}
                @else
                    {{ __('Konto czeka na aktywację przez klub. Trener lub administrator powiąże je z profilem (:role) i dostaniesz e-mail.', ['role' => __($user->requested_role?->label() ?? 'Kibic')]) }}
                @endif
            </p>
        @endif
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach($user->roles() as $role)
                <span class="rounded-full bg-sky-club/10 px-3 py-1 text-sm font-medium text-sky-club-dark">{{ __($role->label()) }}</span>
            @endforeach
        </div>
        @if($teams->isNotEmpty())
            <p class="mt-3 text-sm text-zinc-600">{{ __('Drużyny') }}: {{ $teams->pluck('name')->join(', ') }}</p>
        @endif
        @if($user->canAccessPanel())
            <a href="{{ route('admin.dashboard') }}" class="mt-4 inline-flex rounded-lg bg-ink px-4 py-2 text-sm font-semibold text-white hover:bg-graphite">{{ __('Panel klubu') }} →</a>
        @endif
    </div>

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

    @if($proposals->isNotEmpty())
        <div class="mb-6 rounded-xl bg-white p-6 shadow">
            <h2 class="text-lg font-semibold text-zinc-900">{{ __('Moje propozycje') }}</h2>
            <ul class="mt-3 divide-y divide-zinc-100 text-sm">
                @foreach($proposals as $proposal)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                        <span>{{ $proposal->isPhoto() ? __('Zdjęcie') : __('Dane') }}: {{ $proposal->player?->full_name }} <span class="text-zinc-500">{{ $proposal->created_at->format('d.m.Y') }}</span></span>
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs font-semibold',
                            'bg-amber-100 text-amber-800' => $proposal->status === 'pending',
                            'bg-green-100 text-green-800' => $proposal->status === 'approved',
                            'bg-red-100 text-red-800' => $proposal->status === 'rejected',
                        ])>{{ ['pending' => __('czeka'), 'approved' => __('zatwierdzona'), 'rejected' => __('odrzucona')][$proposal->status] ?? $proposal->status }}</span>
                        @if($proposal->review_note)<span class="w-full text-zinc-500">{{ $proposal->review_note }}</span>@endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 rounded-xl bg-white p-6 shadow">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('Zmiana hasła') }}</h2>
        <form method="POST" action="{{ route('profile.password') }}" class="mt-4 grid gap-4 sm:grid-cols-3">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('Obecne hasło') }}</label>
                <input type="password" name="current_password" autocomplete="current-password" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 shadow-sm">
                @error('current_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('Nowe hasło') }}</label>
                <input type="password" name="password" autocomplete="new-password" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 shadow-sm">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('Powtórz hasło') }}</label>
                <input type="password" name="password_confirmation" autocomplete="new-password" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 shadow-sm">
            </div>
            <div class="sm:col-span-3">
                <button type="submit" class="rounded-lg bg-ink px-5 py-2 text-white hover:bg-graphite">{{ __('Zmień hasło') }}</button>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white p-6 shadow">
        <h2 class="mb-4 text-lg font-semibold text-zinc-900">{{ __('Dane konta') }}</h2>
        <form method="POST" action="{{ route('profile.request-change') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('Imię i nazwisko') }}</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700">Instagram</label>
                <input type="text" name="instagram" value="{{ old('instagram', $user->instagram) }}" placeholder="https://instagram.com/..." class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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

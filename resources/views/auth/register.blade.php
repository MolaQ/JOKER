@extends('layouts.public')

@section('title', __('Załóż konto'))

@section('robots', 'noindex, follow')

@php
    $field = 'w-full px-4 py-3 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-sky-club focus:border-transparent';
@endphp

@section('content')
<section class="container-site pt-14 pb-6 lg:pt-20">
    <p class="eyebrow">{{ __('Konto w serwisie klubu') }}</p>
    <h1 class="heading-xl mt-4">{{ __('Załóż konto') }}</h1>
</section>

<section class="container-site py-12">
    <div class="max-w-xl mx-auto">
        <div class="bg-white rounded-3xl ring-1 ring-zinc-200/70 p-8">
            <form method="POST" action="{{ route('register') }}" class="space-y-6" x-data="{ kind: @js(old('kind', 'fan')) }">
                @csrf
                <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

                <fieldset>
                    <legend class="block text-zinc-700 font-semibold mb-2">{{ __('Kim jesteś?') }}</legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach([
                            'fan' => [__('Kibicem'), __('Serduszka i komentarze. Działa po potwierdzeniu e-maila.')],
                            'parent' => [__('Rodzicem zawodnika'), __('Proponujesz dane i zdjęcia dziecka. Konto aktywuje klub.')],
                            'player' => [__('Zawodnikiem'), __('Proponujesz zmiany w swoim profilu. Konto aktywuje klub.')],
                            'trainer' => [__('Trenerem lub w sztabie'), __('Konto aktywuje administrator lub szef klubu.')],
                        ] as $value => [$label, $hint])
                            <label class="flex cursor-pointer gap-3 rounded-xl border border-zinc-200 p-3 has-[:checked]:border-sky-club has-[:checked]:bg-sky-club/5">
                                <input type="radio" name="kind" value="{{ $value }}" x-model="kind" class="mt-1 text-sky-club-dark" @checked(old('kind', 'fan') === $value)>
                                <span><span class="font-semibold text-zinc-900">{{ $label }}</span><span class="block text-xs text-zinc-500">{{ $hint }}</span></span>
                            </label>
                        @endforeach
                    </div>
                    @error('kind')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
                </fieldset>

                <div>
                    <label for="name" class="block text-zinc-700 font-semibold mb-2">{{ __('Imię i nazwisko') }}</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="{{ $field }}">
                    @error('name')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
                </div>

                <div x-show="kind !== 'fan'" x-cloak>
                    <label for="team_id" class="block text-zinc-700 font-semibold mb-2">{{ __('Drużyna') }}</label>
                    <select id="team_id" name="team_id" class="{{ $field }}">
                        <option value="">{{ __('Wybierz drużynę') }}</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" @selected(old('team_id') == $team->id)>{{ $team->name }}</option>
                        @endforeach
                    </select>
                    @error('team_id')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
                </div>

                <div x-show="kind !== 'fan'" x-cloak>
                    <label for="note" class="block text-zinc-700 font-semibold mb-2">
                        <span x-show="kind === 'parent'">{{ __('Imię i nazwisko dziecka (lub dzieci)') }}</span>
                        <span x-show="kind !== 'parent'">{{ __('Coś, co pomoże nam Cię rozpoznać (opcjonalnie)') }}</span>
                    </label>
                    <textarea id="note" name="note" rows="2" class="{{ $field }}">{{ old('note') }}</textarea>
                    @error('note')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="block text-zinc-700 font-semibold mb-2">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="{{ $field }}">
                    @error('email')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password" class="block text-zinc-700 font-semibold mb-2">{{ __('Hasło') }}</label>
                        <input type="password" id="password" name="password" required autocomplete="new-password" class="{{ $field }}">
                        @error('password')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-zinc-700 font-semibold mb-2">{{ __('Powtórz hasło') }}</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="{{ $field }}">
                    </div>
                </div>

                <div class="space-y-3 rounded-xl bg-zinc-50 p-4">
                    @include('account.partials.consents')

                    <label class="flex items-start gap-2 text-sm text-zinc-700" x-show="kind === 'player'" x-cloak>
                        <input type="checkbox" name="age" value="1" class="mt-0.5 rounded border-zinc-300 text-sky-club-dark" @checked(old('age'))>
                        <span>{{ __('Mam ukończone 16 lat albo mój rodzic (opiekun) zgadza się na założenie konta.') }} <span class="text-red-600">*</span></span>
                    </label>
                    @error('age')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    <p class="text-xs text-zinc-500">{{ __('Pola oznaczone * są warunkiem założenia konta.') }}</p>
                </div>

                <button type="submit" class="w-full bg-ink text-white py-3 rounded-lg hover:bg-graphite transition font-semibold">{{ __('Załóż konto') }}</button>

                <p class="text-center text-sm text-zinc-600">{{ __('Masz już konto?') }} <a href="{{ route('login') }}" class="font-semibold text-sky-club-dark hover:underline">{{ __('Zaloguj się') }}</a></p>
            </form>
        </div>
    </div>
</section>
@endsection

@extends('layouts.public')

@section('title', __('Nowe hasło'))

@section('robots', 'noindex, nofollow')

@php
    $field = 'w-full px-4 py-3 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-sky-club focus:border-transparent';
@endphp

@section('content')
<section class="container-site pt-14 pb-6 lg:pt-20">
    <h1 class="heading-xl">{{ __('Nowe hasło') }}</h1>
</section>

<section class="container-site py-12">
    <div class="max-w-md mx-auto bg-white rounded-3xl ring-1 ring-zinc-200/70 p-8">
        <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label for="email" class="block text-zinc-700 font-semibold mb-2">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required class="{{ $field }}">
                @error('email')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-zinc-700 font-semibold mb-2">{{ __('Nowe hasło') }}</label>
                <input type="password" id="password" name="password" required autocomplete="new-password" class="{{ $field }}">
                @error('password')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-zinc-700 font-semibold mb-2">{{ __('Powtórz hasło') }}</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="{{ $field }}">
            </div>
            <button type="submit" class="w-full bg-ink text-white py-3 rounded-lg hover:bg-graphite transition font-semibold">{{ __('Zapisz hasło') }}</button>
        </form>
    </div>
</section>
@endsection

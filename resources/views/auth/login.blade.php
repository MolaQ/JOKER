@extends('layouts.public')

@section('title', __('Logowanie'))

@section('robots', 'noindex, follow')

@section('content')
<section class="container-site pt-14 pb-6 lg:pt-20">
    <p class="eyebrow">{{ __('Zaloguj się do swojego konta') }}</p>
    <h1 class="heading-xl mt-4">{{ __('Logowanie') }}</h1>
</section>

<!-- Login Form -->
<section class="container-site py-12">
    <div class="max-w-md mx-auto">
        <div class="bg-white rounded-3xl ring-1 ring-zinc-200/70 p-8">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email -->
                <div class="mb-6">
                    <label for="email" class="block text-zinc-700 font-semibold mb-2">
                        Email
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="w-full px-4 py-3 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-sky-club focus:border-transparent @error('email') border-red-500 @enderror"
                        placeholder="{{ __('twoj@email.pl') }}"
                    >
                    @error('email')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label for="password" class="block text-zinc-700 font-semibold mb-2">
                        {{ __('Hasło') }}
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="w-full px-4 py-3 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-sky-club focus:border-transparent @error('password') border-red-500 @enderror"
                        placeholder="••••••••"
                    >
                    @error('password')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="mb-6">
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            name="remember"
                            class="w-4 h-4 text-sky-club-dark border-zinc-300 rounded focus:ring-sky-club"
                        >
                        <span class="ml-2 text-zinc-700">{{ __('Zapamiętaj mnie') }}</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="w-full bg-ink text-white py-3 rounded-lg hover:bg-graphite transition font-semibold mb-4"
                >
                    {{ __('Zaloguj się') }}
                </button>

                <div class="flex flex-wrap justify-between gap-2 text-sm">
                    <a href="{{ route('password.request') }}" class="text-sky-club-dark hover:underline">{{ __('Nie pamiętasz hasła?') }}</a>
                    <a href="{{ route('register') }}" class="font-semibold text-sky-club-dark hover:underline">{{ __('Załóż konto') }}</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@extends('layouts.public')

@section('title', 'Logowanie - Joker Piła')

@section('content')
<!-- Login Header -->
<div class="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white py-16">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">Logowanie</h1>
        <p class="text-xl text-blue-100">
            Zaloguj się do swojego konta
        </p>
    </div>
</div>

<!-- Login Form -->
<section class="container mx-auto px-4 py-12">
    <div class="max-w-md mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email -->
                <div class="mb-6">
                    <label for="email" class="block text-gray-700 font-semibold mb-2">
                        Email
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-900 focus:border-transparent @error('email') border-red-500 @enderror"
                        placeholder="twoj@email.pl"
                    >
                    @error('email')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 font-semibold mb-2">
                        Hasło
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-900 focus:border-transparent @error('password') border-red-500 @enderror"
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
                            class="w-4 h-4 text-blue-900 border-gray-300 rounded focus:ring-blue-900"
                        >
                        <span class="ml-2 text-gray-700">Zapamiętaj mnie</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="w-full bg-blue-900 text-white py-3 rounded-lg hover:bg-blue-800 transition font-semibold mb-4"
                >
                    Zaloguj się
                </button>

                <!-- Info -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                    <p class="text-sm text-blue-900">
                        <strong>Dane testowe:</strong><br>
                        Email: admin@jokerpila.pl<br>
                        Hasło: password
                    </p>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

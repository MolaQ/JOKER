@extends('layouts.public')

@section('title', __('Nie pamiętasz hasła?'))

@section('robots', 'noindex, follow')

@section('content')
<section class="container-site pt-14 pb-6 lg:pt-20">
    <h1 class="heading-xl">{{ __('Nie pamiętasz hasła?') }}</h1>
    <p class="mt-4 max-w-xl text-zinc-600">{{ __('Podaj adres e-mail konta. Wyślemy link do ustawienia nowego hasła.') }}</p>
</section>

<section class="container-site py-12">
    <div class="max-w-md mx-auto bg-white rounded-3xl ring-1 ring-zinc-200/70 p-8">
        <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
            @csrf
            <div>
                <label for="email" class="block text-zinc-700 font-semibold mb-2">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus class="w-full px-4 py-3 border border-zinc-300 rounded-lg focus:ring-2 focus:ring-sky-club focus:border-transparent">
                @error('email')<p class="text-red-600 text-sm mt-2">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="w-full bg-ink text-white py-3 rounded-lg hover:bg-graphite transition font-semibold">{{ __('Wyślij link') }}</button>
            <p class="text-center text-sm"><a href="{{ route('login') }}" class="text-sky-club-dark hover:underline">{{ __('Wróć do logowania') }}</a></p>
        </form>
    </div>
</section>
@endsection

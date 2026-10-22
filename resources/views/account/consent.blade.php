@extends('layouts.public')

@section('title', __('Zgoda na przetwarzanie danych'))

@section('robots', 'noindex, follow')

@section('content')
<section class="container-site py-14 lg:py-20">
    <div class="mx-auto max-w-xl">
        <p class="eyebrow">{{ __('Twoje konto') }}</p>
        <h1 class="heading-lg mt-4">{{ __('Zgoda na przetwarzanie danych') }}</h1>
        <p class="mt-4 text-zinc-600">{{ __('Konto w serwisie klubu działa tylko z tą zgodą. Bez niej możesz usunąć konto razem z danymi.') }}</p>

        <form method="POST" action="{{ route('account.consent.store') }}" class="mt-8 space-y-4 rounded-3xl bg-white p-6 ring-1 ring-zinc-200/70 sm:p-8">
            @csrf
            @include('account.partials.consents')

            <button type="submit" class="w-full rounded-lg bg-ink py-3 font-semibold text-white transition hover:bg-graphite">{{ __('Zapisz zgodę i przejdź dalej') }}</button>
        </form>

        <details class="mt-6 rounded-2xl bg-white p-5 text-sm ring-1 ring-zinc-200/70">
            <summary class="cursor-pointer font-semibold text-zinc-700">{{ __('Nie zgadzam się: usuń moje konto') }}</summary>
            @include('account.partials.delete-form', ['reason' => 'consent_withdrawn'])
        </details>

        <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
            @csrf
            <button type="submit" class="text-sm text-zinc-500 underline">{{ __('Wyloguj się') }}</button>
        </form>
    </div>
</section>
@endsection

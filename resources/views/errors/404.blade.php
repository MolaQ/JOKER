@extends('layouts.public')

@section('title', __('Nie ma takiej strony'))
@section('robots', 'noindex, follow')

@section('content')
<section class="relative isolate -mt-16 overflow-hidden bg-ink pt-16 text-white lg:-mt-20 lg:pt-20">
    <div class="net-pattern absolute inset-0 -z-10" aria-hidden="true"></div>
    <div class="container-site grid items-center gap-10 py-20 lg:grid-cols-2 lg:py-28">
        {{-- „404”, w którym zero to piłka, która wyleciała w aut --}}
        <div class="relative mx-auto w-full max-w-md select-none" aria-hidden="true">
            <svg viewBox="0 0 420 220" class="w-full">
                <defs>
                    <clipPath id="ball404"><circle cx="210" cy="105" r="78"/></clipPath>
                </defs>
                <text x="12" y="185" font-family="Oswald, Impact, sans-serif" font-size="200" font-weight="700" fill="currentColor" class="text-white">4</text>
                <text x="300" y="185" font-family="Oswald, Impact, sans-serif" font-size="200" font-weight="700" fill="currentColor" class="text-white">4</text>
                <g class="origin-center animate-[spin_9s_linear_infinite]" style="transform-box: fill-box">
                    <circle cx="210" cy="105" r="78" fill="#ffd600"/>
                    <g clip-path="url(#ball404)" fill="none" stroke="#12141a" stroke-width="7" stroke-linecap="round">
                        <path d="M210 27c-30 30-38 70-24 120"/>
                        <path d="M136 80c40-8 86 2 130 34"/>
                        <path d="M176 178c22-30 56-52 106-58"/>
                        <path d="M210 27c34 18 58 50 70 90"/>
                        <path d="M136 80c-6 40 4 74 40 98"/>
                    </g>
                    <circle cx="210" cy="105" r="78" fill="none" stroke="#12141a" stroke-width="7"/>
                </g>
                <path d="M30 208h360" stroke="#45a0e0" stroke-width="4" stroke-dasharray="14 10" stroke-linecap="round"/>
            </svg>
        </div>
        <div>
            <p class="eyebrow">{{ __('Błąd 404') }}</p>
            <h1 class="heading-xl mt-3">{{ __('Aut! Tej strony nie ma') }}</h1>
            <p class="mt-5 max-w-xl text-lg text-zinc-300">{{ __('Adres mógł się zmienić albo strona została usunięta. Spróbuj od jednej z sekcji poniżej.') }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('home') }}" class="btn-primary">{{ __('Strona główna') }}</a>
                <a href="{{ route('articles.index') }}" class="btn-dark">{{ __('Aktualności') }}</a>
                <a href="{{ route('schedule.index') }}" class="btn-dark">{{ __('Mecze') }}</a>
                <a href="{{ route('teams.index') }}" class="btn-dark">{{ __('Drużyny') }}</a>
                <a href="{{ route('contact.index') }}" class="btn-dark">{{ __('Kontakt') }}</a>
            </div>
        </div>
    </div>
</section>
@endsection

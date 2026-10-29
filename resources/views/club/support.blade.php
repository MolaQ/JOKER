@extends('layouts.public')

@section('title', __('Wspieraj klub'))
@section('description', __('Jak wesprzeć :club: pakiety sponsorskie, gadżety klubowe i 1,5% podatku.', ['club' => $site['club_name_genitive']]))

@section('content')
<section class="container-site pt-14 pb-10 lg:pt-20">
    <p class="eyebrow">{{ __('Razem budujemy klub') }}</p>
    <h1 class="heading-xl mt-4">{{ __('Wspieraj klub') }}</h1>
    <p class="mt-6 max-w-2xl text-lg text-zinc-600">{{ __($site['support_intro']) }}</p>

    <nav class="mt-8 flex flex-wrap gap-2" aria-label="{{ __('Sekcje') }}">
        @if(filled($site['support_krs']))<a href="#podatek" class="chip">💙 {{ __('1,5% podatku') }}</a>@endif
        @if($packages->isNotEmpty())<a href="#sponsorzy" class="chip">🤝 {{ __('Pakiety sponsorskie') }}</a>@endif
        @if($products->isNotEmpty())<a href="#gadzety" class="chip">🛍️ {{ __('Gadżety') }}</a>@endif
    </nav>
</section>

@if(filled($site['support_krs']) || filled($site['support_account']))
    <section id="podatek" class="container-site scroll-mt-28 pb-14">
        <div class="grid gap-6 lg:grid-cols-2">
            @if(filled($site['support_krs']))
                <div class="rounded-3xl bg-ink p-8 text-white">
                    <p class="eyebrow">{{ __('Nic nie kosztuje') }}</p>
                    <h2 class="mt-3 font-display text-3xl font-bold uppercase">{{ __('Przekaż 1,5% podatku') }}</h2>
                    <p class="mt-4 text-white/80">{{ __('W zeznaniu PIT wpisz numer KRS. Pieniądze trafią na szkolenie młodzieży :club.', ['club' => $site['club_name_genitive']]) }}</p>
                    <dl class="mt-6 space-y-3">
                        <div><dt class="text-xs text-white/60 uppercase">KRS</dt><dd class="font-display text-4xl font-bold tracking-wider tabular-nums">{{ $site['support_krs'] }}</dd></div>
                        @if(filled($site['support_tax_goal']))
                            <div><dt class="text-xs text-white/60 uppercase">{{ __('Cel szczegółowy') }}</dt><dd class="text-lg font-semibold">{{ $site['support_tax_goal'] }}</dd></div>
                        @endif
                    </dl>
                </div>
            @endif
            @if(filled($site['support_account']))
                <div class="card p-8">
                    <p class="eyebrow">{{ __('Darowizna') }}</p>
                    <h2 class="mt-3 font-display text-3xl font-bold uppercase">{{ __('Wpłać na konto klubu') }}</h2>
                    <p class="mt-4 font-mono text-xl tracking-wide">{{ $site['support_account'] }}</p>
                    <p class="mt-2 text-sm text-zinc-500">{{ $site['club_name'] }} · {{ __('tytuł: darowizna na cele statutowe') }}</p>
                </div>
            @endif
        </div>
    </section>
@endif

@if($packages->isNotEmpty())
    <section id="sponsorzy" class="scroll-mt-28 bg-white py-14">
        <div class="container-site">
            <p class="eyebrow">{{ __('Dla firm') }}</p>
            <h2 class="heading-lg mt-3">{{ __('Pakiety sponsorskie') }}</h2>
            <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($packages as $package)
                    <div @class(['card flex flex-col p-8', 'ring-2 ring-sky-club' => $package->is_highlighted])>
                        @if($package->is_highlighted)<span class="chip mb-3 self-start bg-sky-club text-white">{{ __('Polecany') }}</span>@endif
                        <h3 class="font-display text-2xl font-bold uppercase">{{ $package->name }}</h3>
                        @if($package->price)<p class="mt-1 text-lg font-semibold text-sky-club-dark">{{ $package->price }}</p>@endif
                        <ul class="mt-5 flex-1 space-y-2 text-sm text-zinc-700">
                            @foreach($package->benefitList() as $benefit)
                                <li class="flex gap-2"><span class="text-sky-club" aria-hidden="true">✓</span> {{ $benefit }}</li>
                            @endforeach
                        </ul>
                        <a href="{{ route('contact.index', ['temat' => __('Pakiet sponsorski').': '.$package->name]) }}" class="btn-dark mt-6 justify-center">{{ __('Zapytaj o pakiet') }}</a>
                    </div>
                @endforeach
            </div>
            <p class="mt-6 text-sm text-zinc-500">{{ __('Więcej o współpracy:') }} <a href="{{ route('pages.show', 'wspolpraca') }}" class="font-semibold text-sky-club-dark hover:underline">{{ __('Współpraca i sponsoring') }}</a></p>
        </div>
    </section>
@endif

@if($products->isNotEmpty())
    <section id="gadzety" class="container-site scroll-mt-28 py-14">
        <p class="eyebrow">{{ __('Dla kibiców') }}</p>
        <h2 class="heading-lg mt-3">{{ __('Gadżety klubowe') }}</h2>
        <p class="mt-4 max-w-2xl text-zinc-600">{{ __($site['support_shop_note']) }}</p>
        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($products as $product)
                <div @class(['card flex flex-col overflow-hidden', 'opacity-60' => ! $product->is_available])>
                    <div class="relative aspect-square bg-zinc-100">
                        @if($product->photoUrl())
                            <img src="{{ $product->photoUrl() }}" alt="{{ $product->name }}" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover">
                        @else
                            <img src="{{ $site->image('logo', asset('img/logo-256.webp')) }}" alt="" class="absolute inset-0 m-auto h-1/2 w-1/2 object-contain opacity-30">
                        @endif
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        <h3 class="font-semibold">{{ $product->name }}</h3>
                        @if($product->description)<p class="mt-1 text-sm text-zinc-600">{{ $product->description }}</p>@endif
                        @if($product->sizes)<p class="mt-1 text-xs text-zinc-500">{{ __('Rozmiary') }}: {{ $product->sizes }}</p>@endif
                        <div class="mt-auto flex items-center justify-between gap-3 pt-4">
                            <span class="text-lg font-bold">{{ $product->priceLabel() }}</span>
                            @if($product->is_available)
                                <a href="{{ $product->orderUrl() }}" class="rounded-full bg-sky-club px-4 py-2 text-sm font-semibold text-white hover:bg-sky-club-dark">{{ __('Zamów') }}</a>
                            @else
                                <span class="text-sm text-zinc-500">{{ __('Chwilowo brak') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
@endsection

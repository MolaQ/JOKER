<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Joker Piła - Klub Siatkówki')</title>
    <meta name="description" content="@yield('description', 'Joker Piła - klub siatkarski z Piły. Drużyny od młodzików po seniorów, terminarz, wyniki i aktualności.')">
    <meta name="theme-color" content="#12141a">
    <meta property="og:title" content="@yield('title', 'Joker Piła - Klub Siatkówki')">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ asset('img/JOKER-18.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/logo-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/logo-180.png') }}">
    <link rel="preconnect" href="https://images.unsplash.com" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="min-h-screen bg-zinc-50">
    <a href="#tresc" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[60] focus:rounded-full focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold">Przejdź do treści</a>

    @php
        $navigation = [
            ['label' => 'Aktualności', 'route' => 'articles.index', 'active' => 'articles.*'],
            ['label' => 'Drużyny', 'route' => 'teams.index', 'active' => ['teams.*', 'players.*', 'trainers.*']],
            ['label' => 'Terminarz', 'route' => 'schedule.index', 'active' => 'schedule.*'],
            ['label' => 'Tabele', 'route' => 'standings', 'active' => 'standings'],
            ['label' => 'Kontakt', 'route' => 'contact.index', 'active' => 'contact.*'],
        ];
    @endphp

    <header data-header class="group/header sticky top-0 z-50 border-b border-white/5 bg-ink/85 text-white backdrop-blur-xl transition-colors [&.is-scrolled]:bg-ink/95">
        <div class="container-site flex h-16 items-center justify-between gap-4 lg:h-20">
            <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="Joker Piła - strona główna">
                <img src="{{ asset('img/logo-256.webp') }}" alt="" width="48" height="48" class="h-10 w-10 lg:h-12 lg:w-12">
                <span class="leading-none">
                    <span class="block font-display text-lg font-bold tracking-wide uppercase lg:text-xl">Joker Piła</span>
                    <span class="block text-[10px] font-semibold tracking-[0.3em] text-sky-club uppercase">Klub siatkówki</span>
                </span>
            </a>

            <nav class="hidden items-center gap-1 lg:flex" aria-label="Menu główne">
                @foreach($navigation as $item)
                    <a href="{{ route($item['route']) }}" @class([
                        'rounded-full px-4 py-2 text-sm font-medium transition',
                        'bg-white/10 text-white' => request()->routeIs(...(array) $item['active']),
                        'text-zinc-300 hover:text-white' => ! request()->routeIs(...(array) $item['active']),
                    ])>{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                <details class="group/search relative">
                    <summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-full text-zinc-300 transition hover:bg-white/10 hover:text-white [&::-webkit-details-marker]:hidden" aria-label="Szukaj">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                    </summary>
                    <div class="fixed inset-x-4 top-20 sm:absolute sm:inset-x-auto sm:top-12 sm:right-0 sm:w-96">
                        @livewire('global-search')
                    </div>
                </details>

                @auth
                    @if(auth()->user()->canManageContent())
                        <a href="{{ route('admin.dashboard') }}" class="hidden rounded-full bg-sky-club px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-club-dark sm:inline-flex">Panel</a>
                    @endif
                    <a href="{{ route('profile.edit') }}" class="hidden h-10 w-10 items-center justify-center rounded-full bg-white/10 text-sm font-bold sm:flex" title="Mój profil">{{ mb_substr(auth()->user()->name, 0, 1) }}</a>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-full px-4 py-2 text-sm font-medium text-zinc-300 transition hover:text-white sm:inline-flex">Zaloguj</a>
                @endauth

                <button type="button" data-menu-toggle aria-expanded="false" aria-controls="menu-mobilne" class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 lg:hidden" aria-label="Menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h10"/></svg>
                </button>
            </div>
        </div>

        <div id="menu-mobilne" data-menu class="hidden h-[calc(100dvh-4rem)] overflow-y-auto border-t border-white/10 bg-ink lg:hidden">
            <nav class="container-site flex flex-col gap-1 py-6" aria-label="Menu mobilne">
                @foreach($navigation as $item)
                    <a href="{{ route($item['route']) }}" class="border-b border-white/5 py-4 font-display text-3xl font-semibold uppercase {{ request()->routeIs(...(array) $item['active']) ? 'text-sky-club' : 'text-white' }}">{{ $item['label'] }}</a>
                @endforeach
                <div class="mt-6 flex flex-wrap gap-3">
                    @auth
                        @if(auth()->user()->canManageContent())
                            <a href="{{ route('admin.dashboard') }}" class="btn-primary">Panel administracyjny</a>
                        @endif
                        <a href="{{ route('profile.edit') }}" class="btn-ghost">Mój profil</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-ghost">Wyloguj</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn-ghost">Zaloguj się</a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    @if(session('success') || session('error'))
        <div class="container-site mt-4">
            <div role="status" @class([
                'rounded-2xl px-5 py-4 text-sm font-medium',
                'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200' => session('success'),
                'bg-red-50 text-red-800 ring-1 ring-red-200' => session('error'),
            ])>{{ session('success') ?? session('error') }}</div>
        </div>
    @endif

    <main id="tresc">
        @yield('content')
    </main>

    <footer class="relative mt-24 overflow-hidden bg-ink text-zinc-400">
        <div class="net-pattern absolute inset-0" aria-hidden="true"></div>
        <div class="container-site relative py-16">
            <div class="grid gap-12 md:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('img/logo-256.webp') }}" alt="Herb Joker Piła" width="72" height="72" loading="lazy" class="h-18 w-18">
                        <div>
                            <p class="font-display text-3xl font-bold text-white uppercase">Joker Piła</p>
                            <p class="text-sm">Klub siatkówki. Pasja, charakter, drużyna.</p>
                        </div>
                    </div>
                    <a href="{{ route('contact.index') }}" class="btn-primary mt-8">Zapisz dziecko na trening</a>
                </div>

                <div>
                    <p class="eyebrow mb-4">Na skróty</p>
                    <ul class="space-y-2 text-sm">
                        @foreach($navigation as $item)
                            <li><a href="{{ route($item['route']) }}" class="transition hover:text-white">{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <p class="eyebrow mb-4">Kontakt</p>
                    <ul class="space-y-2 text-sm">
                        <li><a href="mailto:kontakt@jokerpila.pl" class="transition hover:text-white">kontakt@jokerpila.pl</a></li>
                        <li><a href="tel:+48123456789" class="transition hover:text-white">+48 123 456 789</a></li>
                        <li>ul. Sportowa 1, 64-920 Piła</li>
                    </ul>
                    <div class="mt-5 flex gap-3">
                        <a href="#" class="flex h-10 w-10 items-center justify-center rounded-full bg-white/5 transition hover:bg-sky-club hover:text-white" aria-label="Facebook">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="flex h-10 w-10 items-center justify-center rounded-full bg-white/5 transition hover:bg-sky-club hover:text-white" aria-label="Instagram">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="mt-14 flex flex-col gap-3 border-t border-white/10 pt-6 text-xs sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ date('Y') }} Joker Piła</p>
                <p>Zdjęcia tematyczne: <a href="{{ route('credits') }}" class="underline hover:text-white">Unsplash i ich autorzy</a></p>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
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
                <a href="{{ route('search') }}" data-search-open class="hidden h-10 items-center gap-3 rounded-full bg-white/10 pr-2 pl-4 text-sm text-zinc-300 transition hover:bg-white/15 hover:text-white sm:flex" aria-label="Szukaj na stronie">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                    <span class="lg:w-28">Szukaj…</span>
                    <kbd class="hidden rounded-md bg-white/10 px-1.5 py-0.5 font-sans text-[11px] lg:inline">/</kbd>
                </a>
                <a href="{{ route('search') }}" data-search-open class="flex h-11 w-11 items-center justify-center rounded-full text-zinc-300 transition hover:bg-white/10 hover:text-white sm:hidden" aria-label="Szukaj na stronie">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                </a>

                @auth
                    @if(auth()->user()->canManageContent())
                        <a href="{{ route('admin.dashboard') }}" class="hidden rounded-full bg-sky-club px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-club-dark sm:inline-flex">Panel</a>
                    @endif
                    <a href="{{ route('profile.edit') }}" class="hidden h-10 w-10 items-center justify-center rounded-full bg-white/10 text-sm font-bold sm:flex" title="Mój profil">{{ mb_substr(auth()->user()->name, 0, 1) }}</a>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-full px-4 py-2 text-sm font-medium text-zinc-300 transition hover:text-white sm:inline-flex">Zaloguj</a>
                @endauth

                <button type="button" data-menu-toggle aria-expanded="false" aria-controls="menu-mobilne" class="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 lg:hidden" aria-label="Menu">
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

    <footer class="relative mt-24 overflow-hidden bg-ink pb-20 text-zinc-400 lg:pb-0">
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

    {{-- Wyszukiwarka (okno otwierane ikoną lupy, klawiszem "/" lub Ctrl+K) --}}
    <dialog data-search-dialog data-index-url="{{ route('search.data') }}" data-page-url="{{ route('search') }}" aria-label="Szukaj na stronie"
        class="m-0 h-dvh max-h-none w-full max-w-none bg-white p-0 backdrop:bg-ink/70 backdrop:backdrop-blur-sm sm:mx-auto sm:mt-[10vh] sm:h-auto sm:max-h-[75vh] sm:max-w-2xl sm:rounded-3xl sm:shadow-2xl">
        <div class="flex h-full max-h-[inherit] flex-col">
            <div class="flex items-center gap-3 border-b border-zinc-200 px-4 pt-[max(0.75rem,env(safe-area-inset-top))] pb-3">
                <svg class="h-5 w-5 shrink-0 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                <input data-search-input type="search" inputmode="search" enterkeyhint="search" autocomplete="off" placeholder="Szukaj zawodnika, drużyny, meczu, wpisu…" aria-label="Szukana fraza"
                    class="h-12 min-w-0 flex-1 bg-transparent text-base text-zinc-900 outline-none placeholder:text-zinc-400 [&::-webkit-search-cancel-button]:hidden">
                <button type="button" data-search-close class="rounded-full px-3 py-2 text-sm font-semibold text-zinc-500 hover:bg-zinc-100">Zamknij</button>
            </div>
            <div class="flex-1 overflow-y-auto overscroll-contain px-2 pb-4">
                <ul data-search-results role="listbox"></ul>
                <p data-search-empty class="hidden px-4 py-10 text-center text-zinc-500">Nic nie znaleziono. Spróbuj innego słowa.</p>
                <p class="px-4 pt-6 text-xs text-zinc-400">Szukasz w całej stronie: drużyny, zawodnicy, sztab, aktualności, mecze i dokumenty. Polskie znaki są opcjonalne.</p>
            </div>
            <a data-search-all href="{{ route('search') }}" class="hidden border-t border-zinc-200 px-4 py-4 pb-[max(1rem,env(safe-area-inset-bottom))] text-center text-sm font-semibold text-sky-club-dark">Pokaż wszystkie wyniki →</a>
        </div>
    </dialog>

    {{-- Dolne menu na telefonie: najważniejsze miejsca pod kciukiem --}}
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-ink/95 pb-[env(safe-area-inset-bottom)] text-zinc-400 backdrop-blur-xl lg:hidden" aria-label="Menu dolne">
        <div class="grid grid-cols-5">
            @foreach([
                ['Start', 'home', 'home', 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z'],
                ['Drużyny', 'teams.index', ['teams.*', 'players.*', 'trainers.*'], 'M17 20v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2m18 0v-2a4 4 0 0 0-3-3.87M14 4.13a4 4 0 0 1 0 7.75M10 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0'],
                ['Mecze', 'schedule.index', ['schedule.*', 'standings'], 'M8 3v4m8-4v4M3 10h18M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z'],
                ['Aktualności', 'articles.index', 'articles.*', 'M4 5h12v14H6a2 2 0 0 1-2-2zm12 4h4v8a2 2 0 0 1-2 2h-2M8 9h4m-4 4h4'],
            ] as [$label, $route, $active, $icon])
                <a href="{{ route($route) }}" @class(['flex flex-col items-center gap-1 py-2.5 text-[11px] font-medium', 'text-sky-club' => request()->routeIs(...(array) $active)])>
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                    {{ $label }}
                </a>
            @endforeach
            <a href="{{ route('search') }}" data-search-open class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-medium {{ request()->routeIs('search') ? 'text-sky-club' : '' }}">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                Szukaj
            </a>
        </div>
    </nav>

    @livewireScripts
</body>
</html>

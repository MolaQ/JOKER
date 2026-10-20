<!DOCTYPE html>
<html lang="pl">
<head>
    @php
        $pageTitle = trim($__env->yieldContent('title'));
        $fullTitle = $pageTitle !== '' ? $pageTitle.' - '.$site['club_name'] : $site['site_title'];
        $logoUrl = $site->image('logo', asset('img/logo-256.webp'));
        $accentColor = $site->color('color_accent');
        $navItems = $site->links('nav_items');
        $topbarLeft = $site->links('topbar_links_left');
        $topbarRight = $site->links('topbar_links_right');
        $socials = $site->socials();
        $topbarSearch = $site['topbar_enabled'] && $site['topbar_show_search'];
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="@yield('description', $site['site_description'])">
    <meta name="theme-color" content="{{ $site->color('color_dark') }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ $logoUrl }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $site['logo'] ? $logoUrl : asset('img/logo-32.png') }}">
    <link rel="apple-touch-icon" href="{{ $site['logo'] ? $logoUrl : asset('img/logo-180.png') }}">
    <link rel="preconnect" href="https://images.unsplash.com" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Kolory z panelu: Wygląd i ustawienia → Kolory --}}
    <style>
        :root {
            --color-sky-club: {{ $accentColor }};
            --color-sky-club-dark: color-mix(in oklab, {{ $accentColor }} 78%, black);
            --color-sky-club-light: color-mix(in oklab, {{ $accentColor }} 65%, white);
            --color-ink: {{ $site->color('color_dark') }};
            --color-graphite: {{ $site->color('color_dark_alt') }};
            --color-navy-800: {{ $site->color('color_dark_alt') }};
            --color-page: {{ $site->color('color_page') }};
            --color-topbar-bg: {{ $site->color('color_topbar_bg') }};
            --color-topbar-text: {{ $site->color('color_topbar_text') }};
            --color-nav-button-bg: {{ $site->color('color_nav_button_bg') }};
            --color-nav-button-text: {{ $site->color('color_nav_button_text') }};
            --color-countdown: {{ $site->color('color_countdown') }};
            --color-club-navy: {{ $site->color('color_club_navy') }};
        }
        body { background-color: var(--color-page); }
    </style>
    @livewireStyles
    @stack('head')
</head>
<body class="min-h-screen">
    <a href="#tresc" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[60] focus:rounded-full focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold">Przejdź do treści</a>

    {{-- Górny pasek (Wygląd i ustawienia → Górny pasek) --}}
    @if($site['topbar_enabled'])
        <div class="relative z-[51] bg-[var(--color-topbar-bg)] text-[var(--color-topbar-text)]">
            <div class="container-site flex h-10 items-center gap-6 text-[13px] font-semibold">
                <nav class="-mx-4 flex min-w-0 flex-1 items-center gap-5 overflow-x-auto px-4 whitespace-nowrap [scrollbar-width:none] sm:mx-0 sm:px-0 [&::-webkit-scrollbar]:hidden" aria-label="Na skróty">
                    @foreach($topbarLeft as $link)
                        <a href="{{ $site->href($link['url']) }}" @if($site->isExternal($link['url'])) target="_blank" rel="noopener" @endif class="transition hover:opacity-70">{{ $link['label'] }}</a>
                    @endforeach
                    @foreach($topbarRight as $link)
                        <a href="{{ $site->href($link['url']) }}" @if($site->isExternal($link['url'])) target="_blank" rel="noopener" @endif class="transition hover:opacity-70 lg:hidden">{{ $link['label'] }}</a>
                    @endforeach
                </nav>

                <div class="hidden items-center gap-5 lg:flex">
                    @foreach($topbarRight as $link)
                        <a href="{{ $site->href($link['url']) }}" @if($site->isExternal($link['url'])) target="_blank" rel="noopener" @endif class="whitespace-nowrap transition hover:opacity-70">{{ $link['label'] }}</a>
                    @endforeach

                    @if($topbarSearch)
                        <a href="{{ route('search') }}" data-search-open class="flex h-7 w-52 items-center gap-2 rounded-full bg-white/60 px-3 font-normal opacity-90 ring-1 ring-black/10 transition hover:bg-white/80" aria-label="Szukaj na stronie">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                            <span class="flex-1">Szukaj…</span>
                            <kbd class="rounded bg-black/10 px-1.5 font-sans text-[11px]">/</kbd>
                        </a>
                    @endif

                    @if($site['topbar_show_social'] && $socials)
                        <div class="flex items-center gap-3">
                            @foreach($socials as $network => $socialUrl)
                                <a href="{{ $socialUrl }}" target="_blank" rel="noopener" class="transition hover:opacity-70" aria-label="{{ ucfirst($network) }}"><x-social-icon :network="$network" class="h-4 w-4" /></a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <header data-header class="group/header sticky top-0 z-50 border-b border-white/5 bg-ink/85 text-white backdrop-blur-xl transition-colors [&.is-scrolled]:bg-ink/95">
        <div class="container-site flex h-16 items-center justify-between gap-4 lg:h-20">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3" aria-label="{{ $site['club_name'] }} - strona główna">
                <img src="{{ $logoUrl }}" alt="" width="48" height="48" class="h-10 w-10 object-contain lg:h-12 lg:w-12">
                <span class="leading-none">
                    <span class="block font-display text-lg font-bold tracking-wide uppercase lg:text-xl">{{ $site['club_name'] }}</span>
                    <span class="block text-[10px] font-semibold tracking-[0.3em] text-sky-club uppercase">{{ $site['club_tagline'] }}</span>
                </span>
            </a>

            <nav class="hidden items-center gap-0.5 lg:flex xl:gap-1" aria-label="Menu główne">
                @foreach($navItems as $link)
                    <a href="{{ $site->href($link['url']) }}" @if($site->isExternal($link['url'])) target="_blank" rel="noopener" @endif @class([
                        'rounded-full px-3 py-2 font-display text-[15px] font-medium tracking-wide uppercase transition xl:px-4',
                        'bg-white/10 text-white' => $site->isCurrent($link['url']),
                        'text-zinc-300 hover:text-white' => ! $site->isCurrent($link['url']),
                    ])>{{ $link['label'] }}</a>
                @endforeach
                @if($site['nav_button_enabled'] && filled($site['nav_button_label']))
                    <a href="{{ $site->href((string) $site['nav_button_url']) }}" class="ml-2 rounded-md bg-[var(--color-nav-button-bg)] px-4 py-2 font-display text-[15px] font-semibold tracking-wide text-[var(--color-nav-button-text)] uppercase transition hover:brightness-95">{{ $site['nav_button_label'] }}</a>
                @endif
            </nav>

            <div class="flex items-center gap-2">
                <a href="{{ route('search') }}" data-search-open @class(['hidden h-10 items-center gap-3 rounded-full bg-white/10 pr-2 pl-4 text-sm text-zinc-300 transition hover:bg-white/15 hover:text-white sm:flex', 'lg:hidden' => $topbarSearch]) aria-label="Szukaj na stronie">
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
                    <a href="{{ route('login') }}" class="hidden rounded-full px-3 py-2 text-sm font-medium text-zinc-400 transition hover:text-white sm:inline-flex">Zaloguj</a>
                @endauth

                <button type="button" data-menu-toggle aria-expanded="false" aria-controls="menu-mobilne" class="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 lg:hidden" aria-label="Menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h10"/></svg>
                </button>
            </div>
        </div>

        <div id="menu-mobilne" data-menu class="hidden h-[calc(100dvh-4rem)] overflow-y-auto border-t border-white/10 bg-ink lg:hidden">
            <nav class="container-site flex flex-col gap-1 py-6" aria-label="Menu mobilne">
                @foreach($navItems as $link)
                    <a href="{{ $site->href($link['url']) }}" class="border-b border-white/5 py-4 font-display text-3xl font-semibold uppercase {{ $site->isCurrent($link['url']) ? 'text-sky-club' : 'text-white' }}">{{ $link['label'] }}</a>
                @endforeach
                @if($site['nav_button_enabled'] && filled($site['nav_button_label']))
                    <a href="{{ $site->href((string) $site['nav_button_url']) }}" class="mt-4 rounded-xl bg-[var(--color-nav-button-bg)] py-4 text-center font-display text-2xl font-semibold text-[var(--color-nav-button-text)] uppercase">{{ $site['nav_button_label'] }}</a>
                @endif
                @if($topbarLeft || $topbarRight)
                    <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-3 text-sm text-zinc-300">
                        @foreach([...$topbarLeft, ...$topbarRight] as $link)
                            <a href="{{ $site->href($link['url']) }}" class="hover:text-white">{{ $link['label'] }}</a>
                        @endforeach
                    </div>
                @endif
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
                        <img src="{{ $logoUrl }}" alt="Herb {{ $site['club_name'] }}" width="72" height="72" loading="lazy" class="h-18 w-18 object-contain">
                        <div>
                            <p class="font-display text-3xl font-bold text-white uppercase">{{ $site['club_name'] }}</p>
                            <p class="text-sm">{{ $site['footer_text'] }}</p>
                        </div>
                    </div>
                    @if(filled($site['footer_button_label']) && filled($site['footer_button_url']))
                        <a href="{{ $site->href((string) $site['footer_button_url']) }}" class="btn-primary mt-8">{{ $site['footer_button_label'] }}</a>
                    @endif
                </div>

                <div>
                    <p class="eyebrow mb-4">Na skróty</p>
                    <ul class="space-y-2 text-sm">
                        @foreach([...$navItems, ...$topbarLeft] as $link)
                            <li><a href="{{ $site->href($link['url']) }}" class="transition hover:text-white">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <p class="eyebrow mb-4">Kontakt</p>
                    <ul class="space-y-2 text-sm">
                        @if(filled($site['contact_email']))
                            <li><a href="mailto:{{ $site['contact_email'] }}" class="transition hover:text-white">{{ $site['contact_email'] }}</a></li>
                        @endif
                        @if(filled($site['contact_phone']))
                            <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact_phone']) }}" class="transition hover:text-white">{{ $site['contact_phone'] }}</a></li>
                        @endif
                        @if(filled($site['contact_address']))
                            <li>{{ $site['contact_address'] }}</li>
                        @endif
                    </ul>
                    @if($socials)
                        <div class="mt-5 flex gap-3">
                            @foreach($socials as $network => $socialUrl)
                                <a href="{{ $socialUrl }}" target="_blank" rel="noopener" class="flex h-10 w-10 items-center justify-center rounded-full bg-white/5 transition hover:bg-sky-club hover:text-white" aria-label="{{ ucfirst($network) }}">
                                    <x-social-icon :network="$network" class="h-4 w-4" />
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-14 flex flex-col gap-3 border-t border-white/10 pt-6 text-xs sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ date('Y') }} {{ $site['club_name'] }}</p>
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

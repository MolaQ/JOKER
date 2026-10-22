<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    @php
        $pageTitle = trim($__env->yieldContent('title'));
        $fullTitle = $pageTitle !== '' ? $pageTitle.' - '.$site['club_name'] : __($site['site_title']);
        $logoUrl = $site->image('logo', asset('img/logo-256.webp'));
        $accentColor = $site->color('color_accent');
        $navItems = $site->links('nav_items');
        $topbarLeft = $site->links('topbar_links_left');
        $topbarRight = $site->links('topbar_links_right');
        $socials = $site->socials();
        $topbarSearch = $site['topbar_enabled'] && $site['topbar_show_search'];
        // Zalogowany nie potrzebuje przycisku prowadzącego do rejestracji.
        $navButtonUrl = (string) $site['nav_button_url'];
        $showNavButton = $site['nav_button_enabled'] && filled($site['nav_button_label'])
            && ! (auth()->check() && rtrim($navButtonUrl, '/') === '/rejestracja');
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    @php
        $metaDescription = trim($__env->yieldContent('description')) ?: __($site['site_description']);
        $canonicalUrl = trim($__env->yieldContent('canonical')) ?: \App\Support\Seo::canonical(request());
        $ogImage = trim($__env->yieldContent('og_image')) ?: $logoUrl;
    @endphp
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="@yield('robots', 'index, follow, max-image-preview:large')">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta name="theme-color" content="{{ $site->color('color_dark') }}">
    <meta property="og:site_name" content="{{ $site['club_name'] }}">
    <meta property="og:locale" content="{{ app()->getLocale() === 'en' ? 'en_GB' : 'pl_PL' }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="{{ $ogImage === $logoUrl ? 'summary' : 'summary_large_image' }}">
    <link rel="sitemap" type="application/xml" href="{{ route('sitemap') }}">
    {!! \App\Support\Seo::jsonLd(\App\Support\Seo::organization($site, $logoUrl)) !!}
    @stack('structured-data')
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $site['logo'] ? $logoUrl : asset('img/logo-32.png') }}">
    <link rel="apple-touch-icon" href="{{ $site['logo'] ? $logoUrl : asset('img/logo-180.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ $site['club_name'] }}">
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
    <a href="#tresc" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[60] focus:rounded-full focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold">{{ __('Przejdź do treści') }}</a>

    {{-- Górny pasek (Wygląd i ustawienia → Górny pasek) --}}
    @if($site['topbar_enabled'])
        <div class="relative z-[51] bg-[var(--color-topbar-bg)] text-[var(--color-topbar-text)]">
            <div class="container-site flex h-10 items-center gap-6 text-[13px] font-semibold">
                <nav class="-mx-4 flex min-w-0 flex-1 items-center gap-5 overflow-x-auto px-4 whitespace-nowrap [scrollbar-width:none] sm:mx-0 sm:px-0 [&::-webkit-scrollbar]:hidden" aria-label="{{ __('Na skróty') }}">
                    @foreach($topbarLeft as $link)
                        <a href="{{ $site->href($link['url']) }}" @if($site->isExternal($link['url'])) target="_blank" rel="noopener" @endif class="transition hover:opacity-70">{{ __($link['label']) }}</a>
                    @endforeach
                    @foreach($topbarRight as $link)
                        <a href="{{ $site->href($link['url']) }}" @if($site->isExternal($link['url'])) target="_blank" rel="noopener" @endif class="transition hover:opacity-70 lg:hidden">{{ __($link['label']) }}</a>
                    @endforeach
                </nav>

                <div class="hidden items-center gap-5 lg:flex">
                    @foreach($topbarRight as $link)
                        <a href="{{ $site->href($link['url']) }}" @if($site->isExternal($link['url'])) target="_blank" rel="noopener" @endif class="whitespace-nowrap transition hover:opacity-70">{{ __($link['label']) }}</a>
                    @endforeach

                    @if($topbarSearch)
                        <a href="{{ route('search') }}" data-search-open class="flex h-7 w-52 items-center gap-2 rounded-full bg-white/60 px-3 font-normal opacity-90 ring-1 ring-black/10 transition hover:bg-white/80" aria-label="{{ __('Szukaj na stronie') }}">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                            <span class="flex-1">{{ __('Szukaj…') }}</span>
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
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3 lg:shrink-0" aria-label="{{ $site['club_name'] }} - {{ __('strona główna') }}">
                <img src="{{ $logoUrl }}" alt="" width="48" height="48" class="h-10 w-10 object-contain lg:h-12 lg:w-12">
                <span class="min-w-0 leading-none">
                    <span class="block font-display text-lg font-bold tracking-wide uppercase lg:text-xl">{{ $site['club_name'] }}</span>
                    <span class="block truncate text-[10px] font-semibold tracking-[0.2em] text-sky-club uppercase sm:tracking-[0.3em]">{{ __($site['club_tagline']) }}</span>
                </span>
            </a>

            <nav class="hidden items-center gap-0.5 lg:flex xl:gap-1" aria-label="{{ __('Menu główne') }}">
                @foreach($navItems as $link)
                    <a href="{{ $site->href($link['url']) }}" @if($site->isExternal($link['url'])) target="_blank" rel="noopener" @endif @class([
                        'rounded-full px-3 py-2 font-display text-[15px] font-medium tracking-wide uppercase transition xl:px-4',
                        'bg-white/10 text-white' => $site->isCurrent($link['url']),
                        'text-zinc-300 hover:text-white' => ! $site->isCurrent($link['url']),
                    ])>{{ __($link['label']) }}</a>
                @endforeach
                @if($showNavButton)
                    <a href="{{ $site->href($navButtonUrl) }}" class="ml-2 rounded-md bg-[var(--color-nav-button-bg)] px-4 py-2 font-display text-[15px] font-semibold tracking-wide text-[var(--color-nav-button-text)] uppercase transition hover:brightness-95">{{ __($site['nav_button_label']) }}</a>
                @endif
            </nav>

            <div class="flex items-center gap-2">
                <x-language-switch class="hidden md:flex" />
                <a href="{{ route('search') }}" data-search-open @class(['hidden h-10 items-center gap-3 rounded-full bg-white/10 pr-2 pl-4 text-sm text-zinc-300 transition hover:bg-white/15 hover:text-white sm:flex', 'lg:hidden' => $topbarSearch]) aria-label="{{ __('Szukaj na stronie') }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                    <span class="lg:w-28">{{ __('Szukaj…') }}</span>
                    <kbd class="hidden rounded-md bg-white/10 px-1.5 py-0.5 font-sans text-[11px] lg:inline">/</kbd>
                </a>
                <a href="{{ route('search') }}" data-search-open class="flex h-11 w-11 items-center justify-center rounded-full text-zinc-300 transition hover:bg-white/10 hover:text-white sm:hidden" aria-label="{{ __('Szukaj na stronie') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                </a>

                @if($site['push_enabled'])
                    <button type="button" data-push-toggle hidden aria-pressed="false" data-label-on="{{ __('Powiadomienia włączone (kliknij, aby wyłączyć)') }}" data-label-off="{{ __('Włącz powiadomienia o wynikach i aktualnościach') }}" title="{{ __('Włącz powiadomienia o wynikach i aktualnościach') }}" class="hidden h-11 w-11 items-center sm:flex justify-center rounded-full text-zinc-300 transition hover:bg-white/10 hover:text-white aria-pressed:text-sky-club">
                        <svg data-push-off class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                        <svg data-push-on class="hidden h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M5.85 3.5a.75.75 0 0 0-1.117-1 9.719 9.719 0 0 0-2.348 4.876.75.75 0 0 0 1.479.248A8.219 8.219 0 0 1 5.85 3.5ZM19.267 2.5a.75.75 0 1 0-1.118 1 8.22 8.22 0 0 1 1.987 4.124.75.75 0 0 0 1.48-.248A9.72 9.72 0 0 0 19.266 2.5Z"/><path fill-rule="evenodd" d="M12 2.25A6.75 6.75 0 0 0 5.25 9v.75a8.217 8.217 0 0 1-2.119 5.52.75.75 0 0 0 .298 1.206c1.544.57 3.16.99 4.831 1.243a3.75 3.75 0 1 0 7.48 0 24.583 24.583 0 0 0 4.83-1.244.75.75 0 0 0 .298-1.205 8.217 8.217 0 0 1-2.118-5.52V9A6.75 6.75 0 0 0 12 2.25ZM9.75 18c0-.034 0-.067.002-.1a25.05 25.05 0 0 0 4.496 0l.002.1a2.25 2.25 0 1 1-4.5 0Z" clip-rule="evenodd"/></svg>
                        <span class="sr-only">{{ __('Powiadomienia') }}</span>
                    </button>
                @endif

                @auth
                    @if(auth()->user()->canManageContent())
                        <a href="{{ route('admin.dashboard') }}" class="hidden rounded-full bg-sky-club px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-club-dark sm:inline-flex">{{ __('Panel') }}</a>
                    @endif
                    <a href="{{ route('profile.edit') }}" class="hidden h-10 w-10 items-center justify-center rounded-full bg-white/10 text-sm font-bold sm:flex" title="{{ __('Mój profil') }}">{{ mb_substr(auth()->user()->name, 0, 1) }}</a>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-full px-3 py-2 text-sm font-medium text-zinc-400 transition hover:text-white sm:inline-flex">{{ __('Zaloguj') }}</a>
                @endauth

                <button type="button" data-menu-toggle aria-expanded="false" aria-controls="menu-mobilne" class="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 lg:hidden" aria-label="{{ __('Menu') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h10"/></svg>
                </button>
            </div>
        </div>

        <div id="menu-mobilne" data-menu class="hidden h-[calc(100dvh-4rem)] overflow-y-auto border-t border-white/10 bg-ink lg:hidden">
            <nav class="container-site flex flex-col gap-1 py-6" aria-label="{{ __('Menu mobilne') }}">
                @foreach($navItems as $link)
                    <a href="{{ $site->href($link['url']) }}" class="border-b border-white/5 py-4 font-display text-3xl font-semibold uppercase {{ $site->isCurrent($link['url']) ? 'text-sky-club' : 'text-white' }}">{{ __($link['label']) }}</a>
                @endforeach
                @if($showNavButton)
                    <a href="{{ $site->href($navButtonUrl) }}" class="mt-4 rounded-xl bg-[var(--color-nav-button-bg)] py-4 text-center font-display text-2xl font-semibold text-[var(--color-nav-button-text)] uppercase">{{ __($site['nav_button_label']) }}</a>
                @endif
                @if($topbarLeft || $topbarRight)
                    <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-3 text-sm text-zinc-300">
                        @foreach([...$topbarLeft, ...$topbarRight] as $link)
                            <a href="{{ $site->href($link['url']) }}" class="hover:text-white">{{ __($link['label']) }}</a>
                        @endforeach
                    </div>
                @endif
                <div class="mt-6 flex items-center gap-3 text-sm text-zinc-300">
                    <span>{{ __('Język strony') }}:</span>
                    <x-language-switch />
                </div>
                @if($site['push_enabled'])
                    <button type="button" data-push-toggle hidden aria-pressed="false" data-label-on="{{ __('Powiadomienia włączone (kliknij, aby wyłączyć)') }}" data-label-off="{{ __('Włącz powiadomienia o wynikach i aktualnościach') }}" class="mt-6 inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2.5 text-sm font-semibold text-white">
                        <span data-push-off>🔔 {{ __('Włącz powiadomienia') }}</span>
                        <span data-push-on class="hidden">🔕 {{ __('Wyłącz powiadomienia') }}</span>
                    </button>
                @endif
                <div class="mt-6 flex flex-wrap gap-3">
                    @auth
                        @if(auth()->user()->canManageContent())
                            <a href="{{ route('admin.dashboard') }}" class="btn-primary">{{ __('Panel administracyjny') }}</a>
                        @endif
                        <a href="{{ route('profile.edit') }}" class="btn-ghost">{{ __('Mój profil') }}</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-ghost">{{ __('Wyloguj') }}</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn-ghost">{{ __('Zaloguj się') }}</a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    <main id="tresc">
        @yield('content')
    </main>

    <footer class="relative mt-24 overflow-hidden bg-ink pb-20 text-zinc-400 lg:pb-0">
        <div class="net-pattern absolute inset-0" aria-hidden="true"></div>
        <div class="container-site relative py-16">
            <div class="grid gap-12 md:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <div class="flex items-center gap-4">
                        <img src="{{ $logoUrl }}" alt="{{ __('Herb') }} {{ $site['club_name'] }}" width="72" height="72" loading="lazy" class="h-18 w-18 object-contain">
                        <div>
                            <p class="font-display text-3xl font-bold text-white uppercase">{{ $site['club_name'] }}</p>
                            <p class="text-sm">{{ __($site['footer_text']) }}</p>
                        </div>
                    </div>
                    @if(filled($site['footer_button_label']) && filled($site['footer_button_url']))
                        <a href="{{ $site->href((string) $site['footer_button_url']) }}" class="btn-primary mt-8">{{ __($site['footer_button_label']) }}</a>
                    @endif
                </div>

                <div>
                    <p class="eyebrow mb-4">{{ __(filled($site['footer_links_title']) ? $site['footer_links_title'] : 'Na skróty') }}</p>
                    <ul class="space-y-2 text-sm">
                        @foreach($site->links('footer_links') ?: [...$navItems, ...$topbarLeft] as $link)
                            <li><a href="{{ $site->href($link['url']) }}" class="transition hover:text-white">{{ __($link['label']) }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <p class="eyebrow mb-4">{{ __('Kontakt') }}</p>
                    <ul class="space-y-2 text-sm">
                        @if(filled($site['contact_email']))
                            <li><a href="mailto:{{ $site['contact_email'] }}" class="transition hover:text-white">{{ $site['contact_email'] }}</a></li>
                        @endif
                        @if(filled($site['contact_phone']))
                            <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact_phone']) }}" class="transition hover:text-white">{{ $site['contact_phone'] }}</a> <span class="text-zinc-500">· {{ __('klub') }}</span></li>
                        @endif
                        @if(filled($site['contact_school_phone']))
                            <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact_school_phone']) }}" class="transition hover:text-white">{{ $site['contact_school_phone'] }}</a> <span class="text-zinc-500">· {{ __('szkoła') }}</span></li>
                        @endif
                        @if(filled($site['contact_address']))
                            <li>{{ $site['contact_address'] }}</li>
                        @endif
                        @if(filled($site['contact_school_name']))
                            <li>@if(filled($site['contact_school_url']))<a href="{{ $site['contact_school_url'] }}" target="_blank" rel="noopener" class="transition hover:text-white">{{ $site['contact_school_name'] }} ↗</a>@else{{ $site['contact_school_name'] }}@endif</li>
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
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-4">
                    <p>&copy; {{ date('Y') }} {{ $site['club_name'] }}@if(filled($site['footer_note']) && $site['footer_note'] !== $site['club_name']) · {{ $site['footer_note'] }}@endif</p>
                    <livewire:visit-counter />
                </div>
                <p>{{ __('Zdjęcia tematyczne:') }} <a href="{{ route('credits') }}" class="underline hover:text-white">{{ __('Unsplash i ich autorzy') }}</a></p>
            </div>
        </div>
    </footer>

    {{-- Wyszukiwarka (okno otwierane ikoną lupy, klawiszem "/" lub Ctrl+K) --}}
    <dialog data-search-dialog data-index-url="{{ route('search.data', app()->getLocale() === 'en' ? ['lang' => 'en'] : []) }}" data-page-url="{{ route('search') }}" aria-label="{{ __('Szukaj na stronie') }}"
        class="m-0 h-dvh max-h-none w-full max-w-none bg-white p-0 backdrop:bg-ink/70 backdrop:backdrop-blur-sm sm:mx-auto sm:mt-[10vh] sm:h-auto sm:max-h-[75vh] sm:max-w-2xl sm:rounded-3xl sm:shadow-2xl">
        <div class="flex h-full max-h-[inherit] flex-col">
            <div class="flex items-center gap-3 border-b border-zinc-200 px-4 pt-[max(0.75rem,env(safe-area-inset-top))] pb-3">
                <svg class="h-5 w-5 shrink-0 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                <input data-search-input type="search" inputmode="search" enterkeyhint="search" autocomplete="off" placeholder="{{ __('Szukaj zawodnika, drużyny, meczu, wpisu…') }}" aria-label="{{ __('Szukana fraza') }}"
                    class="h-12 min-w-0 flex-1 bg-transparent text-base text-zinc-900 outline-none placeholder:text-zinc-400 [&::-webkit-search-cancel-button]:hidden">
                <button type="button" data-search-close class="rounded-full px-3 py-2 text-sm font-semibold text-zinc-500 hover:bg-zinc-100">{{ __('Zamknij') }}</button>
            </div>
            <div class="flex-1 overflow-y-auto overscroll-contain px-2 pb-4">
                <ul data-search-results role="listbox"></ul>
                <p data-search-empty class="hidden px-4 py-10 text-center text-zinc-500">{{ __('Nic nie znaleziono. Spróbuj innego słowa.') }}</p>
                <p class="px-4 pt-6 text-xs text-zinc-400">{{ __('Szukasz w całej stronie: drużyny, zawodnicy, sztab, aktualności, mecze i dokumenty. Polskie znaki są opcjonalne.') }}</p>
            </div>
            <a data-search-all href="{{ route('search') }}" class="hidden border-t border-zinc-200 px-4 py-4 pb-[max(1rem,env(safe-area-inset-bottom))] text-center text-sm font-semibold text-sky-club-dark">{{ __('Pokaż wszystkie wyniki') }} →</a>
        </div>
    </dialog>

    {{-- Dolne menu na telefonie: najważniejsze miejsca pod kciukiem --}}
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-ink/95 pb-[env(safe-area-inset-bottom)] text-zinc-400 backdrop-blur-xl lg:hidden" aria-label="{{ __('Menu dolne') }}">
        <div class="grid grid-cols-5">
            @foreach([
                ['Start', 'home', 'home', 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z'],
                ['Drużyny', 'teams.index', ['teams.*', 'players.*', 'trainers.*'], 'M17 20v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2m18 0v-2a4 4 0 0 0-3-3.87M14 4.13a4 4 0 0 1 0 7.75M10 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0'],
                ['Mecze', 'schedule.index', ['schedule.*', 'standings'], 'M8 3v4m8-4v4M3 10h18M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z'],
                ['Aktualności', 'articles.index', 'articles.*', 'M4 5h12v14H6a2 2 0 0 1-2-2zm12 4h4v8a2 2 0 0 1-2 2h-2M8 9h4m-4 4h4'],
            ] as [$label, $route, $active, $icon])
                <a href="{{ route($route) }}" @class(['flex flex-col items-center gap-1 py-2.5 text-[11px] font-medium', 'text-sky-club' => request()->routeIs(...(array) $active)])>
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                    {{ __($label) }}
                </a>
            @endforeach
            <a href="{{ route('search') }}" data-search-open class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-medium {{ request()->routeIs('search') ? 'text-sky-club' : '' }}">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                {{ __('Szukaj') }}
            </a>
        </div>
    </nav>

    @if($site['push_enabled'])
        <div hidden data-push-config data-sw="{{ asset('sw.js') }}" data-scope="{{ rtrim(url('/'), '/') }}/" data-key-url="{{ route('push.key') }}" data-subscribe-url="{{ route('push.subscribe') }}"></div>
        @if($site['push_prompt'])
            <div data-push-prompt class="fixed inset-x-3 bottom-3 z-50 hidden rounded-2xl bg-ink p-4 text-white shadow-2xl ring-1 ring-white/10 sm:inset-x-auto sm:right-5 sm:bottom-5 sm:max-w-sm" role="dialog" aria-label="{{ __('Powiadomienia') }}">
                <div class="flex gap-3">
                    <img src="{{ $logoUrl }}" alt="" class="h-10 w-10 shrink-0 object-contain">
                    <p class="text-sm leading-snug">{{ __($site['push_prompt_text']) }}</p>
                </div>
                <div class="mt-3 flex justify-end gap-2">
                    <button type="button" data-push-dismiss class="rounded-full px-4 py-2 text-sm font-medium text-zinc-300 hover:text-white">{{ __('Nie teraz') }}</button>
                    <button type="button" data-push-accept class="rounded-full bg-sky-club px-4 py-2 text-sm font-semibold text-white hover:bg-sky-club-dark">{{ __('Włącz powiadomienia') }}</button>
                </div>
            </div>
        @endif
        <div data-push-ios-hint class="fixed inset-x-3 bottom-3 z-50 hidden rounded-2xl bg-white p-4 text-sm text-zinc-800 shadow-2xl ring-1 ring-zinc-200 sm:inset-x-auto sm:right-5 sm:max-w-sm" role="dialog">
            <p>{{ __('Na iPhonie powiadomienia działają po dodaniu strony do ekranu początkowego: stuknij „Udostępnij”, potem „Do ekranu początk.”, i otwórz stronę z ikony.') }}</p>
            <button type="button" onclick="this.parentElement.classList.add('hidden')" class="mt-3 font-semibold text-sky-club-dark">{{ __('Rozumiem') }}</button>
        </div>
    @endif

    <x-toasts />
    <x-confirm-modal />

    @livewireScripts
</body>
</html>

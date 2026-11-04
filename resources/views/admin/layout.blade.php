<!DOCTYPE html>
<html lang="pl" class="h-full bg-zinc-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $site->image('logo', asset('img/logo-32.png')) }}">
    <link rel="manifest" href="{{ route('admin.manifest') }}">
    <meta name="theme-color" content="{{ $site->color('color_dark') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Panel">
    <link rel="apple-touch-icon" href="{{ $site->image('logo', asset('img/logo-180.png')) }}">
    <title>@yield('title', 'Panel Administracyjny') - {{ $site['club_name'] }}</title>
    @include('partials.theme-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root { --color-like: {{ $site->color('color_like') }}; }</style>
    @livewireStyles
</head>
<body class="min-h-full admin-panel">
    @php
        $adminNavigation = [
            'Na co dzień' => [
                ['label' => 'Pulpit', 'route' => 'admin.dashboard', 'icon' => 'home'],
                ['label' => 'Do zatwierdzenia', 'route' => 'admin.approvals.index', 'icon' => 'inbox', 'can' => fn () => \App\Support\Approvals::sectionsFor(auth()->user())->isNotEmpty(), 'badge' => fn () => \App\Support\Approvals::pendingCountFor(auth()->user()), 'active' => ['admin.approvals.*', 'admin.parents.*', 'admin.accounts.*', 'admin.content-proposals.*', 'admin.comments.*', 'admin.reported-messages.*', 'admin.photo-tags.*', 'admin.player-change-requests.*', 'admin.profile-change-requests.*']],
                ['label' => 'Statystyki odwiedzin', 'route' => 'admin.statistics.index', 'icon' => 'chart', 'can' => 'statistics.view'],
                ['label' => 'Aktualności', 'route' => 'admin.articles.index', 'icon' => 'newspaper', 'can' => 'articles.manage'],
                ['label' => 'Powiadomienia', 'route' => 'admin.push.index', 'icon' => 'bell', 'can' => fn () => app(\App\Support\SiteSettings::class)['push_enabled'] && auth()->user()->can('articles.manage')],
                ['label' => 'Plan treningów', 'route' => 'admin.trainings.index', 'icon' => 'clock', 'can' => 'trainings.manage'],
                ['label' => 'Mecze i wyniki', 'route' => 'admin.games.index', 'icon' => 'ball', 'can' => 'games.manage'],
                ['label' => 'Turnieje', 'route' => 'admin.tournaments.index', 'icon' => 'flag', 'can' => 'tournaments.manage'],
                ['label' => 'Minisiatkówka', 'route' => 'admin.mini.index', 'icon' => 'star', 'can' => fn () => app(\App\Support\SiteSettings::class)['module_mini'] && auth()->user()->can('mini.manage')],
                ['label' => 'Transmisje i zdjęcia meczów', 'route' => 'admin.media.index', 'icon' => 'video', 'can' => 'media.manage'],
                ['label' => 'Galerie', 'route' => 'admin.galleries.index', 'icon' => 'photo', 'can' => 'media.manage'],
                ['label' => 'Miniatury transmisji', 'route' => 'thumbnails.index', 'icon' => 'slides', 'can' => 'graphics.use'],
                ['label' => 'Logotypy klubów', 'route' => 'logos.index', 'icon' => 'photo', 'can' => fn () => app(\App\Support\SiteSettings::class)['logos_page'] && auth()->user()->can('graphics.use')],
                ['label' => 'Slajdy na stronie głównej', 'route' => 'admin.hero-slides.index', 'icon' => 'slides', 'can' => 'homepage.manage'],
            ],
            'Treści strony' => [
                ['label' => 'Podstrony', 'route' => 'admin.pages.index', 'icon' => 'list', 'can' => 'site.manage'],
                ['label' => 'Wyróżnienia i certyfikaty', 'route' => 'admin.awards.index', 'icon' => 'star', 'can' => 'site.manage'],
            ],
            'Klub' => [
                ['label' => 'Drużyny i sztab', 'route' => 'admin.teams.index', 'icon' => 'users', 'can' => 'teams.manage'],
                ['label' => 'Zawodnicy', 'route' => 'admin.players.index', 'icon' => 'user', 'can' => 'teams.manage'],
                ['label' => 'Osoby w drużynach', 'route' => 'admin.people.index', 'icon' => 'users', 'can' => fn () => ! auth()->user()->can('users.manage') && auth()->user()->can('people.view')],
                ['label' => 'Wychowankowie', 'route' => 'admin.alumni.index', 'icon' => 'star', 'can' => 'articles.manage'],
                ['label' => 'Sponsorzy', 'route' => 'admin.sponsors.index', 'icon' => 'briefcase', 'can' => 'sponsors.manage'],
                ['label' => 'Wspieraj klub', 'route' => 'admin.support.index', 'icon' => 'heart', 'can' => 'sponsors.manage'],
                ['label' => 'Dokumenty', 'route' => 'admin.documents.index', 'icon' => 'document', 'can' => 'documents.manage'],
            ],
            'System' => [
                ['label' => 'Ustawienia systemu', 'route' => 'admin.settings.index', 'icon' => 'cog', 'can' => 'system'],
                ['label' => 'Użytkownicy', 'route' => 'admin.users.index', 'icon' => 'key', 'can' => 'users.manage'],
                ['label' => 'Role i uprawnienia', 'route' => 'admin.roles.index', 'icon' => 'shield', 'can' => 'roles.manage'],
                ['label' => 'Pozycjonowanie (SEO)', 'route' => 'admin.seo.index', 'icon' => 'chart', 'can' => 'site.manage'],
                ['label' => 'Dziennik zmian', 'route' => 'admin.audit.index', 'icon' => 'list', 'can' => 'audit.view'],
                ['label' => 'Kosz', 'route' => 'admin.trash.index', 'icon' => 'trash', 'can' => 'trash'],
                ['label' => 'Import z plików', 'route' => 'admin.import.index', 'icon' => 'upload', 'can' => 'import.run'],
            ],
            'Ustawienia rozgrywek (rzadko)' => [
                ['label' => 'Tabele', 'route' => 'admin.standings.index', 'icon' => 'table', 'can' => 'games.manage'],
                ['label' => 'Rozgrywki', 'route' => 'admin.competitions.index', 'icon' => 'trophy', 'can' => 'competitions.manage'],
                ['label' => 'Rywale', 'route' => 'admin.rival-teams.index', 'icon' => 'versus', 'can' => 'competitions.manage'],
                ['label' => 'Sezony', 'route' => 'admin.seasons.index', 'icon' => 'calendar', 'can' => 'competitions.manage'],
            ],
        ];
    @endphp

    @php
        $me = auth()->user();
        $initials = collect(preg_split('/\s+/', trim($me->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
        $pendingApprovals = \App\Support\Approvals::pendingCountFor($me);

        // Dolne menu na telefonie: 4 skróty dobrane do głównej roli konta (tylko to, do czego ma dostęp) i „Menu”.
        $navAllowed = fn (array $item) => ! isset($item['can']) || ($item['can'] instanceof \Closure ? ($item['can'])() : $me->can($item['can']));
        $navItems = collect($adminNavigation)->flatten(1)->filter($navAllowed)->keyBy('route');
        $bottomLabels = ['admin.dashboard' => 'Pulpit', 'admin.approvals.index' => 'Zatwierdź', 'admin.games.index' => 'Mecze', 'admin.articles.index' => 'Aktualności', 'admin.trainings.index' => 'Treningi', 'admin.players.index' => 'Zawodnicy', 'admin.tournaments.index' => 'Turnieje', 'admin.mini.index' => 'Mini', 'admin.galleries.index' => 'Galerie', 'admin.media.index' => 'Transmisje', 'thumbnails.index' => 'Miniatury', 'admin.teams.index' => 'Drużyny'];
        $bottomPresets = [
            'admin' => ['admin.dashboard', 'admin.approvals.index', 'admin.games.index', 'admin.articles.index'],
            'boss' => ['admin.dashboard', 'admin.approvals.index', 'admin.games.index', 'admin.articles.index'],
            'trainer' => ['admin.dashboard', 'admin.trainings.index', 'admin.games.index', 'admin.players.index'],
            'staff' => ['admin.dashboard', 'admin.trainings.index', 'admin.games.index', 'admin.players.index'],
            'editor' => ['admin.dashboard', 'admin.articles.index', 'admin.galleries.index', 'admin.approvals.index'],
            'photographer' => ['admin.dashboard', 'admin.galleries.index', 'admin.media.index', 'thumbnails.index'],
            'coordinator' => ['admin.dashboard', 'admin.mini.index', 'admin.players.index', 'admin.approvals.index'],
            'moderator' => ['admin.dashboard', 'admin.approvals.index', 'admin.articles.index', 'admin.players.index'],
        ];
        $bottomNav = collect($bottomPresets[$me->roles()->first()?->value] ?? [])
            ->merge(['admin.dashboard', 'admin.approvals.index', 'admin.games.index', 'admin.trainings.index', 'admin.articles.index', 'admin.tournaments.index', 'admin.players.index', 'admin.mini.index', 'admin.galleries.index'])
            ->unique()->filter(fn (string $route) => $navItems->has($route))->take(4)
            ->map(fn (string $route) => [...$navItems[$route], 'label' => $bottomLabels[$route] ?? $navItems[$route]['label']]);
    @endphp

    {{-- Pasek ładowania u góry: przejście na inną stronę i zapis w Livewire (resources/js/admin-app.js). --}}
    <div data-admin-progress class="pointer-events-none fixed inset-x-0 top-0 z-[70] h-0.5" aria-hidden="true"><div class="h-full w-0 bg-gradient-to-r from-sky-400 to-sky-600 opacity-0 shadow-[0_0_8px] shadow-sky-500/60"></div></div>

    <div x-data="{ menu: false }" @keydown.escape.window="menu = false">
        {{-- Menu boczne: stałe na dużym ekranie, wysuwane na telefonie. --}}
        <div x-show="menu" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-zinc-950/50 backdrop-blur-sm lg:hidden" @click="menu = false"></div>

        <aside :class="menu ? 'translate-x-0' : '-translate-x-full'" class="admin-sidebar fixed inset-y-0 left-0 z-50 flex pt-[env(safe-area-inset-top)] w-72 -translate-x-full flex-col bg-ink text-zinc-300 transition-transform duration-200 lg:w-64 lg:translate-x-0">
            <div class="flex h-16 shrink-0 items-center justify-between gap-3 border-b border-white/[0.06] px-5">
                <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-3">
                    <img src="{{ $site->image('logo', asset('img/logo-96.png')) }}" alt="" class="size-9 shrink-0 object-contain">
                    <span class="min-w-0 leading-tight">
                        <span class="block truncate text-sm font-semibold text-white">{{ $site['club_name'] }}</span>
                        <span class="block text-xs text-zinc-500">Panel klubu</span>
                    </span>
                </a>
                <button type="button" class="rounded-md p-1.5 text-zinc-400 hover:bg-white/10 hover:text-white lg:hidden" @click="menu = false" aria-label="Zamknij menu">
                    <x-admin-icon name="close" />
                </button>
            </div>

            <nav class="admin-scroll flex-1 overflow-y-auto px-3 py-4" aria-label="Menu panelu">
                @include('admin.partials.nav')
            </nav>

            <div class="shrink-0 border-t border-white/[0.06] p-3">
                <x-theme-switch dark class="mb-2 flex w-full" />
                <div class="flex items-center gap-3 rounded-lg px-2 py-2">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-sky-500/15 text-xs font-semibold text-sky-300 ring-1 ring-sky-400/30">{{ $initials }}</span>
                    <a href="{{ route('profile.edit') }}" class="min-w-0 flex-1 leading-tight hover:text-white">
                        <span class="block truncate text-sm font-medium text-zinc-100">{{ $me->name }}</span>
                        <span class="block truncate text-xs text-zinc-500">{{ $me->roles()->map->label()->join(', ') ?: 'Konto' }}</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-md p-2 text-zinc-400 transition hover:bg-white/10 hover:text-white" title="Wyloguj" aria-label="Wyloguj">
                            <x-admin-icon name="logout" />
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="lg:pl-64">
            {{-- Górny pasek: ścieżka, podgląd strony i rzeczy do zatwierdzenia. --}}
            <header data-admin-header class="sticky top-0 z-30 flex h-[calc(4rem+env(safe-area-inset-top))] items-center gap-3 border-b border-zinc-200/80 bg-white/80 px-4 pt-[env(safe-area-inset-top)] backdrop-blur-md sm:px-6 lg:px-8">
                <button type="button" class="-ml-1 rounded-md p-2 text-zinc-600 hover:bg-zinc-100 lg:hidden" @click="menu = true" aria-label="Otwórz menu">
                    <x-admin-icon name="menu" />
                </button>
                <nav class="flex min-w-0 items-center gap-1.5 text-sm" aria-label="Ścieżka">
                    <a href="{{ route('admin.dashboard') }}" class="hidden shrink-0 text-zinc-500 hover:text-zinc-900 sm:inline">Panel</a>
                    <x-admin-icon name="chevron" class="hidden size-3.5 text-zinc-300 sm:block" />
                    <span class="truncate font-medium text-zinc-900">@yield('title', 'Pulpit')</span>
                </nav>
                <div class="ml-auto flex items-center gap-1 sm:gap-2">
                    @if($pendingApprovals > 0)
                        <a href="{{ route('admin.approvals.index') }}" class="relative rounded-md p-2 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900" title="Do zatwierdzenia: {{ $pendingApprovals }}" aria-label="Do zatwierdzenia: {{ $pendingApprovals }}">
                            <x-admin-icon name="inbox" />
                            <span class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-amber-400 px-1 text-[10px] font-bold text-zinc-900">{{ $pendingApprovals }}</span>
                        </a>
                    @endif
                    <x-messages-link :dark="false" class="h-9! w-9! rounded-md!" />
                    <a href="{{ route('home') }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium text-zinc-600 ring-1 ring-zinc-200 transition hover:bg-zinc-50 hover:text-zinc-900">
                        <x-admin-icon name="external" class="size-4" />
                        <span class="hidden sm:inline">Zobacz stronę</span>
                    </a>
                </div>
            </header>

            <main data-admin-main class="mx-auto max-w-7xl pt-6 pb-28 lg:py-8">
                @yield('content')
            </main>
        </div>

        {{-- Dolne menu na telefonie: najczęstsze miejsca pod kciukiem, reszta pod „Menu”. --}}
        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-zinc-200 bg-white/95 pb-[env(safe-area-inset-bottom)] text-zinc-500 backdrop-blur-xl lg:hidden" aria-label="Menu dolne panelu" data-admin-bottom-nav>
            <div class="grid" style="grid-template-columns: repeat({{ $bottomNav->count() + 1 }}, minmax(0, 1fr))">
                @foreach($bottomNav as $item)
                    @php
                        $isActive = request()->routeIs(...(array) ($item['active'] ?? (str_ends_with($item['route'], '.index') ? \Illuminate\Support\Str::beforeLast($item['route'], '.').'.*' : $item['route'])));
                        $badge = isset($item['badge']) ? value($item['badge']) : null;
                    @endphp
                    <a href="{{ route($item['route']) }}" @if($isActive) aria-current="page" @endif @class(['relative flex min-h-14 flex-col items-center justify-center gap-0.5 px-1 text-[11px] font-medium', 'font-semibold text-sky-700' => $isActive])>
                        <span data-pill class="relative flex h-8 w-14 items-center justify-center rounded-full">
                            <x-admin-icon :name="$item['icon']" class="size-6" />
                            @if(! empty($badge))<span class="absolute -top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-amber-400 px-1 text-[10px] font-bold text-zinc-900">{{ $badge }}</span>@endif
                        </span>
                        <span class="max-w-full truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
                <button type="button" @click="menu = true" class="flex min-h-14 flex-col items-center justify-center gap-0.5 px-1 text-[11px] font-medium" aria-label="Całe menu panelu">
                    <span class="flex h-8 w-14 items-center justify-center"><x-admin-icon name="menu" class="size-6" /></span>
                    <span>Menu</span>
                </button>
            </div>
        </nav>
    </div>

    <x-toasts />
    <x-confirm-modal />

    @livewireScripts
</body>
</html>

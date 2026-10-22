<!DOCTYPE html>
<html lang="pl" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $site->image('logo', asset('img/logo-32.png')) }}">
    <link rel="apple-touch-icon" href="{{ $site->image('logo', asset('img/logo-180.png')) }}">
    <title>@yield('title', 'Panel Administracyjny') - Joker Piła</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full admin-panel">
    @php
        $adminNavigation = [
            'Na co dzień' => [
                ['label' => 'Pulpit', 'route' => 'admin.dashboard', 'icon' => '🏠'],
                ['label' => 'Do zatwierdzenia', 'route' => 'admin.approvals.index', 'icon' => '📥', 'can' => fn () => \App\Support\Approvals::sectionsFor(auth()->user())->isNotEmpty(), 'badge' => fn () => \App\Support\Approvals::pendingCountFor(auth()->user()), 'active' => ['admin.approvals.*', 'admin.accounts.*', 'admin.content-proposals.*', 'admin.comments.*', 'admin.photo-tags.*', 'admin.player-change-requests.*', 'admin.profile-change-requests.*']],
                ['label' => 'Statystyki odwiedzin', 'route' => 'admin.statistics.index', 'icon' => '📈', 'can' => 'statistics.view'],
                ['label' => 'Aktualności', 'route' => 'admin.articles.index', 'icon' => '📰', 'can' => 'articles.manage'],
                ['label' => 'Plan treningów', 'route' => 'admin.trainings.index', 'icon' => '🗓️', 'can' => 'trainings.manage'],
                ['label' => 'Mecze i wyniki', 'route' => 'admin.games.index', 'icon' => '🏐', 'can' => 'games.manage'],
                ['label' => 'Turnieje', 'route' => 'admin.tournaments.index', 'icon' => '🥇', 'can' => 'tournaments.manage'],
                ['label' => 'Transmisje i zdjęcia meczów', 'route' => 'admin.media.index', 'icon' => '🎥', 'can' => 'media.manage'],
                ['label' => 'Galerie', 'route' => 'admin.galleries.index', 'icon' => '📷', 'can' => 'media.manage'],
                ['label' => 'Slajdy na stronie głównej', 'route' => 'admin.hero-slides.index', 'icon' => '🖼️', 'can' => 'homepage.manage'],
            ],
            'Wygląd strony' => [
                ['label' => 'Wygląd i ustawienia', 'route' => 'admin.settings.index', 'icon' => '🎨', 'can' => 'site.manage'],
                ['label' => 'Podstrony', 'route' => 'admin.pages.index', 'icon' => '📑', 'can' => 'site.manage'],
            ],
            'Klub' => [
                ['label' => 'Drużyny i sztab', 'route' => 'admin.teams.index', 'icon' => '👥', 'can' => 'teams.manage'],
                ['label' => 'Zawodnicy', 'route' => 'admin.players.index', 'icon' => '🎽', 'can' => 'teams.manage'],
                ['label' => 'Wychowankowie', 'route' => 'admin.alumni.index', 'icon' => '⭐', 'can' => 'articles.manage'],
                ['label' => 'Sponsorzy', 'route' => 'admin.sponsors.index', 'icon' => '🤝', 'can' => 'sponsors.manage'],
                ['label' => 'Wspieraj klub', 'route' => 'admin.support.index', 'icon' => '💙', 'can' => 'sponsors.manage'],
                ['label' => 'Dokumenty', 'route' => 'admin.documents.index', 'icon' => '📄', 'can' => 'documents.manage'],
                ['label' => 'Użytkownicy', 'route' => 'admin.users.index', 'icon' => '🔑', 'can' => 'users.manage'],
                ['label' => 'Role i uprawnienia', 'route' => 'admin.roles.index', 'icon' => '🛡️', 'can' => 'roles.manage'],
                ['label' => 'Import z plików', 'route' => 'admin.import.index', 'icon' => '📥', 'can' => 'import.run'],
            ],
            'Ustawienia rozgrywek (rzadko)' => [
                ['label' => 'Tabele', 'route' => 'admin.standings.index', 'icon' => '📊', 'can' => 'games.manage'],
                ['label' => 'Rozgrywki', 'route' => 'admin.competitions.index', 'icon' => '🏆', 'can' => 'competitions.manage'],
                ['label' => 'Rywale', 'route' => 'admin.rival-teams.index', 'icon' => '🆚', 'can' => 'competitions.manage'],
                ['label' => 'Sezony', 'route' => 'admin.seasons.index', 'icon' => '📅', 'can' => 'competitions.manage'],
            ],
        ];
    @endphp

    <div class="min-h-full md:flex">
        <aside class="md:flex md:w-64 md:flex-col bg-[#12141a] text-white md:min-h-screen">
            <div class="flex items-center justify-between px-4 py-4 border-b border-white/10">
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-3">
                    <img src="{{ $site->image('logo', asset('img/logo-96.png')) }}" alt="" class="h-9 w-9 object-contain">
                    <span class="font-bold leading-tight">Joker Piła<span class="block text-xs font-normal text-sky-300">Panel klubu</span></span>
                </a>
                <a href="{{ route('home') }}" class="text-xs text-zinc-400 hover:text-white" target="_blank">Zobacz stronę ↗</a>
            </div>

            <details class="group md:hidden border-b border-white/10">
                <summary class="cursor-pointer list-none px-4 py-3 text-sm font-semibold">☰ Menu</summary>
                <div class="px-2 pb-3">@include('admin.partials.nav')</div>
            </details>

            <nav class="hidden md:block flex-1 overflow-y-auto px-2 py-4">
                @include('admin.partials.nav')
            </nav>

            <div class="hidden md:block p-4 border-t border-white/10">
                <p class="text-xs text-zinc-400 mb-2">Zalogowano: {{ auth()->user()->name }}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg bg-white/10 px-3 py-2 text-sm font-medium transition hover:bg-white/20">Wyloguj</button>
                </form>
            </div>
        </aside>

        <div class="flex-1 min-w-0">
            <!-- Page Content -->
            <main>
                <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                @yield('content')
            </div>
            </main>
        </div>
    </div>

    <x-toasts />
    <x-confirm-modal />

    @livewireScripts
</body>
</html>

<!DOCTYPE html>
<html lang="pl" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/JOKER-18.png') }}">
    <link rel="shortcut icon" href="{{ asset('img/JOKER-18.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/JOKER-18.png') }}">
    <title>@yield('title', 'Panel Administracyjny') - Joker Piła</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full">
    <div class="min-h-full md:flex">
        <aside class="hidden md:flex md:w-56 md:flex-col bg-gradient-to-b from-blue-900 via-blue-800 to-blue-900 text-white shadow-xl">
            <div class="px-4 py-4 border-b border-white/10">
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2">
                    <span class="text-lg font-bold">🏐 Joker Admin</span>
                </a>
            </div>

            <nav class="flex-1 overflow-y-auto px-2.5 py-3 space-y-2.5">
                <a href="{{ route('admin.dashboard') }}" class="block rounded-lg border px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.dashboard') ? 'border-white/90 bg-white text-blue-900 shadow-sm' : 'border-white/65 bg-white/85 text-blue-900 hover:bg-white' }}">Dashboard</a>
                <a href="{{ route('admin.teams.index') }}" class="block rounded-lg border px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.teams.*') ? 'border-white/90 bg-white text-blue-900 shadow-sm' : 'border-white/65 bg-white/85 text-blue-900 hover:bg-white' }}">Drużyny</a>
                <a href="{{ route('admin.players.index') }}" class="block rounded-lg border px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.players.*') ? 'border-white/90 bg-white text-blue-900 shadow-sm' : 'border-white/65 bg-white/85 text-blue-900 hover:bg-white' }}">Zawodnicy</a>
                <a href="{{ route('admin.games.index') }}" class="block rounded-lg border px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.games.*') ? 'border-white/90 bg-white text-blue-900 shadow-sm' : 'border-white/65 bg-white/85 text-blue-900 hover:bg-white' }}">Mecze</a>
                <a href="{{ route('admin.competitions.index') }}" class="block rounded-lg border px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.competitions.*') ? 'border-white/90 bg-white text-blue-900 shadow-sm' : 'border-white/65 bg-white/85 text-blue-900 hover:bg-white' }}">Rozgrywki</a>
                <a href="{{ route('admin.competition-levels.index') }}" class="block rounded-lg border px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.competition-levels.*') ? 'border-white/90 bg-white text-blue-900 shadow-sm' : 'border-white/65 bg-white/85 text-blue-900 hover:bg-white' }}">Poziomy rozgrywek</a>
                <a href="{{ route('admin.standings.index') }}" class="block rounded-lg border px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.standings.*') ? 'border-white/90 bg-white text-blue-900 shadow-sm' : 'border-white/65 bg-white/85 text-blue-900 hover:bg-white' }}">Tabele</a>
                <a href="{{ route('admin.seasons.index') }}" class="block rounded-lg border px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.seasons.*') ? 'border-white/90 bg-white text-blue-900 shadow-sm' : 'border-white/65 bg-white/85 text-blue-900 hover:bg-white' }}">Sezony</a>
                <a href="{{ route('admin.rival-teams.index') }}" class="block rounded-lg border px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.rival-teams.*') ? 'border-white/90 bg-white text-blue-900 shadow-sm' : 'border-white/65 bg-white/85 text-blue-900 hover:bg-white' }}">Rywale</a>
                <a href="{{ route('admin.articles.index') }}" class="block rounded-lg border px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.articles.*') ? 'border-white/90 bg-white text-blue-900 shadow-sm' : 'border-white/65 bg-white/85 text-blue-900 hover:bg-white' }}">Artykuły</a>
                <a href="{{ route('admin.hero-slides.index') }}" class="block rounded-lg border px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.hero-slides.*') ? 'border-white/90 bg-white text-blue-900 shadow-sm' : 'border-white/65 bg-white/85 text-blue-900 hover:bg-white' }}">Slider</a>
                <a href="{{ route('admin.documents.index') }}" class="block rounded-lg border px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.documents.*') ? 'border-white/90 bg-white text-blue-900 shadow-sm' : 'border-white/65 bg-white/85 text-blue-900 hover:bg-white' }}">Dokumenty</a>
                <a href="{{ route('admin.sponsors.index') }}" class="block rounded-lg border px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.sponsors.*') ? 'border-white/90 bg-white text-blue-900 shadow-sm' : 'border-white/65 bg-white/85 text-blue-900 hover:bg-white' }}">Sponsorzy</a>
                <a href="{{ route('admin.users.index') }}" class="block rounded-lg border px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.users.*') ? 'border-white/90 bg-white text-blue-900 shadow-sm' : 'border-white/65 bg-white/85 text-blue-900 hover:bg-white' }}">Użytkownicy</a>
            </nav>

            <div class="p-4 border-t border-white/10">
                <p class="text-xs text-blue-200 mb-2">{{ auth()->user()->name }}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg border border-white/70 bg-white/90 px-3 py-2 text-sm font-medium text-blue-900 transition hover:bg-white">
                        Wyloguj
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 min-w-0">
            <div class="md:hidden bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white shadow">
                <div class="px-4 py-4 flex items-center justify-between">
                    <a href="{{ route('admin.dashboard') }}" class="text-base font-bold">🏐 Joker Piła Admin</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-md border border-white/70 bg-white/90 px-3 py-1.5 text-xs font-medium text-blue-900">Wyloguj</button>
                    </form>
                </div>
                <div class="px-3 pb-4">
                    <div class="flex flex-col gap-2.5">
                        <a href="{{ route('admin.dashboard') }}" class="rounded-md border border-white/70 bg-white/90 px-3 py-2 text-xs text-blue-900 {{ request()->routeIs('admin.dashboard') ? 'ring-1 ring-white' : '' }}">Dashboard</a>
                        <a href="{{ route('admin.teams.index') }}" class="rounded-md border border-white/70 bg-white/90 px-3 py-2 text-xs text-blue-900 {{ request()->routeIs('admin.teams.*') ? 'ring-1 ring-white' : '' }}">Drużyny</a>
                        <a href="{{ route('admin.players.index') }}" class="rounded-md border border-white/70 bg-white/90 px-3 py-2 text-xs text-blue-900 {{ request()->routeIs('admin.players.*') ? 'ring-1 ring-white' : '' }}">Zawodnicy</a>
                        <a href="{{ route('admin.games.index') }}" class="rounded-md border border-white/70 bg-white/90 px-3 py-2 text-xs text-blue-900 {{ request()->routeIs('admin.games.*') ? 'ring-1 ring-white' : '' }}">Mecze</a>
                        <a href="{{ route('admin.standings.index') }}" class="rounded-md border border-white/70 bg-white/90 px-3 py-2 text-xs text-blue-900 {{ request()->routeIs('admin.standings.*') ? 'ring-1 ring-white' : '' }}">Tabele</a>
                        <a href="{{ route('admin.hero-slides.index') }}" class="rounded-md border border-white/70 bg-white/90 px-3 py-2 text-xs text-blue-900 {{ request()->routeIs('admin.hero-slides.*') ? 'ring-1 ring-white' : '' }}">Slider</a>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <main>
                <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                @if(session('success'))
                    <div class="mb-4 rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @yield('content')
            </div>
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>

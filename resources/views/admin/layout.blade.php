<!DOCTYPE html>
<html lang="pl" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel Administracyjny') - Joker Piła</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full">
    <div class="min-h-full">
        <!-- Navbar -->
        <nav class="bg-gradient-to-r from-blue-600 via-blue-700 to-blue-900 shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center">
                        <a href="{{ route('admin.dashboard') }}" class="flex-shrink-0">
                            <span class="text-white font-bold text-xl">🏐 Joker Piła Admin</span>
                        </a>
                        <div class="hidden md:block">
                            <div class="ml-10 flex items-baseline space-x-4">
                                <a href="{{ route('admin.dashboard') }}" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-blue-800' : '' }}">
                                    Dashboard
                                </a>
                                <a href="{{ route('admin.teams.index') }}" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.teams.*') ? 'bg-blue-800' : '' }}">
                                    Drużyny
                                </a>
                                <a href="{{ route('admin.players.index') }}" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.players.*') ? 'bg-blue-800' : '' }}">
                                    Zawodnicy
                                </a>
                                <a href="{{ route('admin.games.index') }}" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.games.*') ? 'bg-blue-800' : '' }}">
                                    Mecze
                                </a>
                                <a href="{{ route('admin.competitions.index') }}" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.competitions.*') ? 'bg-blue-800' : '' }}">
                                    Rozgrywki
                                </a>
                                <a href="{{ route('admin.competition-levels.index') }}" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.competition-levels.*') ? 'bg-blue-800' : '' }}">
                                    Poziomy rozgrywek
                                </a>
                                <a href="{{ route('admin.standings.index') }}" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.standings.*') ? 'bg-blue-800' : '' }}">
                                    Tabele
                                </a>
                                <a href="{{ route('admin.seasons.index') }}" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.seasons.*') ? 'bg-blue-800' : '' }}">
                                    Sezony
                                </a>
                                <a href="{{ route('admin.rival-teams.index') }}" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.rival-teams.*') ? 'bg-blue-800' : '' }}">
                                    Rywale
                                </a>
                                <a href="{{ route('admin.articles.index') }}" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.articles.*') ? 'bg-blue-800' : '' }}">
                                    Artykuły
                                </a>
                                <a href="{{ route('admin.documents.index') }}" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.documents.*') ? 'bg-blue-800' : '' }}">
                                    Dokumenty
                                </a>
                                <a href="{{ route('admin.sponsors.index') }}" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.sponsors.*') ? 'bg-blue-800' : '' }}">
                                    Sponsorzy
                                </a>
                                <a href="{{ route('admin.users.index') }}" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.users.*') ? 'bg-blue-800' : '' }}">
                                    Użytkownicy
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="text-white text-sm mr-4">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-white hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium">
                                Wyloguj
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

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

    @livewireScripts
</body>
</html>

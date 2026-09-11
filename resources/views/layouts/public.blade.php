<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/JOKER-18.png') }}">
    <link rel="shortcut icon" href="{{ asset('img/JOKER-18.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/JOKER-18.png') }}">
    <title>@yield('title', 'Joker Piła - Klub Siatkówki')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center space-x-3">
                    <img src="{{ asset('img/JOKER-18.png') }}" alt="Logo Joker Piła" class="h-14 w-14 object-contain drop-shadow-lg md:h-16 md:w-16" />
                    <div>
                        <div class="text-xl font-bold">Joker Piła</div>
                        <div class="text-xs text-blue-200">Klub Siatkówki</div>
                    </div>
                </a>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-2">
                    <a href="{{ route('articles.index') }}" class="rounded-md border border-white/30 bg-white/15 px-3 py-1.5 text-xs font-medium text-blue-50 transition hover:bg-white/25 {{ request()->routeIs('articles.*') ? 'bg-white/30 font-semibold' : '' }}">
                        Aktualności
                    </a>
                    <a href="{{ route('schedule.index') }}" class="rounded-md border border-white/30 bg-white/15 px-3 py-1.5 text-xs font-medium text-blue-50 transition hover:bg-white/25 {{ request()->routeIs('schedule.*') ? 'bg-white/30 font-semibold' : '' }}">
                        Terminarz
                    </a>
                    <a href="{{ route('standings') }}" class="rounded-md border border-white/30 bg-white/15 px-3 py-1.5 text-xs font-medium text-blue-50 transition hover:bg-white/25 {{ request()->routeIs('standings') ? 'bg-white/30 font-semibold' : '' }}">
                        Tabela
                    </a>
                    <a href="{{ route('teams.index') }}" class="rounded-md border border-white/30 bg-white/15 px-3 py-1.5 text-xs font-medium text-blue-50 transition hover:bg-white/25 {{ request()->routeIs('teams.*') ? 'bg-white/30 font-semibold' : '' }}">
                        Drużyny
                    </a>
                    <a href="{{ route('contact.index') }}" class="rounded-md border border-white/30 bg-white/15 px-3 py-1.5 text-xs font-medium text-blue-50 transition hover:bg-white/25 {{ request()->routeIs('contact.*') ? 'bg-white/30 font-semibold' : '' }}">
                        Kontakt
                    </a>

                    @auth
                        @if(auth()->user()->canManageContent())
                            <a href="{{ route('admin.dashboard') }}" class="bg-white text-blue-900 px-4 py-2 rounded-lg hover:bg-blue-50 transition font-medium">
                                Panel Admina
                            </a>
                        @endif
                        <a href="{{ route('profile.edit') }}" class="rounded-md border border-white/30 bg-white/15 px-3 py-1.5 text-xs font-medium text-blue-50 transition hover:bg-white/25 {{ request()->routeIs('profile.*') ? 'bg-white/30 font-semibold' : '' }}">Mój profil</a>
                    @endauth

                    <div class="ml-2 flex items-center">
                        <button id="desktop-search-toggle" type="button" class="flex h-8 w-8 items-center justify-center rounded-md border border-white/30 bg-white/15 text-blue-50 transition hover:bg-white/25" aria-label="Otwórz wyszukiwarkę" title="Szukaj">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 6 6a7.5 7.5 0 0 0 10.65 10.65z"></path>
                            </svg>
                        </button>
                        <div id="desktop-search-wrapper" class="w-0 overflow-hidden opacity-0 pointer-events-none transition-all duration-300 ease-out">
                            <div class="w-72 pl-2">
                                @livewire('global-search')
                            </div>
                        </div>
                    </div>

                    @auth
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="ml-2 flex h-8 w-8 items-center justify-center rounded-md border border-red-200/60 bg-red-100/90 text-sm font-bold text-red-700 transition hover:bg-red-200" title="Wyloguj" aria-label="Wyloguj">
                                ⭘
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="ml-2 flex h-8 w-8 items-center justify-center rounded-md border border-emerald-200/70 bg-emerald-100/95 text-sm font-bold text-emerald-700 transition hover:bg-emerald-200" title="Zaloguj" aria-label="Zaloguj">
                            ⏽
                        </a>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <button class="md:hidden" id="mobile-menu-button">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>

            <!-- Mobile Menu -->
            <div class="hidden md:hidden pb-4" id="mobile-menu">
                <div class="flex flex-col space-y-2">
                    <a href="{{ route('articles.index') }}" class="rounded-md border border-white/30 bg-white/15 px-3 py-2 text-xs font-medium text-blue-50 transition hover:bg-white/25">Aktualności</a>
                    <a href="{{ route('schedule.index') }}" class="rounded-md border border-white/30 bg-white/15 px-3 py-2 text-xs font-medium text-blue-50 transition hover:bg-white/25">Terminarz</a>
                    <a href="{{ route('standings') }}" class="rounded-md border border-white/30 bg-white/15 px-3 py-2 text-xs font-medium text-blue-50 transition hover:bg-white/25">Tabela</a>
                    <a href="{{ route('teams.index') }}" class="rounded-md border border-white/30 bg-white/15 px-3 py-2 text-xs font-medium text-blue-50 transition hover:bg-white/25">Drużyny</a>
                    <a href="{{ route('contact.index') }}" class="rounded-md border border-white/30 bg-white/15 px-3 py-2 text-xs font-medium text-blue-50 transition hover:bg-white/25">Kontakt</a>
                    @auth
                        @if(auth()->user()->canManageContent())
                            <a href="{{ route('admin.dashboard') }}" class="bg-white text-blue-900 px-4 py-2 rounded-lg hover:bg-blue-50 transition font-medium inline-block text-center">
                                Panel Admina
                            </a>
                        @endif
                        <a href="{{ route('profile.edit') }}" class="rounded-md border border-white/30 bg-white/15 px-3 py-2 text-xs font-medium text-blue-50 transition hover:bg-white/25">Mój profil</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-md border border-red-200/60 bg-red-100/90 px-3 py-2 text-xs font-bold text-red-700 transition hover:bg-red-200 w-full text-left">
                                ⭘ Wyloguj
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="rounded-md border border-emerald-200/70 bg-emerald-100/95 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-200 inline-block text-center">
                            ⏽ Zaloguj
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="container mx-auto px-4 mt-4">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="container mx-auto px-4 mt-4">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-16">
        <div class="container mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- About -->
                <div>
                    <h3 class="text-lg font-bold mb-4">Joker Piła</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Klub siatkówki z Piły. Pasja, sport i tradycja.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-bold mb-4">Szybkie Linki</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('home') }}" class="text-gray-400 hover:text-white transition">Strona Główna</a></li>
                        <li><a href="{{ route('teams.index') }}" class="text-gray-400 hover:text-white transition">Drużyny</a></li>
                        <li><a href="{{ route('articles.index') }}" class="text-gray-400 hover:text-white transition">Aktualności</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Kontakt</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h3 class="text-lg font-bold mb-4">Kontakt</h3>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li>Email: kontakt@jokerpila.pl</li>
                        <li>Tel: +48 123 456 789</li>
                        <li>Adres: ul. Sportowa 1, Piła</li>
                    </ul>
                </div>

                <!-- Social Media -->
                <div>
                    <h3 class="text-lg font-bold mb-4">Social Media</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400 text-sm">
                <p>&copy; {{ date('Y') }} Joker Piła. Wszelkie prawa zastrzeżone.</p>
            </div>
        </div>
    </footer>

    @livewireScripts

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });

        // Desktop search toggle
        const desktopSearchToggle = document.getElementById('desktop-search-toggle');
        const desktopSearchWrapper = document.getElementById('desktop-search-wrapper');

        if (desktopSearchToggle && desktopSearchWrapper) {
            desktopSearchToggle.addEventListener('click', function () {
                const isHidden = desktopSearchWrapper.classList.contains('w-0');

                if (isHidden) {
                    desktopSearchWrapper.classList.remove('w-0', 'opacity-0', 'pointer-events-none');
                    desktopSearchWrapper.classList.add('w-72', 'opacity-100');
                } else {
                    desktopSearchWrapper.classList.add('w-0', 'opacity-0', 'pointer-events-none');
                    desktopSearchWrapper.classList.remove('w-72', 'opacity-100');
                }
            });

            document.addEventListener('click', function (event) {
                const clickedInsideSearch = desktopSearchWrapper.contains(event.target);
                const clickedToggle = desktopSearchToggle.contains(event.target);

                if (!clickedInsideSearch && !clickedToggle) {
                    desktopSearchWrapper.classList.add('w-0', 'opacity-0', 'pointer-events-none');
                    desktopSearchWrapper.classList.remove('w-72', 'opacity-100');
                }
            });
        }
    </script>
</body>
</html>

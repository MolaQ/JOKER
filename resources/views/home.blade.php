@extends('layouts.public')

@section('title', 'Joker Piła - Strona Główna')

@section('content')
<style>
    @keyframes heroLogoPulse {
        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }
    }

    .hero-logo-pulse {
        animation: heroLogoPulse 6s ease-in-out infinite;
        will-change: transform;
    }
</style>

<!-- Hero Slider -->
<section class="relative overflow-hidden bg-blue-950 text-white">
    @if($heroSlides->isNotEmpty())
        <div id="home-hero-slider" class="relative" style="height: 68vh; min-height: 480px; max-height: 760px;">
            <div class="pointer-events-none absolute inset-0 z-10 flex items-start justify-start p-4 md:p-6">
                <img src="{{ asset('img/JOKER-18.png') }}" alt="Herb klubu Joker Piła" class="hero-logo-pulse h-20 w-20 object-contain drop-shadow-2xl md:h-32 md:w-32" />
            </div>

            @foreach($heroSlides as $slide)
                <article class="hero-slide absolute inset-0 {{ $loop->first ? 'opacity-100' : 'opacity-0 pointer-events-none' }} transition-opacity duration-700 ease-in-out" data-slide-index="{{ $loop->index }}" aria-hidden="{{ $loop->first ? 'false' : 'true' }}">
                    <img src="{{ Storage::url($slide->image_path) }}" alt="{{ $slide->title }}" class="h-full w-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-r from-black/75 via-black/55 to-blue-950/35"></div>

                    <div class="absolute inset-0 z-20 flex items-center">
                        <div class="container mx-auto px-4">
                            <div class="max-w-3xl rounded-2xl border border-white/20 bg-white/10 p-8 shadow-xl backdrop-blur-sm md:p-10">
                                <p class="mb-3 inline-flex rounded-full bg-blue-500/35 px-3 py-1 text-xs font-semibold tracking-wide text-blue-100 ring-1 ring-white/25">Joker Piła</p>
                                <h1 class="text-4xl font-bold leading-tight md:text-6xl">{{ $slide->title }}</h1>

                                @if($slide->subtitle)
                                    <p class="mt-4 max-w-2xl text-base text-blue-100 md:text-xl">{{ $slide->subtitle }}</p>
                                @endif

                                @if($slide->cta_label && $slide->cta_url)
                                    <div class="mt-7">
                                        <a href="{{ $slide->cta_url }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-blue-900 shadow-md transition hover:bg-blue-50">
                                            {{ $slide->cta_label }}
                                            <span aria-hidden="true">→</span>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach

            <button id="home-hero-prev" type="button" class="absolute left-4 top-1/2 z-20 -translate-y-1/2 rounded-full border border-white/35 bg-black/35 p-3 text-white backdrop-blur-sm transition hover:bg-black/55" aria-label="Poprzedni slajd">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7" />
                </svg>
            </button>
            <button id="home-hero-next" type="button" class="absolute right-4 top-1/2 z-20 -translate-y-1/2 rounded-full border border-white/35 bg-black/35 p-3 text-white backdrop-blur-sm transition hover:bg-black/55" aria-label="Następny slajd">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                </svg>
            </button>

            <div id="home-hero-dots" class="absolute bottom-6 left-1/2 z-20 flex -translate-x-1/2 gap-2">
                @foreach($heroSlides as $slide)
                    <button
                        type="button"
                        class="hero-dot h-2.5 w-2.5 rounded-full border border-white/70 {{ $loop->first ? 'bg-white' : 'bg-white/30' }}"
                        data-target-index="{{ $loop->index }}"
                        aria-label="Przejdź do slajdu {{ $loop->iteration }}"
                    ></button>
                @endforeach
            </div>
        </div>
    @else
        <div class="relative overflow-hidden bg-blue-950 text-white">
            <div class="absolute inset-0 opacity-80">
                <x-hero-team-image class="h-full w-full object-cover" />
            </div>

            <div class="relative container mx-auto px-4 py-24">
                <div class="max-w-3xl rounded-2xl border border-white/20 bg-black/30 p-8 backdrop-blur-sm">
                    <h1 class="text-5xl md:text-6xl font-bold mb-6">Joker Piła</h1>
                    <p class="text-xl md:text-2xl text-blue-100">
                        Nowoczesny klub siatkarski. Rozwój od młodzika do seniora — jedna drużyna, jeden charakter.
                    </p>
                </div>
            </div>
        </div>
    @endif
</section>

<!-- Featured Articles -->
@if($featured_articles->count() > 0)
<section class="container mx-auto px-4 py-16">
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold text-gray-900">Wyróżnione</h2>
        <a href="{{ route('articles.index') }}" class="text-blue-900 hover:text-blue-700 font-medium">
            Zobacz wszystkie →
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($featured_articles as $article)
            <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition">
                @if($article->featured_image)
                    <img src="{{ Storage::url($article->featured_image) }}" alt="{{ $article->title }}" class="w-full h-48 object-cover">
                @else
                    <x-placeholder-image label="Artykuł" height="h-48" class="rounded-none" />
                @endif

                <div class="p-6">
                    <div class="text-sm text-gray-500 mb-2">
                        {{ $article->published_at?->format('d.m.Y') }}
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3 hover:text-blue-900 transition">
                        <a href="{{ route('articles.show', $article) }}">
                            {{ $article->title }}
                        </a>
                    </h3>
                    <p class="text-gray-600 mb-4">
                        {{ Str::limit(strip_tags((string) $article->excerpt), 120) }}
                    </p>
                    <div class="mb-3 flex items-center gap-3 text-xs text-gray-500">
                        <span>👁 {{ $article->views_count }}</span>
                        <span>💬 {{ $article->approved_comments_count }}</span>
                        <span>❤️ {{ $article->likes_count }}</span>
                    </div>
                    <a href="{{ route('articles.show', $article) }}" class="text-blue-900 hover:text-blue-700 font-medium">
                        Czytaj więcej →
                    </a>
                </div>
            </article>
        @endforeach
    </div>
</section>
@endif

<!-- Upcoming Games -->
@if($upcoming_games->count() > 0)
<section class="bg-gray-100 py-16">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Najbliższe Mecze</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($upcoming_games as $game)
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="text-sm text-gray-500 mb-3">
                        {{ $game->game_date?->format('d.m.Y H:i') }}
                    </div>

                    <div class="flex items-center justify-between mb-4">
                        <div class="text-center flex-1">
                            <div class="font-bold text-lg text-gray-900">{{ $game->is_home ? $game->team->name : $game->opponent }}</div>
                            <div class="text-sm text-gray-500">Gospodarze</div>
                        </div>

                        <div class="text-2xl font-bold text-gray-400 px-4">vs</div>

                        <div class="text-center flex-1">
                            <div class="font-bold text-lg text-gray-900">{{ $game->is_home ? $game->opponent : $game->team->name }}</div>
                            <div class="text-sm text-gray-500">Gość</div>
                        </div>
                    </div>

                    @if($game->venue)
                        <div class="text-sm text-gray-600 text-center">
                            📍 {{ $game->venue }}
                        </div>
                    @endif

                    @if($game->competition)
                        <div class="mt-2 text-xs text-center text-blue-700 font-semibold">{{ $game->competition->name }}</div>
                    @endif

                    <div class="mt-3 flex justify-center">
                        @livewire('like-button', ['type' => 'game', 'id' => $game->id], key('home-game-like-'.$game->id))
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Teams Overview -->
@if($teams->count() > 0)
<section class="container mx-auto px-4 py-16">
    <h2 class="text-3xl font-bold text-gray-900 mb-8">Nasze Drużyny</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($teams as $team)
            <div class="bg-white rounded-lg shadow-md p-6 text-center hover:shadow-xl transition group">
                <a href="{{ route('teams.show', $team) }}">
                    <x-placeholder-image :label="$team->name" height="h-20" class="w-20 rounded-full mx-auto mb-4" />
                    <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-blue-900 transition">
                        {{ $team->name }}
                    </h3>
                </a>
                @if($team->allTrainers()->isNotEmpty())
                    <p class="text-sm text-gray-600">
                        Trener:
                        @foreach($team->allTrainers() as $trainer)
                            <a href="{{ route('trainers.show', $trainer) }}" class="text-blue-700 hover:underline">{{ $trainer->name }}</a>@if(! $loop->last), @endif
                        @endforeach
                    </p>
                @endif
                <div class="mt-3 flex justify-center">
                    @livewire('like-button', ['type' => 'team', 'id' => $team->id], key('home-team-like-'.$team->id))
                </div>
            </div>
        @endforeach
    </div>
</section>
@endif

<!-- Latest News -->
@if($latest_articles->count() > 0)
<section class="bg-gray-100 py-16">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Ostatnie Aktualności</h2>
            <a href="{{ route('articles.index') }}" class="text-blue-900 hover:text-blue-700 font-medium">
                Wszystkie aktualności →
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($latest_articles->take(6) as $article)
                <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition">
                    @if($article->featured_image)
                        <img src="{{ Storage::url($article->featured_image) }}" alt="{{ $article->title }}" class="w-full h-48 object-cover">
                    @else
                        <x-placeholder-image label="Aktualność" height="h-48" class="rounded-none" />
                    @endif

                    <div class="p-6">
                        <div class="text-sm text-gray-500 mb-2">
                            {{ $article->published_at?->format('d.m.Y') }}
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2 hover:text-blue-900 transition">
                            <a href="{{ route('articles.show', $article) }}">
                                {{ Str::limit($article->title, 60) }}
                            </a>
                        </h3>
                        <p class="text-gray-600 text-sm">
                            {{ Str::limit(strip_tags((string) $article->excerpt), 100) }}
                        </p>
                        <div class="mt-3 flex items-center gap-3 text-xs text-gray-500">
                            <span>👁 {{ $article->views_count }}</span>
                            <span>💬 {{ $article->approved_comments_count }}</span>
                            <span>❤️ {{ $article->likes_count }}</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Popularity Stats -->
@if($popularPlayer || $popularGame)
<section class="container mx-auto px-4 py-12">
    <h2 class="text-3xl font-bold text-gray-900 mb-6">Najpopularniejsze na stronie</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="rounded-xl bg-white p-6 shadow">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Najpopularniejszy zawodnik</h3>
            @if($popularPlayer)
                <a href="{{ route('players.show', $popularPlayer) }}" class="text-blue-900 font-bold hover:underline">{{ $popularPlayer->full_name }}</a>
                <div class="mt-2 text-sm text-gray-600">❤️ {{ $popularPlayer->likes_count }} polubień</div>
            @else
                <p class="text-sm text-gray-500">Brak danych</p>
            @endif
        </div>

        <div class="rounded-xl bg-white p-6 shadow">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Najpopularniejszy mecz</h3>
            @if($popularGame)
                <div class="font-bold text-blue-900">{{ $popularGame->team?->name }} vs {{ $popularGame->opponent }}</div>
                <div class="mt-1 text-xs text-gray-600">{{ $popularGame->competition?->name }}</div>
                <div class="mt-2 text-sm text-gray-600">❤️ {{ $popularGame->likes_count }} polubień</div>
            @else
                <p class="text-sm text-gray-500">Brak danych</p>
            @endif
        </div>
    </div>
</section>
@endif

<!-- Sponsors -->
@if($sponsors->count() > 0)
<section class="container mx-auto px-4 py-16">
    <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Nasi Partnerzy i Sponsorzy</h2>

    <div class="flex flex-wrap justify-center items-center gap-8">
        @foreach($sponsors as $sponsor)
            @if($sponsor->website_url)
                <a href="{{ $sponsor->website_url }}" target="_blank" rel="noopener" class="grayscale hover:grayscale-0 transition opacity-70 hover:opacity-100">
                    @if($sponsor->logo_path)
                        <img src="{{ Storage::url($sponsor->logo_path) }}" alt="{{ $sponsor->name }}" class="h-16 object-contain">
                    @else
                        <div class="px-6 py-3 bg-gray-200 rounded font-semibold text-gray-700">
                            {{ $sponsor->name }}
                        </div>
                    @endif
                </a>
            @else
                <div class="grayscale opacity-70">
                    @if($sponsor->logo_path)
                        <img src="{{ Storage::url($sponsor->logo_path) }}" alt="{{ $sponsor->name }}" class="h-16 object-contain">
                    @else
                        <div class="px-6 py-3 bg-gray-200 rounded font-semibold text-gray-700">
                            {{ $sponsor->name }}
                        </div>
                    @endif
                </div>
            @endif
        @endforeach
    </div>
</section>
@endif
@endsection

@if($heroSlides->count() > 1)
    <script>
        const initHomeHeroSlider = () => {
            const heroSlider = document.getElementById('home-hero-slider');

            if (!heroSlider) {
                return;
            }

            if (heroSlider.dataset.initialized === 'true') {
                return;
            }

            heroSlider.dataset.initialized = 'true';

            const slides = Array.from(heroSlider.querySelectorAll('.hero-slide'));

            if (slides.length <= 1) {
                return;
            }

            const dots = Array.from(heroSlider.querySelectorAll('.hero-dot'));
            const prevButton = document.getElementById('home-hero-prev');
            const nextButton = document.getElementById('home-hero-next');
            let activeIndex = 0;
            let autoplayInterval = null;
            let touchStartX = null;
            let touchEndX = null;

            const showSlide = (index) => {
                activeIndex = (index + slides.length) % slides.length;

                slides.forEach((slide, slideIndex) => {
                    const isActive = slideIndex === activeIndex;
                    slide.classList.toggle('opacity-100', isActive);
                    slide.classList.toggle('opacity-0', !isActive);
                    slide.classList.toggle('pointer-events-none', !isActive);
                    slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                });

                dots.forEach((dot, dotIndex) => {
                    const isActive = dotIndex === activeIndex;
                    dot.classList.toggle('bg-white', isActive);
                    dot.classList.toggle('bg-white/30', !isActive);
                });
            };

            const startAutoplay = () => {
                window.clearInterval(autoplayInterval);
                autoplayInterval = window.setInterval(() => {
                    showSlide(activeIndex + 1);
                }, 5500);
            };

            const restartAutoplay = () => {
                startAutoplay();
            };

            prevButton?.addEventListener('click', () => {
                showSlide(activeIndex - 1);
                restartAutoplay();
            });

            nextButton?.addEventListener('click', () => {
                showSlide(activeIndex + 1);
                restartAutoplay();
            });

            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    showSlide(index);
                    restartAutoplay();
                });
            });

            heroSlider.addEventListener('mouseenter', () => window.clearInterval(autoplayInterval));
            heroSlider.addEventListener('mouseleave', startAutoplay);

            heroSlider.addEventListener('touchstart', (event) => {
                touchStartX = event.changedTouches[0]?.clientX ?? null;
            }, { passive: true });

            heroSlider.addEventListener('touchend', (event) => {
                touchEndX = event.changedTouches[0]?.clientX ?? null;

                if (touchStartX === null || touchEndX === null) {
                    return;
                }

                const delta = touchStartX - touchEndX;

                if (Math.abs(delta) < 40) {
                    return;
                }

                if (delta > 0) {
                    showSlide(activeIndex + 1);
                } else {
                    showSlide(activeIndex - 1);
                }

                restartAutoplay();
            }, { passive: true });

            showSlide(0);
            startAutoplay();
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initHomeHeroSlider, { once: true });
        } else {
            initHomeHeroSlider();
        }

        document.addEventListener('livewire:navigated', initHomeHeroSlider);
    </script>
@endif

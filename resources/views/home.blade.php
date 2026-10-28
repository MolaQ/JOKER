@extends('layouts.public')


@php
    use App\Support\Media;

    $fallbackHero = Media::photo('hero-team');
    $slides = $heroSlides->isNotEmpty()
        ? $heroSlides->map(fn ($slide) => [
            'image' => Media::url($slide->image_path),
            'title' => $slide->title,
            'subtitle' => $slide->subtitle,
            'cta_label' => $slide->cta_label,
            'cta_url' => $slide->cta_url,
        ])
        : collect([[
            'image' => $site->image('hero_image', $fallbackHero['url']),
            'title' => __($site['hero_title']),
            'subtitle' => __($site['hero_subtitle']),
            'cta_label' => __($site['hero_button_label']),
            'cta_url' => $site->href((string) $site['hero_button_url']),
        ]]);
    $nextGame = $countdownGame;
@endphp

@push('head')
    @if($slides->first()['image'])
        <link rel="preload" as="image" href="{{ Media::sized($slides->first()['image'], 1200) }}" @if(Media::srcset($slides->first()['image'])) imagesrcset="{{ Media::srcset($slides->first()['image']) }}" imagesizes="100vw" @endif fetchpriority="high">
    @endif
@endpush

@push('structured-data')
    {!! \App\Support\Seo::jsonLd(\App\Support\Seo::website()) !!}
@endpush

@section('content')
{{-- HERO --}}
<section data-hero-slider class="relative isolate -mt-16 overflow-hidden bg-ink text-white lg:-mt-20">
    <div class="relative min-h-[80svh] lg:min-h-[92svh]">
    @foreach($slides as $slide)
        <div data-slide aria-hidden="{{ $loop->first ? 'false' : 'true' }}" class="absolute inset-0 transition-opacity duration-1000 {{ $loop->first ? 'opacity-100' : 'opacity-0' }}">
            <x-photo :src="$slide['image']" :alt="$slide['title']" :eager="$loop->first" width="1800" height="1200" class="absolute inset-0 h-full w-full scale-105" />
            <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/60 to-ink/20"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-ink/80 via-transparent to-transparent"></div>

            <div class="container-site relative flex min-h-[80svh] flex-col justify-end pt-32 pb-12 lg:min-h-[92svh] lg:pb-48">
                <p class="eyebrow">{{ $site['club_name'] }} · {{ __('Sezon') }} {{ $seasonName ?? date('Y') }}</p>
                <h1 class="heading-xl mt-4 max-w-4xl text-balance">{{ $slide['title'] }}</h1>
                @if($slide['subtitle'])
                    <p class="mt-6 max-w-xl text-lg text-zinc-300">{{ $slide['subtitle'] }}</p>
                @endif
                <div class="mt-8 flex flex-wrap gap-3">
                    @if($slide['cta_label'] && $slide['cta_url'])
                        <a href="{{ $slide['cta_url'] }}" class="btn-primary">{{ $slide['cta_label'] }} <span aria-hidden="true">→</span></a>
                    @endif
                    @if(filled($site['hero_second_button_label']) && filled($site['hero_second_button_url']))
                        <a href="{{ $site->href((string) $site['hero_second_button_url']) }}" class="btn-ghost">{{ __($site['hero_second_button_label']) }}</a>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    @if($slides->count() > 1)
        <div class="absolute bottom-56 left-1/2 z-10 hidden -translate-x-1/2 sm:flex gap-2 sm:bottom-64 lg:bottom-36">
            @foreach($slides as $slide)
                <button type="button" data-dot class="{{ $loop->first ? 'is-active' : '' }} h-1.5 w-8 rounded-full bg-white/30 transition-all [&.is-active]:w-14 [&.is-active]:bg-sky-club" aria-label="{{ __('Slajd') }} {{ $loop->iteration }}"></button>
            @endforeach
        </div>
    @endif

    </div>

    {{-- Najbliższy mecz + liczby klubu (na telefonie pod zdjęciem, na komputerze na zdjęciu) --}}
    <div class="relative z-10 lg:absolute lg:inset-x-0 lg:bottom-0">
        <div class="container-site">
            <div class="grid gap-px overflow-hidden rounded-t-3xl bg-white/10 backdrop-blur-xl sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-ink/70 p-5 sm:col-span-2 lg:p-6">
                    @if($nextGame)
                        <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-4">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold tracking-widest text-sky-club uppercase">{{ __('Najbliższy mecz') }} · {{ $nextGame->kickoffLabel() }}</p>
                                <p class="mt-2 font-display text-xl font-semibold uppercase lg:text-2xl">
                                    {{ $nextGame->team?->name }} <span class="text-zinc-500">{{ __('vs') }}</span> {{ $nextGame->opponentShortName() }}
                                </p>
                                <a href="{{ route('games.calendar', $nextGame) }}" class="mt-1 inline-flex min-h-9 items-center gap-1.5 text-xs font-semibold text-sky-club-light">+ {{ __('Dodaj do kalendarza') }}</a>
                            </div>
                            @if($site['home_show_countdown'])
                                <div>
                                    <div data-countdown="{{ $nextGame->game_date?->toIso8601String() }}" role="timer" aria-hidden="true"></div>
                                    <p data-countdown-text class="sr-only" aria-live="off"></p>
                                </div>
                            @endif
                        </div>
                    @else
                        <p class="text-xs font-semibold tracking-widest text-sky-club uppercase">{{ __('Terminarz') }}</p>
                        <p class="mt-2 font-display text-xl font-semibold uppercase">{{ __('Nowe mecze już wkrótce') }}</p>
                    @endif
                </div>
                @if($site['home_show_stats'])
                    <div class="hidden items-center gap-6 bg-ink/70 p-5 sm:flex lg:p-6">
                        <p><span class="block font-display text-4xl font-bold">{{ $stats['teams'] }}</span><span class="text-xs text-zinc-400 uppercase">{{ __('drużyn') }}</span></p>
                        <p><span class="block font-display text-4xl font-bold">{{ $stats['players'] }}</span><span class="text-xs text-zinc-400 uppercase">{{ __('zawodników') }}</span></p>
                    </div>
                @endif
                <a href="{{ $site->href((string) $site['home_tile_url']) }}" @class(['group flex items-center justify-between gap-4 bg-sky-club p-5 transition hover:bg-sky-club-dark lg:p-6', 'lg:col-span-2' => ! $site['home_show_stats']])>
                    <span class="max-w-40 font-display text-xl leading-tight font-semibold uppercase">{{ __($site['home_tile_label']) }}</span>
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-sky-club-dark transition group-hover:translate-x-1" aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </div>
</section>

{{-- DRUŻYNY --}}
@if($site['home_teams_enabled'])
<section class="container-site py-20 lg:py-28" data-reveal>
    <div class="flex flex-wrap items-end justify-between gap-6">
        <div>
            <p class="eyebrow">{{ __($site['home_teams_eyebrow']) }}</p>
            <h2 class="heading-lg mt-3 max-w-xl text-balance">{{ __($site['home_teams_title']) }}</h2>
        </div>
        <a href="{{ route('teams.index') }}" class="link-underline pb-1 text-sm font-semibold">{{ __('Wszystkie drużyny') }} →</a>
    </div>

    <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($teams as $team)
            <a href="{{ route('teams.show', $team) }}" @class([
                'group relative isolate flex min-h-72 flex-col justify-end overflow-hidden rounded-3xl p-6 text-white',
                'sm:col-span-2 lg:min-h-80' => $loop->first,
            ])>
                <x-photo :src="Media::teamPhoto($team)" :alt="$team->name" sizes="(min-width: 1024px) 50vw, 100vw" class="absolute inset-0 -z-10 h-full w-full transition duration-700 group-hover:scale-105" />
                <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink via-ink/40 to-transparent"></div>
                <span class="chip absolute top-5 left-5 bg-white/15 text-white backdrop-blur">{{ $team->active_players_count }} {{ __('zawodników') }}</span>
                <p class="text-xs font-semibold tracking-widest text-sky-club-light uppercase">{{ $team->birth_year ? __('Rocznik :year', ['year' => $team->birth_year]) : __('Pierwsza drużyna') }}</p>
                <h3 @class(['mt-1 font-display font-bold uppercase', 'text-5xl' => $loop->first, 'text-2xl' => ! $loop->first])>{{ $team->name }}</h3>
                <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold opacity-0 transition group-hover:opacity-100">{{ __('Skład i sztab') }} <span aria-hidden="true">→</span></span>
            </a>
        @endforeach
    </div>
</section>
@endif

{{-- TERMINARZ I WYNIKI --}}
@if($site['home_games_enabled'] && ($upcoming_games->isNotEmpty() || $recent_results->isNotEmpty()))
<section class="relative overflow-hidden bg-ink py-20 text-white lg:py-28">
    <div class="net-pattern absolute inset-0" aria-hidden="true"></div>
    <div class="container-site relative" data-reveal>
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div>
                <p class="eyebrow">{{ __($site['home_games_eyebrow']) }}</p>
                <h2 class="heading-lg mt-3">{{ __($site['home_games_title']) }}</h2>
            </div>
            <a href="{{ route('schedule.index') }}" class="btn-ghost">{{ __('Pełny terminarz') }}</a>
        </div>

        @if($upcoming_games->isNotEmpty())
            <div class="scrollbar-none -mx-4 mt-10 flex snap-x gap-4 overflow-x-auto px-4 pb-2 sm:mx-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:px-0 lg:grid-cols-3">
                @foreach($upcoming_games as $game)
                    <x-match-card :game="$game" dark class="w-[85%] shrink-0 snap-start sm:w-auto" />
                @endforeach
            </div>
        @endif

        @if($recent_results->isNotEmpty())
            <h3 class="heading-md mt-14 text-zinc-300">{{ __('Ostatnie wyniki') }}</h3>
            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($recent_results as $game)
                    <x-match-card :game="$game" dark />
                @endforeach
            </div>
        @endif
    </div>
</section>
@endif

{{-- SKŁAD DNIA --}}
@if($lineup && collect($lineup->players)->pluck('player_id')->filter()->isNotEmpty())
@php
    $lineupPeople = collect($lineup->lineup())->filter(fn ($slot) => $slot['player'])
        ->map(fn ($slot) => ['name' => $slot['player']->full_name, 'role' => $slot['position']->label().($slot['player']->team ? ' · '.$slot['player']->team->name : ''), 'url' => route('players.show', $slot['player']), 'badge' => $slot['player']->jersey_number ?? mb_substr($slot['player']->first_name, 0, 1).mb_substr((string) $slot['player']->last_name, 0, 1)]);
    if ($lineup->coach) {
        $lineupPeople->push(['name' => $lineup->coach->name, 'role' => __('Trener'), 'url' => route('trainers.show', $lineup->coach), 'badge' => collect(explode(' ', $lineup->coach->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('')]);
    }
    $lineupFile = 'sklad-dnia-'.$lineup->date->toDateString().'.png';
@endphp
<section class="container-site py-16 lg:py-24" data-reveal>
    <div class="grid items-center gap-10 lg:grid-cols-[minmax(0,26rem)_1fr]">
        <x-daily-lineup-graphic :lineup="$lineup" class="rounded-3xl shadow-2xl shadow-ink/30 ring-1 ring-black/5" />
        <div>
            <p class="eyebrow">{{ __('Codziennie nowy') }} · {{ $lineup->date->format('d.m.Y') }}</p>
            <h2 class="heading-lg mt-3">{{ __('Skład dnia') }}</h2>
            <p class="mt-4 max-w-xl text-zinc-600">{{ __('Sześciu zawodników i trener ze wszystkich roczników, losowani codziennie. Każde serduszko na profilu zwiększa szansę na miejsce w składzie.') }}</p>
            <ul class="mt-6 grid gap-x-6 gap-y-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($lineupPeople as $person)
                    <li>
                        <a href="{{ $person['url'] }}" class="group flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-ink font-display text-sm font-bold text-sky-club">{{ $person['badge'] }}</span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold group-hover:text-sky-club-dark">{{ $person['name'] }}</span>
                                <span class="block truncate text-xs text-zinc-500">{{ $person['role'] }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-7 flex flex-wrap gap-3">
                <button type="button" data-daily-lineup="download" data-file-name="{{ $lineupFile }}" data-busy-label="{{ __('Przygotowuję…') }}" data-error-message="{{ __('Nie udało się przygotować obrazka. Spróbuj ponownie.') }}" class="btn-primary">{{ __('Pobierz obrazek') }}</button>
                <button type="button" data-daily-lineup="share" data-file-name="{{ $lineupFile }}" data-share-title="{{ __('Skład dnia') }}" data-busy-label="{{ __('Przygotowuję…') }}" data-error-message="{{ __('Nie udało się przygotować obrazka. Spróbuj ponownie.') }}" class="inline-flex items-center justify-center rounded-full px-6 py-3 text-sm font-semibold text-zinc-900 ring-1 ring-zinc-300 transition hover:bg-zinc-100">{{ __('Udostępnij') }}</button>
            </div>
        </div>
    </div>
</section>
@endif

{{-- AKTUALNOŚCI --}}
@if($site['home_news_enabled'] && $articles->isNotEmpty())
<section class="container-site py-20 lg:py-28" data-reveal>
    <div class="flex flex-wrap items-end justify-between gap-6">
        <div>
            <p class="eyebrow">{{ __($site['home_news_eyebrow']) }}</p>
            <h2 class="heading-lg mt-3">{{ __($site['home_news_title']) }}</h2>
        </div>
        <a href="{{ route('articles.index') }}" class="link-underline pb-1 text-sm font-semibold">{{ __('Wszystkie wpisy') }} →</a>
    </div>

    <div class="mt-10 grid gap-6 lg:grid-cols-2">
        @php $lead = $articles->first(); @endphp
        <a href="{{ route('articles.show', $lead) }}" class="group relative isolate flex min-h-[28rem] flex-col justify-end overflow-hidden rounded-3xl p-8 text-white">
            <x-photo :src="Media::articleImage($lead)" :focus="$lead->imageFocus()" :alt="$lead->localizedTitle()" sizes="(min-width: 1024px) 50vw, 100vw" class="absolute inset-0 -z-10 h-full w-full transition duration-700 group-hover:scale-105" />
            <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink via-ink/50 to-transparent"></div>
            <p class="text-xs font-semibold tracking-widest text-sky-club-light uppercase">{{ $lead->published_at?->translatedFormat('d F Y') }}</p>
            <h3 class="mt-3 font-display text-3xl leading-tight font-bold uppercase sm:text-4xl">{{ $lead->localizedTitle() }}@if($lead->hasEnglish()) <x-english-badge class="ml-1" />@endif</h3>
            <p class="mt-3 line-clamp-2 max-w-xl text-zinc-300">{{ Str::limit(strip_tags((string) $lead->localizedExcerpt()), 160) }}</p>
        </a>

        <div class="grid gap-4">
            @foreach($articles->skip(1) as $article)
                <a href="{{ route('articles.show', $article) }}" class="card card-hover group flex gap-5 p-3">
                    <x-photo :src="Media::articleImage($article)" :focus="$article->imageFocus()" :alt="$article->localizedTitle()" sizes="160px" width="320" height="240" class="aspect-[4/3] w-32 shrink-0 rounded-2xl sm:w-40" />
                    <div class="flex flex-col justify-center py-2 pr-2">
                        <p class="text-xs font-semibold text-zinc-500">{{ $article->published_at?->translatedFormat('d F Y') }}</p>
                        <h3 class="mt-1 line-clamp-2 font-semibold text-zinc-900 group-hover:text-sky-club-dark">{{ $article->localizedTitle() }}@if($article->hasEnglish()) <x-english-badge class="ml-1" />@endif</h3>
                        <p class="mt-1 hidden line-clamp-2 text-sm text-zinc-500 sm:block">{{ Str::limit(strip_tags((string) $article->localizedExcerpt()), 110) }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- SPONSORZY --}}
@if($site['home_sponsors_enabled'] && $sponsors->isNotEmpty())
<section class="border-y border-zinc-200 bg-white py-14">
    <div class="container-site">
        <p class="eyebrow text-center text-zinc-400">{{ __($site['home_sponsors_title']) }}</p>
        @php
            // Wielkość logo według poziomu: najważniejszy sponsor największy i w kolorze, mniejsi szarzy do najechania.
            $tierStyles = [
                'platinum' => ['logo' => 'h-20 sm:h-28 max-w-[18rem] sm:max-w-[22rem]', 'text' => 'text-3xl sm:text-4xl', 'gap' => 'gap-x-10 gap-y-6 sm:gap-x-16', 'muted' => false],
                'gold' => ['logo' => 'h-12 sm:h-16 max-w-[10rem] sm:max-w-[13rem]', 'text' => 'text-2xl', 'gap' => 'gap-x-8 gap-y-5 sm:gap-x-12', 'muted' => false],
                'silver' => ['logo' => 'h-11 sm:h-12 max-w-[10rem]', 'text' => 'text-xl', 'gap' => 'gap-x-8 gap-y-4 sm:gap-x-10', 'muted' => true],
                'bronze' => ['logo' => 'h-9 max-w-[8rem]', 'text' => 'text-base', 'gap' => 'gap-x-6 gap-y-4 sm:gap-x-8', 'muted' => true],
            ];
            $sponsorRows = $sponsors->groupBy(fn ($sponsor) => isset($tierStyles[$sponsor->tier]) ? $sponsor->tier : 'bronze')
                ->sortBy(fn ($group, $tier) => array_search($tier, array_keys($tierStyles), true));
        @endphp
        <div class="mt-6 space-y-7">
            @foreach($sponsorRows as $tier => $tierSponsors)
                @php($style = $tierStyles[$tier])
                <div @class(['border-t border-zinc-100 pt-7' => ! $loop->first])>
                    @if($sponsorRows->count() > 1)
                        <p class="mb-4 text-center text-xs font-semibold tracking-[0.2em] text-zinc-400 uppercase">{{ __($tier === 'platinum' && $tierSponsors->count() > 1 ? 'Sponsorzy główni' : \App\Models\Sponsor::TIERS[$tier]['label']) }}</p>
                    @endif
                    <div class="flex flex-wrap items-center justify-center {{ $style['gap'] }}">
                        @foreach($tierSponsors as $sponsor)
                            <a href="{{ $sponsor->website_url ?: '#' }}" @if($sponsor->website_url) target="_blank" rel="noopener" @endif @class(['transition', 'opacity-60 grayscale hover:opacity-100 hover:grayscale-0' => $style['muted'], 'hover:opacity-80' => ! $style['muted']]) title="{{ $sponsor->name }}">
                                @if($sponsor->logo_path)
                                    <img src="{{ Media::url($sponsor->logo_path) }}" alt="{{ $sponsor->name }}" loading="lazy" decoding="async" class="{{ $style['logo'] }} w-auto object-contain">
                                @else
                                    <span class="font-display {{ $style['text'] }} font-bold tracking-wide text-zinc-700 uppercase">{{ $sponsor->name }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- DOŁĄCZ --}}
@if($alumni->isNotEmpty())
    <section class="container-site py-16 lg:py-20" data-reveal>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">{{ __('Od Jokera do PlusLigi') }}</p>
                <h2 class="heading-lg mt-3">{{ __('Nasi wychowankowie') }}</h2>
            </div>
            <a href="{{ route('alumni.index') }}" class="text-sm font-semibold text-sky-club-dark hover:underline">{{ __('Wszyscy wychowankowie') }} →</a>
        </div>
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($alumni as $alumnus)
                <a href="{{ route('alumni.index') }}" class="card card-hover flex items-center gap-4 p-4">
                    @if($alumnus->photoUrl())
                        <img src="{{ $alumnus->photoUrl() }}" alt="" loading="lazy" class="h-16 w-16 shrink-0 rounded-2xl object-cover object-top">
                    @else
                        <img src="{{ $site->image('logo', asset('img/logo-96.png')) }}" alt="" loading="lazy" class="h-16 w-16 shrink-0 object-contain opacity-60">
                    @endif
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $alumnus->name }}</p>
                        <p class="truncate text-sm text-zinc-500">{{ collect([$alumnus->level, $alumnus->current_club])->filter()->implode(' · ') }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif

@if($site['home_join_enabled'])
<section class="container-site pt-20 lg:pt-28" data-reveal>
    <div class="relative isolate overflow-hidden rounded-[2rem] bg-sky-club px-8 py-14 text-white sm:px-14 lg:py-20">
        <x-photo :src="$site->image('home_join_image', Media::photo('ball-court')['url'])" alt="" sizes="100vw" class="absolute inset-0 -z-20 h-full w-full opacity-30 mix-blend-multiply" />
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-sky-club-dark via-sky-club-dark/80 to-transparent"></div>
        <p class="font-display text-sm font-semibold tracking-[0.25em] uppercase opacity-80">{{ __($site['home_join_eyebrow']) }}</p>
        <h2 class="heading-lg mt-3 max-w-2xl">{{ __($site['home_join_title']) }}</h2>
        <p class="mt-4 max-w-xl text-lg text-white/85">{{ __($site['home_join_text']) }}</p>
        @if(filled($site['home_join_button_label']))
            <a href="{{ $site->href((string) $site['home_join_button_url']) }}" class="btn mt-8 bg-white text-sky-club-dark hover:-translate-y-0.5 hover:bg-zinc-100">{{ __($site['home_join_button_label']) }} <span aria-hidden="true">→</span></a>
        @endif
    </div>
</section>
@endif
@endsection

@extends('layouts.public')

@section('title', $player->displayName())
@section('description', $player->seoDescription($site['club_name']))

@if($player->photoUrl())
    @section('og_image', $player->photoUrl())
@endif
@push('structured-data')
    {!! \App\Support\Seo::jsonLd(\App\Support\Seo::player($player)) !!}
@endpush

@section('content')
@php
    $age = $player->age();
    $stats = array_filter([
        'Wiek' => $age ? \App\Livewire\GamesBrowser::yearsLabel($age) : null,
        'Wzrost' => $player->height ? $player->height.' cm' : null,
        'Waga' => $player->weight ? $player->weight.' kg' : null,
        'Zasięg' => $player->reach ? $player->reach.' cm' : null,
        'Zasięg w ataku' => $player->spike_reach ? $player->spike_reach.' cm' : null,
        'Zasięg w bloku' => $player->block_reach ? $player->block_reach.' cm' : null,
    ]);
@endphp
<section class="relative isolate -mt-16 overflow-hidden bg-ink pt-16 text-white lg:-mt-20 lg:pt-20">
    <div class="net-pattern absolute inset-0 -z-10" aria-hidden="true"></div>
    <span class="pointer-events-none absolute -right-8 bottom-0 -z-10 font-display text-[16rem] leading-none font-bold text-white/5 select-none sm:text-[24rem]" aria-hidden="true">{{ $player->jersey_number }}</span>

    <div class="container-site grid items-end gap-10 pt-16 pb-14 lg:grid-cols-[auto_1fr] lg:pt-24">
        <x-avatar :photo="$player->publicPhotoPath()" :name="$player->displayName()" :label="$player->jersey_number" class="aspect-[4/5] w-48 rounded-3xl text-7xl ring-4 ring-sky-club/40 lg:w-64" />
        <div>
            @if($player->team)
                <a href="{{ route('teams.show', $player->team) }}" class="text-sm text-zinc-400 transition hover:text-white">← {{ $player->team->name }}</a>
            @elseif($player->isMini() && $player->miniGroup)
                <a href="{{ route('mini.show', $player->miniGroup) }}" class="text-sm text-zinc-400 transition hover:text-white">← {{ __('Minisiatkówka') }}: {{ $player->miniGroup->name }}</a>
            @endif
            <p class="eyebrow mt-5">@foreach($player->positions() as $playerPosition)<x-position-tip :position="$playerPosition" />@if(! $loop->last) · @endif @endforeach @if($player->jersey_number) · #{{ $player->jersey_number }} @endif</p>
            <h1 class="heading-xl mt-3">@if($player->isMini()){{ $player->displayName() }}@else{{ $player->first_name }}<br>{{ $player->last_name }}@endif <x-message-button :player="$player" class="ml-2" /></h1>
            @if($player->isCaptain())
                <p class="mt-4 inline-flex items-center gap-2 rounded-full bg-sky-club px-3 py-1 text-sm font-bold text-white"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-white text-xs text-sky-club-dark">C</span> {{ __('Kapitan drużyny') }}</p>
            @endif
            @if($socialProfiles = $player->socialProfiles())
                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach($socialProfiles as $profile)
                        <a href="{{ $profile['url'] }}" target="_blank" rel="noopener nofollow" class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 transition hover:bg-sky-club" aria-label="{{ $profile['label'] }}" title="{{ $profile['label'] }}"><x-social-icon :network="$profile['icon']" class="h-4 w-4" /></a>
                    @endforeach
                </div>
            @endif

            @if($stats)
                <dl class="mt-8 grid max-w-3xl grid-cols-2 gap-px overflow-hidden rounded-2xl bg-white/10 ring-1 ring-white/10 {{ [1 => 'sm:grid-cols-1', 2 => 'sm:grid-cols-2', 3 => 'sm:grid-cols-3', 4 => 'sm:grid-cols-4', 5 => 'sm:grid-cols-5', 6 => 'sm:grid-cols-3'][count($stats)] }}">
                    @foreach($stats as $label => $value)
                        <div class="bg-ink/80 px-4 py-3">
                            <dt class="text-[11px] font-semibold tracking-widest text-zinc-400 uppercase">{{ __($label) }}</dt>
                            <dd class="mt-1 font-display text-2xl font-bold">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif

            <div class="mt-6 flex flex-wrap items-center gap-4">
                <x-view-count :model="$player" class="text-white/70" />
                @livewire('like-button', ['type' => 'player', 'id' => $player->id, 'dark' => true], key('player-like-'.$player->id))
            </div>
        </div>
    </div>
</section>

<section class="container-site grid gap-8 py-14 lg:grid-cols-3">
    <div class="card min-w-0 p-6 lg:sticky lg:top-28 lg:self-start">
        <h2 class="heading-md">{{ __('Informacje') }}</h2>
        <dl class="mt-5 divide-y divide-zinc-100 text-sm">
            @foreach([
                'Data urodzenia' => $player->birth_date?->format('d.m.Y'),
                'Rocznik' => $player->birth_date ? null : $player->birth_year,
                'Grupa' => $player->isMini() ? collect([$player->miniGroup?->name])->concat($player->extraMiniGroups->pluck('name'))->filter()->implode(' + ') : null,
                (count($player->positions()) > 1 ? 'Pozycje' : 'Pozycja') => $player->positionsLabel(),
                'Numer' => $player->jersey_number !== null ? '#'.$player->jersey_number : null,
                'Wzrost' => $player->height ? $player->height.' cm' : null,
                'Waga' => $player->weight ? $player->weight.' kg' : null,
                'Zasięg' => $player->reach ? $player->reach.' cm' : null,
                'Zasięg w ataku' => $player->spike_reach ? $player->spike_reach.' cm' : null,
                'Zasięg w bloku' => $player->block_reach ? $player->block_reach.' cm' : null,
            ] as $label => $value)
                @if($value)
                    <div class="flex items-center justify-between py-3">
                        <dt class="text-zinc-500">{{ __($label) }}</dt>
                        <dd class="font-semibold">
                            @if(in_array($label, ['Pozycja', 'Pozycje'], true))
                                @foreach($player->positions() as $playerPosition)<x-position-tip :position="$playerPosition" align="right" />@if(! $loop->last) · @endif @endforeach
                            @else
                                {{ $value }}
                            @endif
                        </dd>
                    </div>
                @endif
            @endforeach
        </dl>

        @if($seasonTeams->filter()->isNotEmpty())
            <h3 class="mt-6 text-xs font-semibold tracking-widest text-zinc-500 uppercase">{{ __('Drużyny w tym sezonie') }}</h3>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($seasonTeams->filter() as $seasonTeam)
                    <a href="{{ route('teams.show', $seasonTeam) }}" class="chip hover:bg-sky-club hover:text-white">{{ $seasonTeam->name }}</a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="min-w-0 space-y-8 lg:col-span-2">
        @livewire('player-proposal-form', ['player' => $player], key('player-proposal-'.$player->id))

        @php
            // Nagrody indywidualne (z turniejów na stronie i wpisane w panelu zawodnika) osobno, na górze; reszta osiągnięć niżej jak dotąd.
            $individualAwards = $player->individualAwards();
            $otherAchievements = $player->achievements->filter(fn ($achievement) => ! $achievement->tournamentAward?->category?->tournament);
        @endphp
        @if($individualAwards->isNotEmpty())
            <div class="overflow-hidden rounded-3xl bg-ink text-white ring-1 ring-white/10" data-theme-keep>
                <div class="flex items-center gap-3 px-6 pt-6">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-amber-300 to-amber-500 text-amber-950 shadow-lg shadow-amber-500/30" aria-hidden="true">
                        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8m-4-4v4M7 4h10v5a5 5 0 0 1-10 0V4Zm0 2H4v1a3 3 0 0 0 3 3m10-4h3v1a3 3 0 0 1-3 3"/></svg>
                    </span>
                    <h2 class="font-display text-2xl leading-none font-bold uppercase">{{ __('Nagrody indywidualne') }}</h2>
                </div>
                <ul class="mt-5 grid gap-px bg-white/10">
                    @foreach($individualAwards as $award)
                        <li class="relative bg-ink px-6 py-5">
                            <p class="font-display text-xl leading-tight font-bold text-amber-300 uppercase sm:text-2xl">{{ $award['name'] }}</p>
                            @if($award['event'])
                                <p class="mt-1 font-semibold">
                                    @if($award['url'])
                                        <a href="{{ $award['url'] }}" class="hover:text-sky-club-light hover:underline">{{ $award['event'] }}</a>
                                    @else
                                        {{ $award['event'] }}
                                    @endif
                                </p>
                            @endif
                            <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-zinc-400">
                                @if($award['date'])
                                    <span class="inline-flex items-center gap-1.5"><svg class="size-4 text-sky-club" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M16 3v4M8 3v4M3 10h18"/></svg>{{ $award['date']->translatedFormat('j F Y') }}</span>
                                @endif
                                @if($award['place'])
                                    <span class="inline-flex items-center gap-1.5"><svg class="size-4 text-sky-club" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/></svg>{{ $award['place'] }}</span>
                                @endif
                                @if($award['category'])
                                    <span class="inline-flex items-center gap-1.5"><svg class="size-4 text-sky-club" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h10"/></svg>{{ $award['category'] }}</span>
                                @endif
                                @if($award['team'])
                                    <span class="inline-flex items-center gap-1.5"><svg class="size-4 text-sky-club" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 19v-1a4 4 0 0 0-8 0v1M12 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>{{ $award['team'] }}</span>
                                @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($miniTrips->isNotEmpty())
            <div class="card p-6">
                <h2 class="heading-md">{{ __('Turnieje minisiatkówki') }}</h2>
                <ul class="mt-4 divide-y divide-zinc-100">
                    @foreach($miniTrips as $tripTeam)
                        <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 py-3 text-sm">
                            <span class="min-w-0">
                                <span class="block font-semibold text-zinc-900">{{ $tripTeam->trip->title }}</span>
                                <span class="text-zinc-500">{{ $tripTeam->trip->dateLabel() }}@if($tripTeam->trip->city) · {{ $tripTeam->trip->city }}@endif · {{ $tripTeam->name }}@if($tripTeam->group) ({{ $tripTeam->group->name }})@endif</span>
                            </span>
                            @if($result = $tripTeam->resultLabel())<span class="chip bg-sky-club/10 text-sky-club-dark">{{ $result }}</span>@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($career->isNotEmpty())
            <div class="card overflow-hidden">
                <h2 class="heading-md px-6 pt-6">{{ __('Kariera klubowa') }}</h2>
                <div class="mt-4 overflow-x-auto">
                    <table data-sortable class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 text-xs font-semibold tracking-wider text-zinc-500 uppercase">
                            <tr>
                                <th class="px-6 py-3">{{ __('Sezon') }}</th>
                                <th class="px-3 py-3">{{ __('Drużyna') }}</th>
                                <th class="px-3 py-3">{{ __('Rozgrywki') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Nr') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @foreach($career as $entry)
                                <tr>
                                    <td class="px-6 py-3 font-semibold whitespace-nowrap tabular-nums">{{ $entry['season']?->name ?? '—' }}</td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <a href="{{ route('teams.show', $entry['team']) }}" class="font-semibold text-zinc-900 hover:text-sky-club-dark">{{ $site['club_name'] }} · {{ $entry['team']->name }}</a>
                                        @if($entry['captain'])<span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800" title="{{ __('Kapitan') }}">C</span>@endif
                                    </td>
                                    <td class="px-3 py-3 text-zinc-600">{{ $entry['competitions']->join(', ') ?: '—' }}</td>
                                    <td class="px-6 py-3 text-right tabular-nums">{{ $entry['jersey'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($otherAchievements->isNotEmpty())
            <div class="card p-6">
                <h2 class="heading-md">{{ __('Osiągnięcia') }}</h2>
                <ul class="mt-5 space-y-3">
                    @foreach($otherAchievements as $achievement)
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600" aria-hidden="true">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8m-4-4v4M7 4h10v5a5 5 0 0 1-10 0V4Zm0 2H4v1a3 3 0 0 0 3 3m10-4h3v1a3 3 0 0 1-3 3"/></svg>
                            </span>
                            <div>
                                <p class="font-semibold text-zinc-900">{{ $achievement->title }}</p>
                                @if($achievement->season)<p class="text-xs text-zinc-500">{{ $achievement->season }}</p>@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($recentGames->isNotEmpty())
            <div class="card p-6">
                <h2 class="heading-md">{{ __('Ostatnie mecze') }}</h2>
                <ul class="mt-5 divide-y divide-zinc-100">
                    @foreach($recentGames as $game)
                        @php
                            $result = $game->ourSetsResult();
                            $outcome = $game->outcome();
                            $won = $outcome && $outcome !== 'derby' ? $outcome === 'win' : null;
                            $article = $game->latestArticle();
                        @endphp
                        <li class="flex items-center gap-3 py-3 text-sm">
                            @if($outcome === 'derby')
                                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sky-club/15"><x-derby-heart class="h-4 w-4 text-sky-club" /></span>
                            @elseif($won === true)
                                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-600" aria-label="{{ __('Wygrana') }}"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7"/></svg></span>
                            @elseif($won === false)
                                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-red-500/10 text-red-600" aria-label="{{ __('Porażka') }}"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg></span>
                            @endif
                            <span class="w-12 shrink-0 text-zinc-500 tabular-nums">{{ $game->game_date?->format('d.m') }}</span>
                            <span class="min-w-0 flex-1 truncate">
                                @if($game->is_home)
                                    <x-team-label :label="$game->ourLabel()" :badge="false" class="font-semibold text-zinc-900" />
                                    <span class="text-zinc-500">{{ __('vs') }} {{ $game->opponentShortName() }}</span>
                                @else
                                    <span class="font-semibold text-zinc-900">{{ $game->opponentShortName() }}</span>
                                    <span class="text-zinc-500">{{ __('vs') }}</span> <x-team-label :label="$game->ourLabel()" :badge="false" class="font-semibold text-zinc-900" />
                                @endif
                            </span>
                            @if($result)
                                <span class="font-display text-lg font-bold tabular-nums">{{ $game->home_score }}:{{ $game->away_score }}</span>
                            @endif
                            @if($article)
                                <a href="{{ route('articles.show', $article) }}" class="text-xs font-semibold text-sky-club-dark hover:underline">{{ __('Relacja') }}</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($player->bio || $player->interests)
            <div class="card p-8">
                <h2 class="heading-md">{{ __('O zawodniku') }}</h2>
                @if($player->bio)
                    <div class="prose-club mt-5">{!! strip_tags($player->bio, '<p><br><strong><em><b><i><u><s><mark><blockquote><hr><ul><ol><li>') !!}</div>
                @endif
                @if($player->interests)
                    <h3 class="mt-6 text-xs font-semibold tracking-widest text-zinc-500 uppercase">{{ __('Zainteresowania') }}</h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach(array_filter(array_map('trim', explode(',', $player->interests))) as $interest)
                            <span class="chip">{{ $interest }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        @if($mentions->isNotEmpty())
            <x-mentions-list :articles="$mentions" :name="$player->displayName()" />
        @endif
    </div>
</section>

@if($photos->isNotEmpty())
    <section id="zdjecia" class="container-site pb-16">
        <p class="eyebrow">{{ __('Galerie') }}</p>
        <h2 class="heading-lg mt-3">{{ __('Zdjęcia') }}</h2>
        <div class="mt-6 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
            @foreach($photos as $photo)
                <a href="{{ route('galleries.show', $photo->gallery) }}" class="group relative aspect-[4/3] overflow-hidden rounded-2xl bg-zinc-100" title="{{ $photo->gallery->title }}">
                    <img src="{{ $photo->thumbUrl() }}" alt="{{ $photo->altText($player->full_name) }}" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105">
                </a>
            @endforeach
        </div>
    </section>
@endif
@endsection

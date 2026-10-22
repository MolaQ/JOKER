@extends('layouts.public')

@section('title', $player->full_name)
@section('description', __(':name - :position drużyny :team (:club).', ['name' => $player->full_name, 'position' => $player->positionsLabel() ?? __('zawodnik'), 'team' => $player->team?->name, 'club' => $site['club_name']]))

@if($player->photo_path)
    @section('og_image', \App\Support\Media::url($player->photo_path))
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
        'Zasięg w ataku' => $player->spike_reach ? $player->spike_reach.' cm' : null,
        'Zasięg w bloku' => $player->block_reach ? $player->block_reach.' cm' : null,
    ]);
@endphp
<section class="relative isolate -mt-16 overflow-hidden bg-ink pt-16 text-white lg:-mt-20 lg:pt-20">
    <div class="net-pattern absolute inset-0 -z-10" aria-hidden="true"></div>
    <span class="pointer-events-none absolute -right-8 bottom-0 -z-10 font-display text-[16rem] leading-none font-bold text-white/5 select-none sm:text-[24rem]" aria-hidden="true">{{ $player->jersey_number }}</span>

    <div class="container-site grid items-end gap-10 pt-16 pb-14 lg:grid-cols-[auto_1fr] lg:pt-24">
        <x-avatar :photo="$player->photo_path" :name="$player->full_name" :label="$player->jersey_number" class="aspect-[4/5] w-48 rounded-3xl text-7xl ring-4 ring-sky-club/40 lg:w-64" />
        <div>
            <a href="{{ route('teams.show', $player->team) }}" class="text-sm text-zinc-400 transition hover:text-white">← {{ $player->team?->name }}</a>
            <p class="eyebrow mt-5">{{ $player->positionsLabel() }} @if($player->jersey_number) · #{{ $player->jersey_number }} @endif</p>
            <h1 class="heading-xl mt-3">{{ $player->first_name }}<br>{{ $player->last_name }}</h1>

            @if($stats)
                <dl class="mt-8 grid max-w-3xl grid-cols-2 gap-px overflow-hidden rounded-2xl bg-white/10 ring-1 ring-white/10 {{ [1 => 'sm:grid-cols-1', 2 => 'sm:grid-cols-2', 3 => 'sm:grid-cols-3', 4 => 'sm:grid-cols-4', 5 => 'sm:grid-cols-5'][count($stats)] }}">
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
    <div class="card p-6 lg:sticky lg:top-28 lg:self-start">
        <h2 class="heading-md">{{ __('Informacje') }}</h2>
        <dl class="mt-5 divide-y divide-zinc-100 text-sm">
            @foreach([
                'Data urodzenia' => $player->birth_date?->format('d.m.Y'),
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
                        <dd class="font-semibold">{{ $value }}</dd>
                    </div>
                @endif
            @endforeach
        </dl>

        <h3 class="mt-6 text-xs font-semibold tracking-widest text-zinc-500 uppercase">{{ __('Drużyny w tym sezonie') }}</h3>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach($seasonTeams->filter() as $seasonTeam)
                <a href="{{ route('teams.show', $seasonTeam) }}" class="chip hover:bg-sky-club hover:text-white">{{ $seasonTeam->name }}</a>
            @endforeach
        </div>
    </div>

    <div class="space-y-8 lg:col-span-2">
        @livewire('player-proposal-form', ['player' => $player], key('player-proposal-'.$player->id))

        @if($career->isNotEmpty())
            <div class="card overflow-hidden">
                <h2 class="heading-md px-6 pt-6">{{ __('Kariera klubowa') }}</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
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

        @if($player->achievements->isNotEmpty())
            <div class="card p-6">
                <h2 class="heading-md">{{ __('Osiągnięcia') }}</h2>
                <ul class="mt-5 space-y-3">
                    @foreach($player->achievements as $achievement)
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
                            $won = $result ? $result[0] > $result[1] : null;
                            $article = $game->latestArticle();
                        @endphp
                        <li class="flex items-center gap-3 py-3 text-sm">
                            @if($won === true)
                                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-600" aria-label="{{ __('Wygrana') }}"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7"/></svg></span>
                            @elseif($won === false)
                                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-red-500/10 text-red-600" aria-label="{{ __('Porażka') }}"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg></span>
                            @endif
                            <span class="w-12 shrink-0 text-zinc-500 tabular-nums">{{ $game->game_date?->format('d.m') }}</span>
                            <span class="min-w-0 flex-1 truncate">
                                <x-team-label :label="$game->ourLabel()" class="font-semibold text-zinc-900" />
                                <span class="text-zinc-500">{{ $game->is_home ? __('vs') : __('u') }} {{ $game->opponentShortName() }}</span>
                            </span>
                            @if($result)
                                <span class="font-display text-lg font-bold tabular-nums">{{ $result[0] }}:{{ $result[1] }}</span>
                            @endif
                            @if($article)
                                <a href="{{ route('articles.show', $article) }}" class="text-xs font-semibold text-sky-club-dark hover:underline">{{ __('Relacja') }}</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($player->bio)
            <div class="card p-8">
                <h2 class="heading-md">{{ __('O zawodniku') }}</h2>
                <div class="prose-club mt-5">{!! strip_tags($player->bio, '<p><br><strong><em><b><i><ul><ol><li>') !!}</div>
            </div>
        @endif

        @foreach($player->positions() as $playerPosition)
            <div @class(['relative overflow-hidden rounded-3xl p-8', 'bg-sky-club text-white' => $loop->first, 'bg-white text-zinc-900 ring-1 ring-sky-club/30' => ! $loop->first])>
                <p class="font-display text-sm font-semibold tracking-[0.25em] uppercase opacity-80">{{ $loop->first ? __('Pozycja') : __('Gra też jako') }}</p>
                <h2 class="mt-2 font-display text-3xl font-bold uppercase">{{ $playerPosition->label() }}</h2>
                <p @class(['mt-3 max-w-xl', 'text-white/90' => $loop->first, 'text-zinc-600' => ! $loop->first])>{{ $playerPosition->description() }}</p>
            </div>
        @endforeach
    </div>
</section>

@if($photos->isNotEmpty())
    <section id="zdjecia" class="container-site pb-16">
        <p class="eyebrow">{{ __('Galerie') }}</p>
        <h2 class="heading-lg mt-3">{{ __('Zdjęcia') }}</h2>
        <div class="mt-6 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
            @foreach($photos as $photo)
                <a href="{{ route('galleries.show', $photo->gallery) }}" class="group relative aspect-[4/3] overflow-hidden rounded-2xl bg-zinc-100" title="{{ $photo->gallery->title }}">
                    <img src="{{ $photo->url() }}" alt="{{ $photo->altText($player->full_name) }}" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105">
                </a>
            @endforeach
        </div>
    </section>
@endif
@endsection

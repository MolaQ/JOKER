@extends('layouts.public')

@section('title', $group->name.' · '.__('Minisiatkówka'))
@section('description', __(':group: dzieci, wyjazdy, wyniki i zasady gry w minisiatkówce.', ['group' => $group->name]))
@section('og_image', $group->photoUrl())

@section('content')
<section class="relative isolate -mt-16 overflow-hidden bg-ink pt-16 text-white lg:-mt-20 lg:pt-20">
    <x-photo :src="$group->photoUrl()" :alt="$group->name" eager width="1800" height="1000" class="absolute inset-0 -z-20 h-full w-full opacity-60" />
    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink via-ink/70 to-ink/30"></div>
    <div class="container-site pt-24 pb-14 lg:pt-36 lg:pb-20">
        <a href="{{ route('mini.index') }}" class="text-sm text-zinc-400 transition hover:text-white">← {{ __('Minisiatkówka') }}</a>
        @if($group->ageLine())<p class="eyebrow mt-6">{{ $group->ageLine() }}</p>@endif
        <h1 class="heading-xl mt-3">{{ $group->name }}</h1>
        <span class="mt-4 flex gap-2">@for($i = 0; $i < $group->players_on_court; $i++)<span class="size-4 rounded-full bg-amber-400"></span>@endfor</span>
        <dl class="mt-10 flex flex-wrap gap-x-10 gap-y-4">
            <div><dt class="text-xs text-zinc-400 uppercase">{{ __('Dzieci') }}</dt><dd class="font-display text-4xl font-bold">{{ $players->count() }}</dd></div>
            <div><dt class="text-xs text-zinc-400 uppercase">{{ __('Medale') }}</dt><dd class="font-display text-4xl font-bold">{{ $medals->count() }}</dd></div>
            @if($upcoming->first())
                <div><dt class="text-xs text-zinc-400 uppercase">{{ __('Najbliższy wyjazd') }}</dt><dd class="font-display text-2xl font-semibold uppercase">{{ $upcoming->first()->dateLabel() }}@if($upcoming->first()->city) · {{ $upcoming->first()->city }}@endif</dd></div>
            @endif
        </dl>
    </div>
</section>

@php
    $tabs = collect([
        'dzieci' => [__('Dzieci'), true],
        'wyjazdy' => [__('Wyjazdy'), $upcoming->isNotEmpty()],
        'wyniki' => [__('Wyniki'), $past->isNotEmpty()],
        'historia' => [__('Historia'), $medals->isNotEmpty() || filled($group->history)],
        'zasady' => [__('Zasady'), filled($group->rules)],
    ])->filter(fn ($tab) => $tab[1]);
@endphp
<section class="container-site py-10 lg:py-14" x-data="{ tab: (new URLSearchParams(location.search)).get('tab') || 'dzieci' }">
    <nav class="flex flex-wrap gap-2" role="tablist">
        @foreach($tabs as $key => [$name])
            <button type="button" role="tab" x-on:click="tab = '{{ $key }}'; history.replaceState(null, '', '?tab={{ $key }}')" :aria-selected="tab === '{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-sky-club text-white' : 'bg-white text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-50'" class="rounded-full px-4 py-2 text-sm font-semibold">{{ $name }}</button>
        @endforeach
    </nav>

    {{-- Dzieci: zdjęcie tylko ze zgodą rodzica, inaczej sylwetka --}}
    <div x-show="tab === 'dzieci'" class="mt-8 grid grid-cols-2 gap-6 sm:grid-cols-4 lg:grid-cols-6">
        @forelse($players as $child)
            <figure class="text-center">
                @if($child->photoUrl())
                    <img src="{{ $child->photoUrl() }}" alt="{{ $child->name() }}" loading="lazy" class="mx-auto size-24 rounded-full object-cover ring-4 ring-white shadow">
                @else
                    <span class="mx-auto flex size-24 items-end justify-center overflow-hidden rounded-full bg-zinc-200 ring-4 ring-white shadow" aria-hidden="true"><svg viewBox="0 0 24 24" class="size-20 text-zinc-400" fill="currentColor"><circle cx="12" cy="8" r="4.5"/><path d="M3 23c0-5 4-8.5 9-8.5s9 3.5 9 8.5z"/></svg></span>
                @endif
                <figcaption class="mt-2 text-sm font-semibold text-zinc-900">{{ $child->name() }}@if($child->birth_year)<span class="block text-xs font-normal text-zinc-500">{{ $child->birth_year }}</span>@endif</figcaption>
            </figure>
        @empty
            <p class="col-span-full text-sm text-zinc-500">{{ __('Lista dzieci pojawi się wkrótce.') }}</p>
        @endforelse
    </div>

    <div x-show="tab === 'wyjazdy'" x-cloak class="mt-8 grid gap-3 lg:grid-cols-2">
        @foreach($upcoming as $trip)
            <x-mini-trip :trip="$trip" :show-parent-info="$showParentInfo" :show-group="false" />
        @endforeach
        @unless($showParentInfo)<p class="text-sm text-zinc-500 lg:col-span-2">{{ __('Rodzice po zalogowaniu widzą godzinę zbiórki, transport i koszt wyjazdu.') }}</p>@endunless
    </div>

    <div x-show="tab === 'wyniki'" x-cloak class="mt-8 grid gap-3 lg:grid-cols-2">
        @foreach($past as $trip)
            <x-mini-trip :trip="$trip" :show-group="false" />
        @endforeach
    </div>

    <div x-show="tab === 'historia'" x-cloak class="mt-8 grid gap-8 lg:grid-cols-2">
        @if($medals->isNotEmpty())
            <ul class="space-y-2">
                @foreach($medals as $team)
                    <li class="flex items-center gap-3 rounded-2xl bg-white px-4 py-3 ring-1 ring-zinc-200">
                        <span class="text-3xl">{{ ['🥇', '🥈', '🥉'][$team->place - 1] }}</span>
                        <div><p class="font-semibold text-zinc-900">{{ $team->trip->title }}</p><p class="text-sm text-zinc-500">{{ $team->trip->starts_on->translatedFormat('F Y') }}@if($team->trip->city) · {{ $team->trip->city }}@endif · {{ $team->name }}</p></div>
                    </li>
                @endforeach
            </ul>
        @endif
        @if($group->history)<div class="prose max-w-none whitespace-pre-line text-zinc-700">{{ $group->history }}</div>@endif
    </div>

    <div x-show="tab === 'zasady'" x-cloak class="prose mt-8 max-w-3xl whitespace-pre-line text-zinc-700">{{ $group->rules }}</div>

    @if($others->isNotEmpty())
        <h2 class="heading-md mt-16">{{ __('Inne grupy') }}</h2>
        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($others as $other)
                <x-mini-group-tile :group="$other" class="min-h-48!" />
            @endforeach
        </div>
    @endif
</section>
@endsection

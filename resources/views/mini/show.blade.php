@extends('layouts.public')

@section('title', $group->name.' · '.__($site['mini_title']))
@section('description', __(':group: dzieci, wyjazdy, wyniki i zasady gry w minisiatkówce.', ['group' => $group->name]))
@section('og_image', $group->photoUrl())

@section('content')
<section class="relative isolate -mt-16 overflow-hidden bg-ink pt-16 text-white lg:-mt-20 lg:pt-20">
    <x-photo :src="$group->photoUrl()" :alt="$group->name" eager width="1800" height="1000" class="absolute inset-0 -z-20 h-full w-full opacity-60" />
    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink via-ink/70 to-ink/30"></div>
    <div class="container-site pt-24 pb-14 lg:pt-36 lg:pb-20">
        <a href="{{ route('mini.index') }}" class="text-sm text-zinc-400 transition hover:text-white">← {{ __($site['mini_title']) }}</a>
        @if($group->ageLine())<p class="eyebrow mt-6">{{ $group->ageLine() }}</p>@endif
        <h1 class="heading-xl mt-3">{{ $group->name }}</h1>
        <x-mini-players :group="$group" size="lg" class="mt-4" />
        <dl class="mt-10 flex flex-wrap gap-x-10 gap-y-4">
            <div><dt class="text-xs text-zinc-400 uppercase">{{ __('Dzieci') }}</dt><dd class="font-display text-4xl font-bold">{{ $players->count() }}</dd></div>
            @if($site['mini_show_history'])<div><dt class="text-xs text-zinc-400 uppercase">{{ __('Medale') }}</dt><dd class="font-display text-4xl font-bold">{{ $medals->count() }}</dd></div>@endif
            @if($site['mini_show_trips'] && $upcoming->first())
                <div><dt class="text-xs text-zinc-400 uppercase">{{ __('Najbliższy wyjazd') }}</dt><dd class="font-display text-2xl font-semibold uppercase">{{ $upcoming->first()->dateLabel() }}@if($upcoming->first()->city) · {{ $upcoming->first()->city }}@endif</dd></div>
            @endif
        </dl>
    </div>
</section>

@php
    $tabs = collect([
        'o-grupie' => [__('O grupie'), filled(strip_tags((string) $group->about))],
        'dzieci' => [__('Dzieci'), $site['mini_show_children']],
        'wyjazdy' => [__('Wyjazdy'), $site['mini_show_trips'] && $upcoming->isNotEmpty()],
        'wyniki' => [__('Wyniki'), $site['mini_show_results'] && $past->isNotEmpty()],
        'historia' => [__('Historia i sukcesy'), $site['mini_show_history'] && ($medals->isNotEmpty() || filled($group->history))],
        'zasady' => [__('Zasady'), $site['mini_show_rules'] && filled($group->rules)],
        'zglos' => ['＋ '.__('Turnieje, wyniki, dzieci'), \App\Livewire\MiniSubmissions::canSubmit()],
    ])->filter(fn ($tab) => $tab[1]);
@endphp
{{-- Grupy jako zakładki: kliknięcie przechodzi do składu, dzieci i zasad danej grupy --}}
<div class="sticky top-16 z-30 border-b border-zinc-200 bg-white/90 backdrop-blur-xl lg:top-20">
    <nav class="container-site scrollbar-none flex gap-2 overflow-x-auto py-3" aria-label="{{ __('Grupy minisiatkówki') }}">
        @foreach($groups as $tabGroup)
            <a href="{{ route('mini.show', $tabGroup) }}" @if($tabGroup->is($group)) aria-current="page" @endif @class([
                'flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition',
                'bg-club-navy text-white shadow' => $tabGroup->is($group),
                'bg-zinc-100 text-zinc-700 hover:bg-zinc-200' => ! $tabGroup->is($group),
            ])>
                <x-mini-players :group="$tabGroup" size="xs" aria-hidden="true" />
                {{ $tabGroup->name }}
            </a>
        @endforeach
    </nav>
</div>

<section class="container-site py-10 lg:py-14" x-data="{ tab: (new URLSearchParams(location.search)).get('tab') || @js($tabs->keys()->first()) }">
    <nav class="flex flex-wrap gap-2" role="tablist">
        @foreach($tabs as $key => [$name])
            <button type="button" role="tab" x-on:click="tab = '{{ $key }}'; history.replaceState(null, '', '?tab={{ $key }}')" :aria-selected="tab === '{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-sky-club text-white' : 'bg-white text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-50'" class="rounded-full px-4 py-2 text-sm font-semibold">{{ $name }}</button>
        @endforeach
    </nav>

    @if($tabs->has('o-grupie'))
    <div x-show="tab === 'o-grupie'" @if($tabs->keys()->first() !== 'o-grupie') x-cloak @endif class="prose-club mt-8 max-w-3xl">{!! $group->html('about') !!}</div>
    @endif

    {{-- Dzieci: kafelki jak w kadrze drużyn --}}
    @if($tabs->has('dzieci'))
    <div x-show="tab === 'dzieci'" @if($tabs->keys()->first() !== 'dzieci') x-cloak @endif class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6">
        @php
            $accent = $group->iconColor();
            $initials = fn ($child) => mb_strtoupper(mb_substr($child->first_name, 0, 1).mb_substr($child->last_name, 0, 1));
        @endphp
        @forelse($players as $child)
            {{-- Kafelek jak w kadrze drużyn: zdjęcie tylko ze zgodą rodzica, inaczej inicjały w kolorze grupy --}}
            <figure class="card group relative overflow-hidden" style="--mini-accent: {{ $accent }}">
                <div class="relative aspect-[4/5] w-full overflow-hidden bg-gradient-to-br from-graphite to-ink">
                    @if($child->photoUrl())
                        <img src="{{ $child->photoUrl() }}" alt="{{ $child->displayName() }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                    @else
                        <span class="absolute inset-0 flex items-center justify-center pb-10 font-display text-6xl font-bold text-(--mini-accent)" aria-hidden="true">{{ $initials($child) }}</span>
                    @endif
                    <span class="absolute inset-x-0 top-0 h-1 bg-(--mini-accent)" aria-hidden="true"></span>
                    @if($site['mini_show_birth_year'] && $child->birth_year)
                        <span class="absolute top-3 right-3 rounded-full bg-black/40 px-2 py-0.5 text-[11px] font-bold text-white tabular-nums backdrop-blur">{{ $child->birth_year }}</span>
                    @endif
                    <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-ink via-ink/80 to-transparent px-3 pt-8 pb-3">
                        <span class="block font-display text-lg leading-tight font-semibold text-white uppercase">{{ $child->displayName() }}</span>
                        <x-mini-players :group="$group" size="xs" class="mt-1 opacity-90" aria-hidden="true" />
                    </figcaption>
                </div>
            </figure>
        @empty
            <p class="col-span-full text-sm text-zinc-500">{{ __('Lista dzieci pojawi się wkrótce.') }}</p>
        @endforelse
    </div>
    @endif

    @if($tabs->has('wyjazdy'))
    <div x-show="tab === 'wyjazdy'" @if($tabs->keys()->first() !== 'wyjazdy') x-cloak @endif class="mt-8 grid gap-3 lg:grid-cols-2">
        @foreach($upcoming as $trip)
            <x-mini-trip :trip="$trip" :show-parent-info="$showParentInfo" :show-group="false" />
        @endforeach
        @unless($showParentInfo)<p class="text-sm text-zinc-500 lg:col-span-2">{{ __('Rodzice po zalogowaniu widzą godzinę zbiórki, transport i koszt wyjazdu.') }}</p>@endunless
    </div>
    @endif

    @if($tabs->has('wyniki'))
    <div x-show="tab === 'wyniki'" @if($tabs->keys()->first() !== 'wyniki') x-cloak @endif class="mt-8 grid gap-3 lg:grid-cols-2">
        @foreach($past as $trip)
            <x-mini-trip :trip="$trip" :show-group="false" />
        @endforeach
    </div>
    @endif

    @if($tabs->has('historia'))
    <div x-show="tab === 'historia'" @if($tabs->keys()->first() !== 'historia') x-cloak @endif class="mt-8 grid gap-8 lg:grid-cols-2">
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
        @if($group->history)<div class="prose-club max-w-3xl">{!! $group->html('history') !!}</div>@endif
    </div>
    @endif

    @if($tabs->has('zglos'))
    <div x-show="tab === 'zglos'" @if($tabs->keys()->first() !== 'zglos') x-cloak @endif class="mt-8">
        <livewire:mini-submissions :group-id="$group->id" />
    </div>
    @endif

    @if($tabs->has('zasady'))
    <div x-show="tab === 'zasady'" @if($tabs->keys()->first() !== 'zasady') x-cloak @endif class="prose-club mt-8 max-w-3xl">{!! $group->html('rules') !!}</div>
    @endif

</section>
@endsection

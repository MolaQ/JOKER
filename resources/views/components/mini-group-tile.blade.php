@props(['group'])
{{-- Kafel grupy minisiatkówki: zdjęcie, ikonki zawodników = liczba graczy na boisku, kategoria i jedna żywa informacja --}}
@php $medal = $site['mini_tile_medal'] ? $group->medals()->first(fn ($team) => $team->place === 1) : null; @endphp
<a href="{{ route('mini.show', $group) }}" {{ $attributes->merge(['class' => 'group relative isolate flex min-h-64 flex-col justify-end overflow-hidden rounded-3xl p-6 text-white']) }}>
    <x-photo :src="$group->photoUrl()" :alt="$group->name" sizes="(min-width: 1024px) 25vw, 100vw" class="absolute inset-0 -z-10 h-full w-full transition duration-700 group-hover:scale-105" />
    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink via-ink/40 to-transparent"></div>
    {{-- Górny pasek: liczba dzieci i medal z lewej, ikonki zawodników z prawej, zawsze w tym samym miejscu na każdym kaflu --}}
    <div class="absolute inset-x-5 top-5 flex items-start justify-between gap-2">
        <span class="flex flex-wrap gap-1.5">
            @if($site['mini_tile_count'] && ($group->players_count ?? null))
                <span class="chip bg-white/15 text-white backdrop-blur">{{ trans_choice(':count dziecko|:count dzieci|:count dzieci', $group->players_count) }}</span>
            @endif
            @if($medal)
                <span class="chip bg-amber-400 font-bold text-amber-950" title="{{ $medal->trip->title }}">🥇 {{ $medal->trip->starts_on->format('Y') }}</span>
            @endif
        </span>
        @if($site['mini_tile_dots'])
            <x-mini-players :group="$group" size="md" class="rounded-full bg-black/25 px-2 py-1 backdrop-blur" />
        @endif
    </div>
    @if($group->ageLine())
        <p class="text-xs font-semibold tracking-widest text-sky-club-light uppercase">{{ $group->ageLine() }}</p>
    @endif
    <h3 class="mt-1 font-display text-3xl font-bold uppercase">{{ $group->name }}</h3>
    @if($site['mini_tile_live'] && ($line = $group->tileLine()))
        <p class="mt-2 text-sm text-white/80">{{ $line }}</p>
    @endif
</a>

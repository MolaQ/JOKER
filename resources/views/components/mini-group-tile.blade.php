@props(['group'])
{{-- Kafel grupy minisiatkówki: zdjęcie, kropki = liczba graczy na boisku, kategoria i jedna żywa informacja --}}
@php $medal = $site['mini_tile_medal'] ? $group->medals()->first(fn ($team) => $team->place === 1) : null; @endphp
<a href="{{ route('mini.show', $group) }}" {{ $attributes->merge(['class' => 'group relative isolate flex min-h-64 flex-col justify-end overflow-hidden rounded-3xl p-6 text-white']) }}>
    <x-photo :src="$group->photoUrl()" :alt="$group->name" sizes="(min-width: 1024px) 25vw, 100vw" class="absolute inset-0 -z-10 h-full w-full transition duration-700 group-hover:scale-105" />
    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink via-ink/40 to-transparent"></div>
    @if($site['mini_tile_count'] && ($group->players_count ?? null))
        <span class="chip absolute top-5 left-5 bg-white/15 text-white backdrop-blur">{{ trans_choice(':count dziecko|:count dzieci|:count dzieci', $group->players_count) }}</span>
    @endif
    @if($medal)
        <span class="chip absolute top-5 right-5 bg-amber-400 font-bold text-amber-950" title="{{ $medal->trip->title }}">🥇 {{ $medal->trip->starts_on->format('Y') }}</span>
    @endif
    @if($site['mini_tile_dots'])
    <span class="mb-2 flex gap-1.5" aria-label="{{ trans_choice(':count gracz na boisku|:count graczy na boisku|:count graczy na boisku', $group->players_on_court) }}">
        @for($i = 0; $i < $group->players_on_court; $i++)<span class="size-3 rounded-full bg-amber-400"></span>@endfor
    </span>
    @endif
    @if($group->ageLine())
        <p class="text-xs font-semibold tracking-widest text-sky-club-light uppercase">{{ $group->ageLine() }}</p>
    @endif
    <h3 class="mt-1 font-display text-3xl font-bold uppercase">{{ $group->name }}</h3>
    @if($site['mini_tile_live'] && ($line = $group->tileLine()))
        <p class="mt-2 text-sm text-white/80">{{ $line }}</p>
    @endif
</a>

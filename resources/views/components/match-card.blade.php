@props(['game', 'dark' => false])

@php
    $ourName = 'Joker · '.($game->team?->name ?? 'Piła');
    $opponent = $game->opponentShortName();
    $home = $game->is_home ? $ourName : $opponent;
    $away = $game->is_home ? $opponent : $ourName;
    $sets = $game->isFinished() ? [$game->home_score, $game->away_score] : null;
    $result = $game->ourSetsResult();
    $won = $result ? $result[0] > $result[1] : null;
@endphp

<article {{ $attributes->merge(['class' => ($dark ? 'bg-white/5 text-white ring-white/10' : 'bg-white text-zinc-900 ring-zinc-200/70').' relative flex flex-col rounded-3xl p-6 ring-1 transition duration-300 hover:-translate-y-1']) }}>
    <div class="flex items-center justify-between gap-3 text-xs">
        <span class="font-semibold tracking-wider uppercase {{ $dark ? 'text-sky-club-light' : 'text-sky-club-dark' }}">
            {{ $game->game_date?->translatedFormat('D, d M · H:i') }}
        </span>
        @if($won !== null)
            <span @class(['rounded-full px-2.5 py-0.5 font-bold', 'bg-emerald-500/15 text-emerald-500' => $won, 'bg-red-500/15 text-red-500' => ! $won])>{{ $won ? 'Wygrana' : 'Porażka' }}</span>
        @else
            <span class="rounded-full px-2.5 py-0.5 font-semibold {{ $dark ? 'bg-white/10' : 'bg-zinc-100' }}">{{ $game->is_home ? 'U siebie' : 'Wyjazd' }}</span>
        @endif
    </div>

    <div class="mt-5 grid grid-cols-[1fr_auto_1fr] items-center gap-3">
        <p class="font-display text-lg leading-tight font-semibold uppercase">{{ $home }}</p>
        <p class="font-display text-2xl font-bold tabular-nums {{ $dark ? 'text-white' : 'text-zinc-900' }}">
            @if($sets)
                {{ $sets[0] }}<span class="mx-1 opacity-40">:</span>{{ $sets[1] }}
            @else
                <span class="text-base opacity-50">vs</span>
            @endif
        </p>
        <p class="text-right font-display text-lg leading-tight font-semibold uppercase">{{ $away }}</p>
    </div>

    <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs {{ $dark ? 'text-zinc-400' : 'text-zinc-500' }}">
        @if($game->competition)
            <span>{{ $game->competition->name }}</span>
        @endif
        @if($game->venue)
            <span>{{ $game->venue }}</span>
        @endif
    </div>
</article>

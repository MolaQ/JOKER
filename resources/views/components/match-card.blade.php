@props(['game', 'dark' => false])

@php
    $ourName = 'Joker · '.($game->team?->name ?? 'Piła');
    $opponent = $game->opponentShortName();
    $home = $game->is_home ? $ourName : $opponent;
    $away = $game->is_home ? $opponent : $ourName;
    $sets = $game->isFinished() ? [$game->home_score, $game->away_score] : null;
    $result = $game->ourSetsResult();
    $outcome = $game->outcome();
    $won = $outcome && $outcome !== 'derby' ? $outcome === 'win' : null;
@endphp

<article {{ $attributes->merge(['class' => ($dark ? 'bg-white/5 text-white ring-white/10' : 'bg-white text-zinc-900 ring-zinc-200/70').' relative flex flex-col rounded-3xl p-6 ring-1 transition duration-300 hover:-translate-y-1']) }}>
    <div class="flex items-center justify-between gap-3 text-xs">
        <span class="font-semibold tracking-wider uppercase {{ $dark ? 'text-sky-club-light' : 'text-sky-club-dark' }}">
            {{ $game->isFinished() && $game->game_date?->format('H:i') === '00:00' ? $game->game_date->translatedFormat('D, d M') : $game->kickoffLabel() }}
        </span>
        @if($outcome === 'derby')
            <span class="inline-flex items-center gap-1 rounded-full bg-sky-club/15 px-2.5 py-0.5 font-bold {{ $dark ? 'text-sky-club-light' : 'text-sky-club-dark' }}"><x-derby-heart class="h-3.5 w-3.5 {{ $dark ? 'text-sky-club-light' : 'text-sky-club' }}" /> {{ __('Pilska siatkówka') }}</span>
        @elseif($won !== null)
            <span @class(['rounded-full px-2.5 py-0.5 font-bold', 'bg-emerald-500/15 text-emerald-500' => $won, 'bg-red-500/15 text-red-500' => ! $won])>{{ $won ? __('Wygrana') : __('Porażka') }}</span>
        @else
            <span class="rounded-full px-2.5 py-0.5 font-semibold {{ $dark ? 'bg-white/10' : 'bg-zinc-100' }}">{{ $game->is_home ? __('U siebie') : __('Wyjazd') }}</span>
        @endif
    </div>

    <div class="mt-5 grid grid-cols-[1fr_auto_1fr] items-center gap-3">
        <p class="font-display text-lg leading-tight font-semibold uppercase">{{ $home }}</p>
        <p class="font-display text-2xl font-bold tabular-nums {{ $dark ? 'text-white' : 'text-zinc-900' }}">
            @if($sets)
                {{ $sets[0] }}<span class="mx-1 opacity-40">:</span>{{ $sets[1] }}
            @else
                <span class="text-base opacity-50">{{ __('vs') }}</span>
            @endif
        </p>
        <p class="text-right font-display text-lg leading-tight font-semibold uppercase">{{ $away }}</p>
    </div>

    @if($sets && is_array($game->sets_score) && count($game->sets_score))
        <p class="mt-3 text-center text-xs tabular-nums {{ $dark ? 'text-zinc-400' : 'text-zinc-500' }}">
            {{ collect($game->sets_score)->map(fn ($set) => implode(':', (array) $set))->implode(' · ') }}
        </p>
    @endif

    <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs {{ $dark ? 'text-zinc-400' : 'text-zinc-500' }}">
        @if($game->competition)
            <span>{{ $game->competition->name }}</span>
        @endif
        @if($game->venue)
            <span>{{ $game->venue }}</span>
        @endif
        <a href="{{ route('games.show', $game) }}" class="inline-flex min-h-11 items-center font-semibold {{ $dark ? 'text-sky-club-light' : 'text-sky-club-dark' }} hover:underline">{{ __('Szczegóły meczu') }} →</a>
        @if($game->isUpcoming())
            <a href="{{ route('games.calendar', $game) }}" class="ml-auto inline-flex min-h-11 items-center gap-1.5 font-semibold {{ $dark ? 'text-sky-club-light' : 'text-sky-club-dark' }}" title="{{ __('Dodaj mecz do kalendarza w telefonie') }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M8 3v4m8-4v4M3 10h18M12 14v4m-2-2h4M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/></svg>
                {{ __('Do kalendarza') }}
            </a>
        @endif
    </div>
</article>

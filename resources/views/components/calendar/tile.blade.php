@props(['tile'])
{{-- Kafelek dnia w kalendarzu meczów: pasek i nazwa drużyny w kolorze kategorii, rozgrywki, pod spodem pary herbów z wynikiem albo godziną.
     Telefon: zostaje pasek, herb rywala i wynik. Kliknięcie meczów otwiera okno szczegółów (calendarDetails). --}}
@php
    $base = 'group/tile relative block w-full overflow-hidden rounded-xl bg-white py-1 pr-1 pl-2 text-left shadow-sm ring-1 ring-zinc-200 transition duration-200 hover:-translate-y-0.5 hover:shadow-md hover:ring-(--tile) md:py-1.5 md:pr-1.5 md:pl-2.5';
    $stripe = '<span class="absolute inset-y-0 left-0 w-1 bg-(--tile)" aria-hidden="true"></span>';
    $head = 'hidden min-w-0 md:block';
@endphp
@if($tile['type'] === 'matches')
    <button type="button" style="--tile: {{ $tile['color'] }}" class="{{ $base }}"
        x-on:click="$dispatch('match-details', { tile: @js(\Illuminate\Support\Arr::only($tile, ['label', 'color', 'competition', 'matches'])), el: $el })"
        aria-label="{{ $tile['label'] }} · {{ $tile['competition'] }}: {{ collect($tile['matches'])->map(fn ($match) => $match['home']['name'].' – '.$match['away']['name'].' '.$match['mid'])->implode(', ') }}">
        {!! $stripe !!}
        <span class="{{ $head }}">
            <span class="block truncate text-[10px] leading-tight font-bold tracking-wider text-(--tile) uppercase [filter:brightness(.8)]">{{ $tile['label'] }}</span>
            <span class="block truncate text-[10px] leading-tight text-zinc-500">{{ $tile['competition'] }}</span>
        </span>
        {{-- Dzień turnieju potrafi mieć kilkanaście meczów: na kafelku trzy pierwsze, resztę pokazuje okno szczegółów --}}
        @php $hidden = max(0, count($tile['matches']) - 3); @endphp
        @foreach(array_slice($tile['matches'], 0, 3) as $match)
            <span @class(['flex flex-col items-center justify-center gap-0.5 md:mt-1 md:flex-row md:justify-between md:gap-1', 'mt-1 border-t border-dashed border-zinc-200 pt-1' => ! $loop->first])>
                @foreach(['home', 'away'] as $sideKey)
                    @if($sideKey === 'away')
                        <span @class([
                            'shrink-0 font-display text-[10px] leading-none font-bold tabular-nums max-md:order-2 md:text-xs',
                            'text-emerald-600' => $match['outcome'] === 'win',
                            'text-red-600' => $match['outcome'] === 'loss',
                            'text-sky-club-dark' => $match['outcome'] === 'derby',
                            'font-semibold text-zinc-500' => ! $match['finished'],
                        ])>{{ $match['mid'] ?: '–' }}</span>
                    @endif
                    <x-avatar logo :photo="$match[$sideKey]['logo']" :name="$match[$sideKey]['name']" @class(['size-6 shrink-0 rounded-full text-[9px] max-md:order-1 md:size-7', 'hidden md:flex' => $match['ourSide'] === $sideKey]) />
                @endforeach
            </span>
        @endforeach
        @if($hidden)
            <span class="mt-1 block border-t border-dashed border-zinc-200 pt-1 text-center text-[10px] font-semibold text-zinc-500">+{{ $hidden }}</span>
        @endif
    </button>
@elseif($tile['type'] === 'mini')
    <a href="{{ $tile['url'] }}" style="--tile: {{ $tile['color'] }}" class="{{ $base }}" title="{{ $tile['competition'] }}{{ $tile['trip']->city ? ' · '.$tile['trip']->city : '' }}">
        {!! $stripe !!}
        <span class="flex flex-wrap items-center gap-0.5">@foreach($tile['groups'] as $miniGroup)<x-mini-players :group="$miniGroup" size="xs" />@endforeach</span>
        <span class="{{ $head }} mt-0.5">
            <span class="block truncate text-[10px] leading-tight font-bold tracking-wider text-(--tile) uppercase [filter:brightness(.8)]">{{ $tile['label'] }}</span>
            <span class="block truncate text-[10px] leading-tight text-zinc-500">{{ $tile['competition'] }}</span>
            @foreach($tile['trip']->teams as $miniTeam)
                @if($miniTeam->place)
                    <span class="block truncate text-[10px] leading-tight text-zinc-700">{{ $miniTeam->name }} · <strong>{{ $miniTeam->place }}.</strong></span>
                @endif
            @endforeach
        </span>
    </a>
@else
    @php $tag = $tile['url'] ? 'a' : 'div'; @endphp
    <{{ $tag }} @if($tile['url']) href="{{ $tile['url'] }}" @endif style="--tile: {{ $tile['color'] }}" class="{{ $base }}" title="{{ $tile['competition'] }}{{ $tile['tournament']->city ? ' · '.$tile['tournament']->city : '' }}">
        {!! $stripe !!}
        <span class="block truncate text-[10px] leading-tight font-bold tracking-wider text-(--tile) uppercase [filter:brightness(.8)]">{{ __('Turniej') }}</span>
        <span class="line-clamp-2 block text-[10px] leading-tight text-zinc-700 md:line-clamp-1">{{ $tile['competition'] }}</span>
        <span class="{{ $head }} text-[10px] leading-tight text-zinc-500">{{ $tile['label'] }}</span>
    </{{ $tag }}>
@endif

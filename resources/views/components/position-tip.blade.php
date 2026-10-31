@props(['position', 'align' => 'left'])
{{-- Pozycja zawodnika jako przycisk: po kliknięciu wyskakuje dymek z opisem (treść: Ustawienia → Profile zawodników). --}}
@php
    // Strefy boiska (siatka u góry): 4 3 2 / 5 6 1. Podświetlamy, gdzie zwykle gra dana pozycja.
    $zones = match ($position) {
        \App\PlayerPosition::Setter => [2, 1],
        \App\PlayerPosition::OutsideHitter => [4, 6],
        \App\PlayerPosition::Opposite => [2, 1],
        \App\PlayerPosition::MiddleBlocker => [3],
        \App\PlayerPosition::Libero => [5, 6],
    };
    $cells = [4 => [0, 0], 3 => [1, 0], 2 => [2, 0], 5 => [0, 1], 6 => [1, 1], 1 => [2, 1]];
@endphp
<span x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false" {{ $attributes->class('relative inline-block') }}>
    <button type="button" @click="open = ! open" :aria-expanded="open" aria-haspopup="dialog"
        class="cursor-pointer [text-transform:inherit] underline decoration-current/40 decoration-dotted underline-offset-4 transition hover:decoration-current">{{ $position->label() }}</button>
    <span x-show="open" x-cloak role="dialog" aria-label="{{ $position->label() }}"
        x-transition:enter="transition duration-300 ease-[cubic-bezier(.34,1.56,.64,1)]"
        x-transition:enter-start="opacity-0 scale-75 -translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        @class([
            'absolute top-full z-40 mt-3 block w-72 max-w-[calc(100vw-2rem)] rounded-2xl bg-gradient-to-br from-club-navy to-sky-club-dark p-5 text-left font-sans tracking-normal normal-case text-white shadow-2xl shadow-sky-club/30 ring-1 ring-white/15',
            'left-0 origin-top-left' => $align === 'left',
            'right-0 origin-top-right' => $align === 'right',
        ])>
        <span @class(['absolute -top-1.5 block h-3 w-3 rotate-45 bg-club-navy ring-1 ring-white/15 [clip-path:polygon(0_0,100%_0,0_100%)]', 'left-6' => $align === 'left', 'right-6' => $align === 'right'])></span>
        <span class="flex items-start gap-4">
            <svg viewBox="0 0 66 50" class="h-12 w-16 shrink-0" aria-hidden="true">
                <rect x="1" y="5" width="64" height="44" rx="3" fill="none" stroke="currentColor" stroke-opacity=".35" />
                <line x1="0" y1="4" x2="66" y2="4" stroke="currentColor" stroke-width="2.5" />
                @foreach($cells as $zone => [$col, $row])
                    <rect x="{{ 3 + $col * 21 }}" y="{{ 7 + $row * 21 }}" width="18" height="19" rx="2" @class(['fill-[#ffd600]' => in_array($zone, $zones), 'fill-white/10' => ! in_array($zone, $zones)]) />
                @endforeach
            </svg>
            <span class="block min-w-0">
                <span class="block text-[11px] font-semibold tracking-[0.2em] text-white/60 uppercase">{{ __('Pozycja') }}</span>
                <span class="mt-0.5 block font-display text-2xl leading-tight font-bold uppercase">{{ $position->label() }}</span>
            </span>
        </span>
        <span class="mt-3 block text-sm leading-relaxed text-white/90">{{ $position->description() }}</span>
    </span>
</span>

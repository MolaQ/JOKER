@props(['venue'])
@php
    // Symbol miejsca treningu: sala podzielona w poprzek na trzy boiska (1 lewe, 2 środkowe, 3 prawe), główne boisko to cała sala, siłownia osobno.
    // Kolory i ikonę siłowni ustawia się w panelu: System → Ustawienia systemu → Kolory.
    $site = app(\App\Support\SiteSettings::class);
    $court = $site->color('color_venue');
    $gym = $site->color('color_gym');
    $third = 46 / 3;
    $part = ['court1' => 0, 'court2' => 1, 'court3' => 2][$venue] ?? null;
@endphp
<svg {{ $attributes->merge(['class' => 'h-[30px] w-12 shrink-0']) }} viewBox="0 0 48 30" aria-hidden="true">
    @if($venue === 'gym')
        <rect x="1" y="1" width="46" height="28" rx="4" fill="{{ $gym }}" fill-opacity="0.12" stroke="{{ $gym }}" stroke-width="1.5"/>
        <g transform="translate(13 4) scale(0.9167)" fill="none" stroke="{{ $gym }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            @switch($site->get('gym_icon'))
                @case('kettlebell')
                    <path d="M8.5 9.5 7.6 6.4A3 3 0 0 1 10.5 2.5h3a3 3 0 0 1 2.9 3.9l-.9 3.1"/><path d="M5 15a7 7 0 1 1 14 0c0 2.2-.6 4.2-1.6 5.5H6.6C5.6 19.2 5 17.2 5 15Z"/>
                    @break
                @case('plate')
                    <circle cx="12" cy="12" r="9.5"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2" fill="{{ $gym }}"/>
                    @break
                @case('lifter')
                    <path d="M2 5h20M4 3v4M20 3v4"/><circle cx="12" cy="9.5" r="1.8"/><path d="M7 5.5 9 12h6l2-6.5M12 12v4l-3 6M12 16l3 6"/>
                    @break
                @default
                    <path d="M6.5 6.5v11M17.5 6.5v11M3.5 9v6M20.5 9v6M6.5 12h11"/>
            @endswitch
        </g>
    @elseif($venue === 'main')
        <rect x="1" y="1" width="46" height="28" rx="4" fill="{{ $court }}" stroke="{{ $court }}" stroke-width="1.5"/>
    @else
        <rect x="1" y="1" width="46" height="28" rx="4" fill="#fff" stroke="#a1a1aa" stroke-width="1.5"/>
        @if($part !== null)
            <rect x="{{ 1 + $part * $third + 1.5 }}" y="2.5" width="{{ $third - 3 }}" height="25" rx="2" fill="{{ $court }}"/>
        @endif
        <line x1="{{ 1 + $third }}" y1="5" x2="{{ 1 + $third }}" y2="25" stroke="#d4d4d8" stroke-width="1.2"/>
        <line x1="{{ 1 + 2 * $third }}" y1="5" x2="{{ 1 + 2 * $third }}" y2="25" stroke="#d4d4d8" stroke-width="1.2"/>
    @endif
</svg>

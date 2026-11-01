@props(['uid' => 'x'])
{{-- Napis VS w stylu komiksowego wybuchu (miniatury transmisji): czarny rozprysk, kreski, plamki
     i dwie litery z obwódką. V ma kolor drużyny z prawej (stoi na lewej połówce), S kolor drużyny z lewej,
     więc każda litera odcina się od swojego tła. Tło przezroczyste. --}}
@php
    mt_srand(1992);
    $rand = fn (float $min, float $max) => $min + mt_rand() / mt_getrandmax() * ($max - $min);
    [$cx, $cy] = [400, 240];

    // Postrzępiony rozprysk: ostre wierzchołki na przemian z wcięciami, rozciągnięty w poziomie.
    $burst = [];
    $spikes = 34;
    for ($i = 0; $i < $spikes * 2; $i++) {
        $angle = $i / ($spikes * 2) * 2 * M_PI;
        $radius = $i % 2 === 0 ? $rand(150, 215) : $rand(85, 120);
        $burst[] = round($cx + cos($angle) * $radius * 1.55, 1).','.round($cy + sin($angle) * $radius * 0.95, 1);
    }

    // Kreski „prędkości” rozchodzące się od środka.
    $lines = [];
    for ($i = 0; $i < 30; $i++) {
        $angle = $rand(0, 2 * M_PI);
        [$from, $to] = [$rand(170, 230), $rand(260, 380)];
        $lines[] = [
            round($cx + cos($angle) * $from * 1.5, 1), round($cy + sin($angle) * $from * 0.9, 1),
            round($cx + cos($angle) * $to * 1.5, 1), round($cy + sin($angle) * $to * 0.9, 1),
            round($rand(1.5, 4.5), 1), $i % 3 === 0 ? '#fff' : '#0b0b0b',
        ];
    }

    // Plamki farby wokół rozprysku.
    $drops = [];
    for ($i = 0; $i < 34; $i++) {
        $angle = $rand(0, 2 * M_PI);
        $radius = $rand(150, 300);
        $drops[] = [round($cx + cos($angle) * $radius * 1.45, 1), round($cy + sin($angle) * $radius * 0.85, 1), round($rand(2.5, 13), 1)];
    }
@endphp
<svg {{ $attributes->merge(['class' => 'ytt-vs-burst']) }} viewBox="0 0 800 480" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <defs>
        <linearGradient id="ytt-vs-v-{{ $uid }}" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" style="stop-color: color-mix(in srgb, var(--ac) 35%, #fff)" />
            <stop offset=".45" style="stop-color: var(--ac)" />
            <stop offset="1" style="stop-color: color-mix(in srgb, var(--ac) 55%, #000)" />
        </linearGradient>
        <linearGradient id="ytt-vs-s-{{ $uid }}" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" style="stop-color: color-mix(in srgb, var(--hc) 35%, #fff)" />
            <stop offset=".45" style="stop-color: var(--hc)" />
            <stop offset="1" style="stop-color: color-mix(in srgb, var(--hc) 55%, #000)" />
        </linearGradient>
    </defs>
    @foreach($lines as [$x1, $y1, $x2, $y2, $width, $stroke])
        <line x1="{{ $x1 }}" y1="{{ $y1 }}" x2="{{ $x2 }}" y2="{{ $y2 }}" stroke="{{ $stroke }}" stroke-width="{{ $width }}" stroke-linecap="round" />
    @endforeach
    <polygon points="{{ implode(' ', $burst) }}" fill="#0b0b0b" />
    @foreach($drops as [$x, $y, $r])
        <circle cx="{{ $x }}" cy="{{ $y }}" r="{{ $r }}" fill="#0b0b0b" />
    @endforeach
    <g class="ytt-vs-letters" transform="translate(400 330) skewX(-12)">
        <text x="-118" y="0" text-anchor="middle" font-size="330" fill="url(#ytt-vs-v-{{ $uid }})" stroke="#0b0b0b" stroke-width="20" stroke-linejoin="round" paint-order="stroke">V</text>
        <text x="118" y="0" text-anchor="middle" font-size="330" fill="url(#ytt-vs-s-{{ $uid }})" stroke="#0b0b0b" stroke-width="20" stroke-linejoin="round" paint-order="stroke">S</text>
        <text x="-118" y="0" text-anchor="middle" font-size="330" fill="none" stroke="rgba(255,255,255,.55)" stroke-width="3" transform="translate(-4 -5)">V</text>
        <text x="118" y="0" text-anchor="middle" font-size="330" fill="none" stroke="rgba(255,255,255,.55)" stroke-width="3" transform="translate(-4 -5)">S</text>
    </g>
</svg>

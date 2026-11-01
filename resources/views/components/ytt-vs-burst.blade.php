@props(['uid' => 'x'])
{{-- Napis VS w stylu graffiti (miniatury transmisji): ostre litery z grubą czarną obwódką i cieniem,
     w środku gradient i języki płomieni, za nimi czarny rozprysk tuszu z kolcami, plamkami i bazgrołami.
     Kolory wnętrza każdej litery idą ze zmiennych CSS (--vv1..--vv4 dla V, --vs1..--vs4 dla S, --vso obwódka).
     Tło przezroczyste. Kształty są stałe (losowanie z ziarnem), więc grafika wygląda zawsze tak samo. --}}
@php
    mt_srand(1992);
    $rand = fn (float $min, float $max) => $min + mt_rand() / mt_getrandmax() * ($max - $min);
    $pt = fn (array $points) => implode(' ', array_map(fn ($p) => $p[0].','.$p[1], $points));
    [$cx, $cy] = [410, 262];

    // Litery: wielokąty o ostrych kątach i kolcach, jak w malowanym sprayem graffiti.
    $letterV = [
        [62, 118], [132, 102], [140, 80], [162, 100], [270, 92], [256, 122], [292, 280], [350, 146], [334, 130],
        [412, 94], [462, 62], [432, 120], [372, 262], [394, 268], [356, 300], [340, 390], [326, 452], [298, 404],
        [272, 404], [240, 322], [200, 336], [222, 274], [178, 154], [124, 156],
    ];
    $letterS = [
        [452, 150], [430, 124], [480, 110], [600, 102], [722, 92], [782, 58], [748, 118], [730, 152], [660, 150],
        [586, 154], [534, 160], [536, 216], [686, 228], [730, 262], [762, 290], [728, 302], [724, 352], [690, 398],
        [640, 416], [452, 420], [372, 468], [414, 404], [430, 356], [600, 356], [656, 350], [652, 300], [500, 292],
        [458, 262], [448, 200],
    ];

    // Języki płomieni w literach: od dołu w górę, lekko wygięte.
    $flames = function (float $from, float $to, int $count) use ($rand) {
        $paths = [];
        for ($i = 0; $i < $count; $i++) {
            $x = $from + ($to - $from) * ($i + $rand(.1, .9)) / $count;
            $w = $rand(12, 24);
            $tip = $rand(140, 300);
            $sway = $rand(-40, 40);
            $paths[] = sprintf('M%.0f,480 C%.0f,390 %.0f,%.0f %.0f,%.0f C%.0f,%.0f %.0f,390 %.0f,480 Z',
                $x - $w, $x - $w - $sway * .6, $x + $sway - $w * .2, $tip + 90, $x + $sway * .4, $tip,
                $x + $sway + $w * .5, $tip + 110, $x + $w - $sway * .5, $x + $w);
        }

        return $paths;
    };
    $flamesV = $flames(100, 450, 11);
    $flamesS = $flames(420, 770, 11);

    // Rozprysk tuszu: nieregularna plama rozciągnięta w poziomie.
    $splat = [];
    for ($i = 0; $i < 90; $i++) {
        $angle = $i / 90 * 2 * M_PI;
        $radius = $rand(105, 140) + (mt_rand(0, 6) === 0 ? $rand(15, 45) : 0);
        $splat[] = [round($cx + cos($angle) * $radius * 1.75, 1), round($cy + sin($angle) * $radius * .95, 1)];
    }

    // Cienkie kolce rozchodzące się od środka.
    $rays = [];
    for ($i = 0; $i < 70; $i++) {
        $angle = $rand(0, 2 * M_PI);
        $from = $rand(110, 150);
        $to = $from + $rand(70, 190);
        $half = $rand(.005, .014);
        $rays[] = $pt([
            [round($cx + cos($angle - $half) * $from * 1.7, 1), round($cy + sin($angle - $half) * $from, 1)],
            [round($cx + cos($angle) * $to * 1.7, 1), round($cy + sin($angle) * $to, 1)],
            [round($cx + cos($angle + $half) * $from * 1.7, 1), round($cy + sin($angle + $half) * $from, 1)],
        ]);
    }

    // Plamki i krople.
    $drops = [];
    for ($i = 0; $i < 46; $i++) {
        $angle = $rand(0, 2 * M_PI);
        $radius = $rand(150, 300);
        $drops[] = [round($cx + cos($angle) * $radius * 1.55, 1), round($cy + sin($angle) * $radius * .85, 1), round($rand(2, 11), 1)];
    }

    // Drobne bazgroły (tagi) po bokach.
    $tags = [];
    foreach ([[96, 384], [668, 450], [700, 150], [120, 220]] as [$tx, $ty]) {
        $line = [];
        for ($i = 0; $i < 9; $i++) {
            $line[] = [round($tx + $i * $rand(5, 9), 1), round($ty + ($i % 2 ? -1 : 1) * $rand(4, 12), 1)];
        }
        $tags[] = $pt($line);
    }
@endphp
<svg {{ $attributes->merge(['class' => 'ytt-vs-burst']) }} viewBox="0 0 820 520" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <defs>
        @foreach(['v' => 'vv', 's' => 'vs'] as $letter => $var)
            <linearGradient id="ytt-{{ $letter }}-fill-{{ $uid }}" x1="0" y1="0" x2="0.15" y2="1">
                <stop offset="0" style="stop-color: var(--{{ $var }}1)" />
                <stop offset=".5" style="stop-color: var(--{{ $var }}2)" />
                <stop offset="1" style="stop-color: var(--{{ $var }}3)" />
            </linearGradient>
            <clipPath id="ytt-{{ $letter }}-clip-{{ $uid }}">
                <polygon points="{{ $pt($letter === 'v' ? $letterV : $letterS) }}" />
            </clipPath>
        @endforeach
    </defs>

    <g fill="#0b0b0b">
        @foreach($rays as $ray)<polygon points="{{ $ray }}" />@endforeach
        <polygon points="{{ $pt($splat) }}" />
        @foreach($drops as [$x, $y, $r])<circle cx="{{ $x }}" cy="{{ $y }}" r="{{ $r }}" />@endforeach
    </g>
    <g fill="none" stroke="#0b0b0b" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
        @foreach($tags as $tag)<polyline points="{{ $tag }}" />@endforeach
    </g>

    @foreach(['v' => [$letterV, $flamesV, 'vv'], 's' => [$letterS, $flamesS, 'vs']] as $letter => [$shape, $tongues, $var])
        {{-- cień (wytłoczenie) w prawo w dół --}}
        <polygon points="{{ $pt($shape) }}" transform="translate(12 14)" fill="var(--vso)" stroke="var(--vso)" stroke-width="16" stroke-linejoin="miter" />
        {{-- obwódka i wypełnienie --}}
        <polygon points="{{ $pt($shape) }}" fill="var(--vso)" stroke="var(--vso)" stroke-width="16" stroke-linejoin="miter" />
        <polygon points="{{ $pt($shape) }}" fill="url(#ytt-{{ $letter }}-fill-{{ $uid }})" />
        <g clip-path="url(#ytt-{{ $letter }}-clip-{{ $uid }})">
            @foreach($tongues as $index => $tongue)
                <path d="{{ $tongue }}" style="fill: var(--{{ $var }}{{ $index % 3 === 2 ? 3 : 4 }})" opacity="{{ $index % 3 === 2 ? '.7' : '.85' }}" />
            @endforeach
            {{-- połysk przy górnej krawędzi --}}
            <polygon points="{{ $pt($shape) }}" fill="none" stroke="rgba(255,255,255,.55)" stroke-width="5" transform="translate(-3 -4)" />
        </g>
    @endforeach
</svg>

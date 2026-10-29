@props(['lineup'])
@php
    use App\PlayerPosition;
    use App\Support\Media;

    $slots = $lineup->lineup();
    $coach = $lineup->coach;
    $initials = fn (string $name) => collect(preg_split('/\s+/u', trim($name)))->filter()->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->take(2)->implode('');
    // Miejsca na boisku 1080×1350: kolumny 17/50/83% szerokości boiska, pierwsza linia przy siatce, druga za linią ataku.
    $columns = [0.17, 0.5, 0.83];
    $rowTop = [380, 728];
    $W = 840;
    $H = 820;
    $attack = (int) round($H * 0.36);
    $end = $H - 110;
@endphp
{{-- Grafika „Skład dnia” (1080×1350, na Facebooka i Instagrama). Na stronie skalowana do szerokości ramki,
     przycisk „Pobierz obrazek” zapisuje ją w pełnym rozmiarze (resources/js/daily-lineup.js). --}}
<div {{ $attributes->merge(['class' => 'daily-lineup-frame']) }} data-daily-lineup-frame>
    <div class="daily-lineup" data-daily-lineup>
        <img class="dl-bg" src="{{ asset('img/sklad-dnia-tlo.jpg') }}" alt="" aria-hidden="true">
        <div class="dl-shade" aria-hidden="true"></div>

        <div class="dl-header">
            <img class="dl-logo" src="{{ $site->image('logo', asset('img/logo-512.png')) }}" alt="">
            <div class="dl-date">{{ $lineup->date->format('d.m.Y') }}<small>{{ $lineup->date->translatedFormat('l') }}</small></div>
            <div class="dl-title">{{ __('Skład dnia') }}</div>
            <div class="dl-sub">{{ __(':club · wszystkie roczniki', ['club' => $site['club_name']]) }}</div>
        </div>

        <div class="dl-court" aria-hidden="true">
            <svg viewBox="-60 -40 {{ $W + 120 }} {{ $H + 60 }}">
                <defs>
                    <filter id="dl-glow" x="-20%" y="-20%" width="140%" height="140%"><feGaussianBlur stdDeviation="7" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
                    <pattern id="dl-mesh" width="14" height="14" patternUnits="userSpaceOnUse"><path d="M0 0H14M0 0V14" stroke="#e0f0ff" stroke-opacity=".55" stroke-width="1.4"/></pattern>
                </defs>
                <rect x="0" y="0" width="{{ $W }}" height="{{ $end }}" fill="rgba(4,8,24,.45)"/>
                <g stroke="#60b4ff" stroke-width="5" fill="none" filter="url(#dl-glow)" stroke-linecap="square">
                    <rect x="0" y="0" width="{{ $W }}" height="{{ $end }}"/>
                    <line x1="0" y1="{{ $attack }}" x2="{{ $W }}" y2="{{ $attack }}"/>
                    <line x1="-60" y1="{{ $attack }}" x2="-14" y2="{{ $attack }}" stroke-dasharray="14 12"/>
                    <line x1="{{ $W + 14 }}" y1="{{ $attack }}" x2="{{ $W + 60 }}" y2="{{ $attack }}" stroke-dasharray="14 12"/>
                    <line x1="0" y1="{{ $end + 16 }}" x2="0" y2="{{ $end + 50 }}"/>
                    <line x1="{{ $W }}" y1="{{ $end + 16 }}" x2="{{ $W }}" y2="{{ $end + 50 }}"/>
                </g>
                {{-- Siatka z taśmą, słupkami i antenkami (białe, nad siatką czerwone paski). --}}
                <rect x="-46" y="-78" width="{{ $W + 92 }}" height="70" fill="rgba(4,8,24,.35)"/>
                <rect x="-46" y="-78" width="{{ $W + 92 }}" height="70" fill="url(#dl-mesh)"/>
                <rect x="-46" y="-86" width="{{ $W + 92 }}" height="12" rx="3" fill="#e0f0ff" filter="url(#dl-glow)"/>
                <rect x="-46" y="-12" width="{{ $W + 92 }}" height="5" rx="2" fill="#e0f0ff" opacity=".8"/>
                <rect x="-62" y="-112" width="10" height="122" rx="4" fill="#e0f0ff" filter="url(#dl-glow)"/>
                <rect x="{{ $W + 52 }}" y="-112" width="10" height="122" rx="4" fill="#e0f0ff" filter="url(#dl-glow)"/>
                @foreach([-3, $W - 3] as $x)
                    <rect x="{{ $x }}" y="-176" width="6" height="170" fill="#ffffff"/>
                    @foreach(range(0, 4) as $band)
                        <rect x="{{ $x }}" y="{{ -176 + $band * 18 }}" width="6" height="9" fill="#e11d2e"/>
                    @endforeach
                @endforeach
            </svg>
        </div>

        @foreach($slots as $index => $slot)
            @php
                $player = $slot['player'];
                $libero = $slot['position'] === PlayerPosition::Libero;
            @endphp
            @if($player)
                <div class="dl-tile" style="left: {{ 120 + $columns[$index % 3] * $W }}px; top: {{ $rowTop[intdiv($index, 3)] }}px">
                    <div @class(['dl-pic', 'dl-pic-libero' => $libero])>
                        @if($player->photo_path)
                            <img src="{{ Media::url($player->photo_path) }}" alt="">
                        @elseif($player->jersey_number !== null)
                            <span class="dl-num">{{ $player->jersey_number }}</span>
                        @else
                            <span class="dl-ini">{{ $initials($player->full_name) }}</span>
                        @endif
                        @if($lineup->captain_id === $player->id)
                            <span class="dl-cap">C</span>
                        @endif
                    </div>
                    <div class="dl-name"><span>{{ $player->first_name }}</span><strong>{{ $player->last_name }}</strong></div>
                    <div class="dl-meta"><b>{{ $slot['position']->label() }}</b><span>{{ $player->team?->name }}</span></div>
                </div>
            @endif
        @endforeach

        @if($coach)
            <div class="dl-coach">
                <div class="dl-pic">
                    @if($coach->avatar_path)
                        <img src="{{ Media::url($coach->avatar_path) }}" alt="">
                    @else
                        <span class="dl-ini">{{ $initials($coach->name) }}</span>
                    @endif
                </div>
                <div><div class="dl-coach-label">{{ __('Trener') }}</div><div class="dl-coach-name">{{ $coach->name }}</div></div>
            </div>
        @endif

        <div class="dl-footer">{{ __('Losowanie co dzień · więcej serc na profilu, większa szansa') }} · <strong>{{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'jokerpila.pl' }}</strong></div>
    </div>
</div>

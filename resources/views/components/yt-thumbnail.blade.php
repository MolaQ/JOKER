@props(['settings', 'data', 'mode' => 'preview', 'sponsors' => []])
{{-- Miniatura transmisji 1920×1080 (strona /miniatury). Pełny rozmiar, ramka skaluje ją do podglądu,
     przycisk „Pobierz PNG” zapisuje ją w pełnym rozmiarze (resources/js/thumbnail-export.js). --}}
@php
    use App\Models\ThumbnailTemplate;
    use App\Support\ThumbnailData;

    $s = $settings;
    $summary = $mode === 'summary';
    $sets = ThumbnailData::parseSets((string) ($data['sets'] ?? ''));
    $score = ThumbnailData::score((string) ($data['score'] ?? ''), $sets);
    [$dayName, $dateLabel] = ThumbnailData::dateLabels((string) ($data['date'] ?? ''));
    $time = (string) ($data['time'] ?? '');
    $place = trim((string) ($data['place'] ?? ''));
    $place2 = trim((string) ($data['place2'] ?? ''));
    $homeWon = $score ? $score[0] > $score[1] : null;
    $images = collect($s['images'] ?? [])->filter(fn ($image) => filled($image['path'] ?? null))->keyBy('position');
    if ($s['tournament_logo'] && filled($data['tournament_logo'] ?? null)) {
        $images['tl'] = ['path' => $data['tournament_logo'], 'position' => 'tl', 'size' => 210];
    }
    $sponsors = $s['show_sponsors'] ? $sponsors : [];
    $bars = $s['layout'] === 'bars';
    $color = fn (?string $value, string $fallback) => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $value) ? $value : $fallback;
    $home = $data['home'];
    $away = $data['away'];
    $style = implode(';', [
        '--hc:'.$color($home['color'] ?? null, '#1d6ff2'),
        '--ac:'.$color($away['color'] ?? null, ThumbnailData::RIVAL_COLOR),
        '--hl:'.$color($s['accent'], '#60a5fa'),
        '--bar1:'.$color($s['bar_from'], '#0b1f4d'),
        '--bar2:'.$color($s['bar_to'], '#1d4ed8'),
        '--panel:'.$color($s['panel'], '#080a10'),
        'font-family:'.ThumbnailTemplate::fontFamily($s['font']),
    ]);
    $background = ThumbnailData::src($s['background'] ?: '/img/miniatury/hala.jpg');
    $initials = fn (array $side) => mb_strtoupper(mb_substr($side['name'] ?? '', 0, 1).mb_substr($side['sub'] ?? '', 0, 1)) ?: '?';
@endphp
<div {{ $attributes->merge(['class' => 'ytt '.($bars ? 'ytt-bars' : 'ytt-modern').($summary ? ' ytt-summary' : '').($sponsors ? ' ytt-has-sponsors' : '')]) }} style="{{ $style }}">
    <div class="ytt-bg" style="background-image: url('{{ $background }}')"></div>
    @if($s['tint'])
        <div class="ytt-half ytt-half-l"></div>
        <div class="ytt-half ytt-half-r"></div>
    @endif
    <div class="ytt-shade"></div>

    @if($bars)
        <div class="ytt-bar ytt-bar-top">
            @foreach(preg_split('/\s*\|\s*|\R/u', (string) ($s['label'] ?: ' ')) as $line)
                <span>{{ $line }}</span>
            @endforeach
        </div>
    @else
        <div class="ytt-streak" style="left:-60px;top:250px;width:420px;background:var(--hc)"></div>
        <div class="ytt-streak" style="left:-40px;top:300px;width:260px;height:10px;background:#fff"></div>
        <div class="ytt-streak" style="right:-60px;top:830px;width:420px;background:var(--ac)"></div>
        <div class="ytt-streak" style="right:-40px;top:880px;width:260px;height:10px;background:#fff"></div>
        <div class="ytt-title">
            @if(filled($s['label']))<div class="ytt-label">{{ $s['label'] }}</div>@endif
            @php $title = (string) ($summary ? $s['summary_title'] : $s['preview_title']); $words = preg_split('/\s+/u', trim($title), 2); @endphp
            <div class="ytt-h1">{{ $words[0] ?? '' }} @if(isset($words[1]))<b>{{ $words[1] }}</b>@endif</div>
            @if(filled($data['stage'] ?? null))<div class="ytt-stage">{{ $data['stage'] }}</div>@endif
        </div>
    @endif

    @foreach($images as $position => $image)
        <div class="ytt-corner ytt-corner-{{ $position }}" style="width:{{ (int) ($image['size'] ?? 200) }}px;height:{{ (int) ($image['size'] ?? 200) }}px"><img src="{{ ThumbnailData::src($image['path']) }}" alt=""></div>
    @endforeach

    @foreach(['h' => $home, 'a' => $away] as $key => $side)
        <div class="ytt-team ytt-team-{{ $key }}" style="--glow: var(--{{ $key === 'h' ? 'hc' : 'ac' }})">
            <div class="ytt-logo">
                @if(filled($side['logo'] ?? null))
                    <img src="{{ $side['logo'] }}" alt="">
                @else
                    <div class="ytt-fallback">{{ $initials($side) }}</div>
                @endif
            </div>
            @if($s['show_names'])
                <div class="ytt-name">{{ $side['name'] }}@if(filled($side['sub'] ?? null))<b>{{ $side['sub'] }}</b>@endif</div>
            @endif
        </div>
    @endforeach

    <div class="ytt-center">
        @if($bars && filled($data['stage'] ?? null))<div class="ytt-stage">{{ $data['stage'] }}</div>@endif
        @if($summary)
            @if($score)
                <div class="ytt-score">{{ $score[0] }}<i>:</i>{{ $score[1] }}</div>
            @else
                <div class="ytt-score ytt-score-empty">?<i>:</i>?</div>
            @endif
            @if($sets)
                <div class="ytt-sets">
                    @foreach($sets as [$a, $b])
                        <span @class(['ytt-set-won' => $homeWon !== null && ($a > $b) === $homeWon])>{{ $a }}:{{ $b }}</span>
                    @endforeach
                </div>
            @endif
            @if(filled($data['mvp'] ?? null))<div class="ytt-mvp">★ {{ $data['mvp'] }}</div>@endif
        @else
            <div class="ytt-vs">VS</div>
            @if($bars && ($dayName || $time))
                <div class="ytt-when">{{ $dayName }}@if($time) · {{ $time }}@endif</div>
            @endif
            @if(filled($s['live_text']))<div class="ytt-live">{{ $s['live_text'] }}</div>@endif
        @endif
    </div>

    @if($bars)
        <div class="ytt-bar ytt-bar-bottom">
            <span>{{ collect([$dateLabel, collect([$place, $place2])->filter()->implode(', ')])->filter()->implode(' | ') }}</span>
        </div>
    @else
        <div class="ytt-info">
            @if($dateLabel)
                <div class="ytt-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
                    <p>{{ $summary ? __('Rozegrany') : $dayName }}<small>{{ $dateLabel }}</small></p>
                </div>
            @endif
            @if(! $summary && $time)
                <div class="ytt-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    <p>{{ __('Godz.') }}<small>{{ $time }}</small></p>
                </div>
            @endif
            @if($place || $place2)
                <div class="ytt-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s7-6.1 7-11.5A7 7 0 0 0 5 9.5C5 14.9 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                    <p>{{ $place ?: $place2 }}@if($place && $place2)<small>{{ $place2 }}</small>@endif</p>
                </div>
            @endif
        </div>
    @endif

    @if($sponsors)
        <div class="ytt-sponsors">
            @foreach($sponsors as $logo)
                <img src="{{ $logo }}" alt="">
            @endforeach
        </div>
    @endif
</div>

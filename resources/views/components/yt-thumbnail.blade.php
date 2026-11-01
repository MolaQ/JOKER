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
    // Litery VS: kolory z szablonu albo (przełącznik) z barw drużyn na krzyż: V w kolorze gości, S gospodarzy.
    $vsLetters = [];
    foreach (['v' => 'vv', 's' => 'vs'] as $letter => $var) {
        if ($s['vs_team_colors']) {
            $team = $color(($letter === 'v' ? $away : $home)['color'] ?? null, $letter === 'v' ? ThumbnailData::RIVAL_COLOR : '#1d6ff2');
            $tones = ["color-mix(in srgb, {$team} 45%, #fff)", $team, "color-mix(in srgb, {$team} 60%, #000)", "color-mix(in srgb, {$team} 25%, #fff)"];
        } else {
            $tones = array_map(fn ($i) => $color($s["vs_{$letter}{$i}"] ?? null, ThumbnailTemplate::DEFAULTS["vs_{$letter}{$i}"]), [1, 2, 3, 4]);
        }
        foreach ($tones as $i => $tone) {
            $vsLetters[] = "--{$var}".($i + 1).':'.$tone;
        }
    }
    $stage = $s['show_stage'] ? (trim((string) $s['stage_text']) ?: (string) ($data['stage'] ?? '')) : '';
    $style = implode(';', [
        '--hc:'.$color($home['color'] ?? null, '#1d6ff2'),
        '--ac:'.$color($away['color'] ?? null, ThumbnailData::RIVAL_COLOR),
        // Gradient w połówce: od drugiego koloru drużyny przy krawędzi do pierwszego przy środku.
        '--hc2:'.($s['tint_gradient'] ? $color($home['color2'] ?? null, 'color-mix(in srgb, var(--hc) 55%, #fff)') : 'var(--hc)'),
        '--ac2:'.($s['tint_gradient'] ? $color($away['color2'] ?? null, 'color-mix(in srgb, var(--ac) 55%, #fff)') : 'var(--ac)'),
        '--vsc:'.$color($s['vs_color'], '#ffffff'),
        '--vsfont:'.ThumbnailTemplate::vsFontFamily($s['vs_font'], $s['font']),
        '--hl:'.$color($s['accent'], '#60a5fa'),
        '--tc:'.$color($s['title_color'] ?? null, '#ffffff'),
        '--bar1:'.$color($s['bar_from'], '#0b1f4d'),
        '--bar2:'.$color($s['bar_to'], '#1d4ed8'),
        '--panel:'.$color($s['panel'], '#080a10'),
        'font-family:'.ThumbnailTemplate::fontFamily($s['font']),
        '--vso:'.$color($s['vs_outline'] ?? null, '#0b0b0b'),
        ...$vsLetters,
        '--vss:'.max(30, min(250, (int) $s['vs_size'])) / 100,
        '--vsy:'.max(-400, min(400, (int) $s['vs_offset'])).'px',
    ]);
    $background = ThumbnailData::src($s['background'] ?: '/img/miniatury/hala.jpg');
    $initials = fn (array $side) => mb_strtoupper(mb_substr($side['name'] ?? '', 0, 1).mb_substr($side['sub'] ?? '', 0, 1)) ?: '?';
@endphp
<div {{ $attributes->merge(['class' => 'ytt '.($bars ? 'ytt-bars' : 'ytt-modern').($summary ? ' ytt-summary' : '').($sponsors ? ' ytt-has-sponsors' : '').($s['show_names'] ? '' : ' ytt-no-names').($s['title_two_lines'] ? ' ytt-title-2' : '')]) }} style="{{ $style }}">
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
        <div class="ytt-streak" style="left:-60px;top:196px;width:420px;background:var(--hc)"></div>
        <div class="ytt-streak" style="left:-40px;top:238px;width:260px;height:10px;background:#fff"></div>
        <div class="ytt-streak" style="right:-60px;top:968px;width:420px;background:var(--ac)"></div>
        <div class="ytt-streak" style="right:-40px;top:1012px;width:260px;height:10px;background:#fff"></div>
        <div class="ytt-title">
            @if(filled($s['label']))<div class="ytt-label">{{ $s['label'] }}</div>@endif
            @php $title = (string) ($summary ? $s['summary_title'] : $s['preview_title']); $words = preg_split('/\s+/u', trim($title), 2); @endphp
            <div class="ytt-h1">{{ $words[0] ?? '' }} @if(isset($words[1]))<b>{{ $words[1] }}</b>@endif</div>
            @if($stage !== '')<div class="ytt-stage">{{ $stage }}</div>@endif
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
        @if($bars && $stage !== '')<div class="ytt-stage">{{ $stage }}</div>@endif
        @if($summary)
            @if($score)
                <div class="ytt-score">{{ $score[0] }}<i>:</i>{{ $score[1] }}</div>
            @else
                <div class="ytt-score ytt-score-empty">?<i>:</i>?</div>
            @endif
            @if($sets)
                {{-- Sety: wygrana strona pogrubiona, kartka w kolorze zwycięzcy seta --}}
                <div class="ytt-sets">
                    @foreach($sets as $index => [$a, $b])
                        <div @class(['ytt-set', 'ytt-set-home' => $a > $b, 'ytt-set-away' => $b > $a])>
                            <small>{{ $index + 1 }}. set</small>
                            <span><b @class(['ytt-set-win' => $a > $b])>{{ $a }}</b><i>:</i><b @class(['ytt-set-win' => $b > $a])>{{ $b }}</b></span>
                        </div>
                    @endforeach
                </div>
            @endif
            @if(filled($data['mvp'] ?? null))<div class="ytt-mvp">★ {{ $data['mvp'] }}</div>@endif
        @else
            <div class="ytt-vs-wrap">
                @if($s['vs_style'] === 'image' && filled($s['vs_image']))
                    <img src="{{ ThumbnailData::src($s['vs_image']) }}" alt="VS" class="ytt-vs-image">
                @elseif($s['vs_style'] === 'burst')
                    <x-ytt-vs-burst :uid="$attributes->get('id', 'vs')" />
                @else
                    <div class="ytt-vs">VS</div>
                @endif
            </div>
            @if($bars && ($dayName || $time))
                <div class="ytt-when">{{ $dayName }}@if($time) · {{ $time }}@endif</div>
            @endif
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

    @if(! $summary && filled($s['live_text']))
        <div class="ytt-live ytt-live-{{ $s['live_position'] === 'br' ? 'br' : 'bl' }}">{{ $s['live_text'] }}</div>
    @endif

    @if($sponsors)
        <div class="ytt-sponsors">
            @foreach($sponsors as $logo)
                <img src="{{ $logo }}" alt="">
            @endforeach
        </div>
    @endif
</div>

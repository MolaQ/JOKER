@props([
    'src' => null,
    'alt' => '',
    'sizes' => '100vw',
    'width' => 800,
    'height' => 600,
    'eager' => false,
    // Punkt, który zostaje widoczny przy przycinaniu (np. „50% 30%”).
    'focus' => null,
])

@php
    $srcset = \App\Support\Media::srcset($src);
    $fallbackSrc = \App\Support\Media::sized($src, 800);
    // Gdy zdjęcie ma być tłem (absolute), nie nadpisujemy pozycjonowania.
    $position = str_contains((string) $attributes->get('class'), 'absolute') ? '' : 'relative ';
@endphp

{{-- Gradient w tle pokazuje się, dopóki zdjęcie się nie wczyta (albo gdy go brak). --}}
<div {{ $attributes->merge(['class' => $position.'overflow-hidden bg-gradient-to-br from-navy-800 via-navy-900 to-ink']) }}>
    @if($src)
        <img
            src="{{ $fallbackSrc }}"
            @if($srcset) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
            alt="{{ $alt }}"
            width="{{ $width }}"
            height="{{ $height }}"
            loading="{{ $eager ? 'eager' : 'lazy' }}"
            decoding="async"
            @if($eager) fetchpriority="high" @endif
            onerror="this.remove()"
            class="absolute inset-0 h-full w-full object-cover"
            @if($focus) style="object-position: {{ $focus }}" @endif
        >
    @endif
    {{ $slot }}
</div>

{{-- Motyw Jasny / Ciemny / Auto: ustawiany przed narysowaniem strony, żeby nie mrugała jasnym tłem.
     Bez własnego wyboru obowiązuje motyw z ustawień (System → Kolory), domyślnie jasny. --}}
@php $themeDefault = in_array($site['theme_default'] ?? 'light', ['light', 'dark', 'auto'], true) ? $site['theme_default'] : 'light'; @endphp
<script>
    (() => {
        const fallback = @js($themeDefault);
        let theme = fallback;
        try { theme = localStorage.getItem('theme') || fallback; } catch (e) {}
        const dark = theme === 'dark' || (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.dataset.themeChoice = theme;
        document.documentElement.dataset.themeDefault = fallback;
    })();
</script>
@php
    // Kolor panelu z ustawień: cała skala „sky” (przyciski, zaznaczenia, dolne menu) liczona od jednego koloru.
    $panelColor = $site->color('color_panel');
@endphp
@if(request()->routeIs('admin.*', 'thumbnails.*', 'logos.*') && strtolower($panelColor) !== '#0284c7')
    @php
        $mix = fn (int $percent, string $with) => "color-mix(in oklab, {$panelColor} {$percent}%, {$with})";
        $light = [50 => $mix(8, 'white'), 100 => $mix(16, 'white'), 200 => $mix(30, 'white'), 300 => $mix(50, 'white'), 400 => $mix(75, 'white'), 500 => $mix(90, 'white'), 600 => $panelColor, 700 => $mix(82, 'black'), 800 => $mix(66, 'black'), 900 => $mix(52, 'black'), 950 => $mix(36, 'black')];
        $dark = [50 => $light[950], 100 => $light[900], 200 => $light[800], 300 => $light[700], 400 => $light[400], 500 => $light[500], 600 => $light[600], 700 => $light[300], 800 => $light[200], 900 => $light[100], 950 => $light[50]];
    @endphp
    <style>
        html:root { @foreach($light as $shade => $value)--color-sky-{{ $shade }}: {{ $value }}; @endforeach }
        html:root.dark { @foreach($dark as $shade => $value)--color-sky-{{ $shade }}: {{ $value }}; @endforeach }
    </style>
@endif

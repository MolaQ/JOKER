@props(['filled' => true])
{{-- Serduszko polubień; kolor z panelu: System → Ustawienia systemu → Kolory → Serduszka. --}}
<svg {{ $attributes->merge(['class' => 'size-4 shrink-0']) }} viewBox="0 0 24 24" fill="{{ $filled ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linejoin="round" d="M12 20s-7.5-4.6-9.6-9.3C.9 7.2 3 3.5 6.6 3.5c2.1 0 3.6 1.2 5.4 3.2 1.8-2 3.3-3.2 5.4-3.2 3.6 0 5.7 3.7 4.2 7.2C19.5 15.4 12 20 12 20Z"/></svg>

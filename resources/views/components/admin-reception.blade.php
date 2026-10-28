@props(['views' => 0, 'likes' => 0])
{{-- Kolumna „Odbiór” w panelu: wyświetlenia i polubienia (serduszka) profilu. --}}
<span {{ $attributes->merge(['class' => 'flex items-center gap-3 tabular-nums']) }}>
    <span class="inline-flex items-center gap-1" title="Wyświetlenia (jedno wejście na wizytę)">
        <svg class="size-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 0 1 0-.64C3.42 7.51 7.36 4.5 12 4.5s8.57 3 9.96 7.18a1 1 0 0 1 0 .64C20.58 16.49 16.64 19.5 12 19.5s-8.57-3-9.96-7.18Z"/><circle cx="12" cy="12" r="3"/></svg>
        {{ number_format((int) $views, 0, ',', ' ') }}<span class="sr-only"> wyświetleń</span>
    </span>
    <span @class(['inline-flex items-center gap-1', 'text-rose-600' => $likes > 0]) title="Polubienia">
        <svg class="size-4 {{ $likes > 0 ? 'text-rose-500' : 'text-zinc-400' }}" fill="{{ $likes > 0 ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linejoin="round" d="M12 20s-7.5-4.6-9.6-9.3C.9 7.2 3 3.5 6.6 3.5c2.1 0 3.6 1.2 5.4 3.2 1.8-2 3.3-3.2 5.4-3.2 3.6 0 5.7 3.7 4.2 7.2C19.5 15.4 12 20 12 20Z"/></svg>
        {{ (int) $likes }}<span class="sr-only"> polubień</span>
    </span>
</span>

{{--
    Licznik odwiedzin w stopce: cztery równe kolumny (liczba nad podpisem), na telefonie na całą szerokość.
    Liczba, która się zmieniła po odświeżeniu (co 15 s), wjeżdża od dołu i na chwilę się podświetla (.stat-tick).
--}}
@php
    $stats = [
        ['online', $online, trans_choice('osoba teraz|osoby teraz|osób teraz', $online), __('Osoby na stronie w ostatnich 5 minutach')],
        ['today', $today, trans_choice('gość dziś|gości dziś|gości dziś', $today), __('Goście dzisiaj')],
        ['visits', $visits, trans_choice('wizyta|wizyty|wizyt', $visits), __('Wszystkie wizyty (odwiedzający liczony raz dziennie)')],
        ['total', $total, trans_choice('odsłona|odsłony|odsłon', $total), __('Wszystkie odsłony strony')],
    ];
@endphp
<div wire:poll.15s.visible class="grid w-full grid-cols-4 divide-x divide-white/10 overflow-hidden rounded-2xl bg-white/5 tabular-nums ring-1 ring-white/10 sm:w-auto sm:min-w-96" aria-live="polite">
    @foreach($stats as [$key, $value, $label, $title])
        <div class="flex min-w-0 flex-col items-center justify-center px-2 py-2 text-center sm:px-4" title="{{ $title }}">
            <span class="flex items-center gap-1.5 font-display text-lg leading-none font-semibold text-white">
                @if($key === 'online')
                    <span class="relative flex size-2 shrink-0" aria-hidden="true">
                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                        <span class="relative inline-flex size-2 rounded-full bg-emerald-400"></span>
                    </span>
                @endif
                <span class="overflow-hidden"><span wire:key="stat-{{ $key }}-{{ $value }}" class="stat-tick inline-block">{{ number_format($value, 0, ',', ' ') }}</span></span>
            </span>
            <span class="mt-1 truncate text-[11px] text-zinc-400">{{ $label }}</span>
        </div>
    @endforeach
</div>

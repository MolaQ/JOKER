@php $number = fn (int $value) => '<b class="font-semibold text-white">'.number_format($value, 0, ',', ' ').'</b>'; @endphp
<p wire:poll.15s.visible class="inline-flex flex-wrap items-center gap-x-3 gap-y-1 rounded-full bg-white/5 px-4 py-1.5 tabular-nums text-zinc-300 ring-1 ring-white/10" aria-live="polite">
    <span class="inline-flex items-center gap-2" title="{{ __('Osoby na stronie w ostatnich 5 minutach') }}">
        <span class="relative flex h-2 w-2">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
        </span>
        <span>{!! trans_choice(':count osoba teraz|:count osoby teraz|:count osób teraz', $online, ['count' => $number($online)]) !!}</span>
    </span>
    <span class="h-3 w-px bg-white/15" aria-hidden="true"></span>
    <span>{!! trans_choice(':count gość dziś|:count gości dziś|:count gości dziś', $today, ['count' => $number($today)]) !!}</span>
    <span class="h-3 w-px bg-white/15" aria-hidden="true"></span>
    <span>{!! __(':count odsłon', ['count' => $number($total)]) !!}</span>
</p>

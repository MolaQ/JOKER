<p wire:poll.15s.visible class="flex flex-wrap items-center gap-x-3 gap-y-1 tabular-nums text-zinc-500" aria-live="polite">
    <span class="inline-flex items-center gap-1.5" title="{{ __('Osoby na stronie w ostatnich 5 minutach') }}">
        <span class="relative flex h-1.5 w-1.5">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
            <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
        </span>
        {{ trans_choice(':count osoba teraz|:count osoby teraz|:count osób teraz', $online, ['count' => number_format($online, 0, ',', ' ')]) }}
    </span>
    <span aria-hidden="true">·</span>
    <span>{{ trans_choice(':count gość dziś|:count gości dziś|:count gości dziś', $today, ['count' => number_format($today, 0, ',', ' ')]) }}</span>
    <span aria-hidden="true">·</span>
    <span>{{ __(':count odsłon', ['count' => number_format($total, 0, ',', ' ')]) }}</span>
</p>

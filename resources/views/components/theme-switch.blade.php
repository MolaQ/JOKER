@props(['dark' => false])
{{-- Motyw: Jasny / Ciemny / Auto (resources/js/theme.js). --}}
<div role="group" aria-label="{{ __('Motyw') }}" {{ $attributes->class(['inline-flex rounded-full p-0.5 text-xs font-semibold', 'bg-white/[0.06] ring-1 ring-white/10' => $dark, 'bg-zinc-100 ring-1 ring-zinc-200' => ! $dark]) }}>
    @foreach(['light' => ['Jasny', 'M12 3v1.5m0 15V21m9-9h-1.5M4.5 12H3m15.36 6.36-1.06-1.06M6.7 6.7 5.64 5.64m12.72 0L17.3 6.7M6.7 17.3l-1.06 1.06M16.5 12a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z'], 'dark' => ['Ciemny', 'M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79Z'], 'auto' => ['Auto', 'M9 17.25v1.5m6-1.5v1.5M4.5 4.5h15A1.5 1.5 0 0 1 21 6v9.75a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 15.75V6a1.5 1.5 0 0 1 1.5-1.5Z']] as $value => [$label, $icon])
        <button type="button" data-theme-set="{{ $value }}" aria-pressed="false" @class([
            'flex min-h-9 flex-1 items-center justify-center gap-1.5 rounded-full px-3 transition',
            'text-zinc-400 hover:text-white aria-pressed:bg-white/10 aria-pressed:text-white' => $dark,
            'text-zinc-500 hover:text-zinc-900 aria-pressed:bg-white aria-pressed:text-zinc-900 aria-pressed:shadow-sm' => ! $dark,
        ])>
            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icon }}"/></svg>
            {{ __($label) }}
        </button>
    @endforeach
</div>

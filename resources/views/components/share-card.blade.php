@props(['card' => null, 'dark' => false, 'compact' => false])
{{-- Przycisk „Grafika”: przeglądarka rysuje grafikę z wynikiem albo relacją i udostępnia ją (telefon) albo pobiera (komputer). --}}
@if($card)
    @if($compact)
    <button type="button" data-share-card="{{ json_encode($card + ['theme' => \App\Support\ShareCards::theme()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}" title="{{ __('Grafika z wynikiem do udostępnienia') }}" aria-label="{{ __('Grafika z wynikiem do udostępnienia') }}"
        {{ $attributes->class('relative z-10 inline-flex size-6 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 hover:bg-sky-club/15 hover:text-sky-club-dark') }}>
        <svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16.5v2.25A2.25 2.25 0 0 0 6.25 21h11.5A2.25 2.25 0 0 0 20 18.75V16.5M12 3v12m0-12 4 4m-4-4-4 4"/></svg>
    </button>
    @else
    <button type="button" data-share-card="{{ json_encode($card + ['theme' => \App\Support\ShareCards::theme()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
        {{ $attributes->class([
            'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-semibold transition',
            'bg-white/10 text-white ring-1 ring-white/20 hover:bg-white/20' => $dark,
            'bg-white text-zinc-800 ring-1 ring-zinc-200 hover:bg-zinc-50' => ! $dark,
        ]) }}>
        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16.5v2.25A2.25 2.25 0 0 0 6.25 21h11.5A2.25 2.25 0 0 0 20 18.75V16.5M12 3v12m0-12 4 4m-4-4-4 4"/></svg>
        {{ __('Grafika') }}
    </button>
    @endif
@endif

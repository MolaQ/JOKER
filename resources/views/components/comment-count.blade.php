@props(['count' => 0])
{{-- Liczba komentarzy z ikonką dymka --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-sm font-medium text-zinc-700']) }} title="{{ trans_choice(':count komentarz|:count komentarze|:count komentarzy', $count) }}">
    <span aria-hidden="true">💬</span>
    <span class="tabular-nums" aria-hidden="true">{{ $count }}</span>
    <span class="sr-only">{{ trans_choice(':count komentarz|:count komentarze|:count komentarzy', $count) }}</span>
</span>

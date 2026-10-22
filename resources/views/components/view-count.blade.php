@props(['model'])
{{-- Licznik wyświetleń profilu (zawodnik, sztab, drużyna) --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-sm']) }} title="{{ __('Liczba wyświetleń') }}">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
    {{ $model->viewsLabel() }}
</span>

{{-- Brytyjska flaga przy wpisie, który ma wersję angielską --}}
<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center align-middle']) }} title="{{ __('Dostępna wersja angielska') }}">
    <x-flag country="gb" class="h-3 w-[1.125rem] rounded-[2px] ring-1 ring-black/15" />
    <span class="sr-only">{{ __('Dostępna wersja angielska') }}</span>
</span>

@props(['match', 'dark' => false])
{{-- Stan seta, który trwa (np. „2. set 13:10”), z pulsującą kropką; wpisywany przy stoliku co jakiś czas. --}}
@if($label = $match->currentSetLabel())
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 font-semibold whitespace-nowrap tabular-nums '.($dark ? 'text-red-300' : 'text-red-600')]) }} title="{{ __('Set trwa') }}">
        <span class="size-1.5 animate-pulse rounded-full {{ $dark ? 'bg-red-400' : 'bg-red-600' }}" aria-hidden="true"></span>{{ $label }}
    </span>
@endif

@props(['match'])
{{-- Plakietka dla kibiców, gdy mecz zaczyna się później (albo wcześniej) niż w planie --}}
@if($delay = $match->delayMinutes())
    <span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center rounded-full bg-amber-100 px-1.5 text-[10px] font-bold tracking-normal text-amber-800 normal-case']) }} title="{{ __('Według planu: :time', ['time' => $match->planned_starts_at->format('H:i')]) }}">{{ $delay > 0 ? __('opóźnienie :min min', ['min' => $delay]) : __('wcześniej o :min min', ['min' => abs($delay)]) }}</span>
@elseif($possible = $match->possibleDelay())
    <span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center rounded-full bg-amber-50 px-1.5 text-[10px] font-semibold tracking-normal text-amber-700 ring-1 ring-amber-200 normal-case']) }} title="{{ __('Poprzedni mecz na tym boisku zaczął się później. Godzina w harmonogramie się nie zmienia.') }}">{{ __('możliwe opóźnienie ok. :min min', ['min' => $possible]) }}</span>
@endif

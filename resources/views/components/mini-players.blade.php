@props(['group', 'size' => 'md'])
@php
    // Piłka w kolorze grupy i cyfra: tylu zawodników gra na boisku. Zawsze ten sam kwadrat, niezależnie od grupy.
    $box = ['xs' => 'size-[18px]', 'sm' => 'size-6', 'md' => 'size-8', 'lg' => 'size-12'][$size] ?? 'size-8';
    $color = $group->iconColor();
    $clip = 'mini-ball-'.$group->id.'-'.\Illuminate\Support\Str::random(6);
@endphp
<span {{ $attributes->merge(['class' => "{$box} inline-flex shrink-0"]) }} role="img" aria-label="{{ $group->name }} · {{ trans_choice(':count zawodnik na boisku|:count zawodników na boisku|:count zawodników na boisku', (int) $group->players_on_court, ['count' => (int) $group->players_on_court]) }}">
    <svg viewBox="0 0 24 24" class="size-full" aria-hidden="true">
        <g transform="translate(0.3 0.3) scale(0.84)">
            <clipPath id="{{ $clip }}"><circle cx="12" cy="12" r="11.2"/></clipPath>
            <circle cx="12" cy="12" r="11.2" fill="{{ $color }}"/>
            <g clip-path="url(#{{ $clip }})" fill="none" stroke="#fff" stroke-width="1.6">
                @foreach([0, 120, 240] as $angle)
                    <g transform="rotate({{ $angle }} 12 12)"><path d="M12 12C12.6 7 16.5 3.4 22 3.6"/><path d="M8.6 9.6C9.6 4.8 13.4 1.4 18.5.2"/></g>
                @endforeach
            </g>
        </g>
        <circle cx="17.8" cy="17.8" r="6" fill="{{ $color }}" stroke="#fff" stroke-width="1.2"/>
        <text x="17.8" y="21.5" text-anchor="middle" font-family="system-ui, sans-serif" font-weight="800" font-size="10.5" fill="#fff">{{ (int) $group->players_on_court }}</text>
    </svg>
</span>

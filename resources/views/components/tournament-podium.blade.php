@props(['standings'])
{{-- Klasyfikacja końcowa: pierwsza trójka na podium, dalsze miejsca listą. Nierozstrzygnięte miejsca na szaro. --}}
@php
    $byPosition = $standings->keyBy('position');
    $rest = $standings->where('position', '>', 3);
    $steps = [2 => ['h-20', 'from-zinc-300 to-zinc-400'], 1 => ['h-28', 'from-amber-300 to-amber-500'], 3 => ['h-14', 'from-orange-300 to-orange-500']];
@endphp
<div {{ $attributes }}>
    @if($byPosition->has(1) || $byPosition->has(2) || $byPosition->has(3))
        <div class="mx-auto grid max-w-xl grid-cols-[1fr_1.15fr_1fr] items-end gap-2 sm:gap-4">
            @foreach($steps as $position => [$height, $gradient])
                @php $row = $byPosition->get($position); @endphp
                <div class="text-center">
                    @if($row)
                        @if($position === 1)<span class="text-2xl" aria-hidden="true">🏆</span>@endif
                        @if($row['participant'])
                            <x-avatar logo :photo="$row['participant']->logoPath()" :name="$row['participant']->name()" class="mx-auto mb-1 h-10 w-10 rounded-full text-xs" />
                        @endif
                        <p @class(['mb-2 text-sm leading-tight', 'font-bold text-zinc-900' => $row['participant'], 'italic text-zinc-400' => ! $row['participant'], 'text-sky-club-dark' => $row['participant']?->isOwnTeam()])>{{ $row['text'] }}</p>
                    @endif
                    <div class="{{ $height }} flex items-center justify-center rounded-t-xl bg-linear-to-b {{ $gradient }} font-display text-3xl font-bold text-white">{{ $position }}</div>
                </div>
            @endforeach
        </div>
    @endif
    @if($rest->isNotEmpty())
        <ol class="mx-auto mt-4 max-w-xl divide-y divide-zinc-100 rounded-2xl bg-white px-4 ring-1 ring-zinc-200">
            @foreach($rest as $row)
                <li class="flex items-center gap-3 py-2.5">
                    <span class="w-8 font-display text-xl font-bold tabular-nums">{{ $row['position'] }}.</span>
                    @if($row['participant'])
                        <x-avatar logo :photo="$row['participant']->logoPath()" :name="$row['participant']->name()" class="h-7 w-7 shrink-0 rounded-full text-[10px]" />
                    @endif
                    <span @class(['font-semibold text-zinc-900' => $row['participant'], 'italic text-zinc-400' => ! $row['participant']])>{{ $row['text'] }}</span>
                </li>
            @endforeach
        </ol>
    @endif
</div>

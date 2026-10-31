@props(['trip', 'showParentInfo' => false, 'showGroup' => true])
{{-- Wyjazd na turniej: data jak kartka z kalendarza, miejsce, drużyny ze składami, wynik i (dla rodziców) szczegóły --}}
<article {{ $attributes->merge(['class' => 'flex gap-4 rounded-2xl bg-white p-4 ring-1 ring-zinc-200']) }}>
    <div class="flex w-14 shrink-0 flex-col self-start overflow-hidden rounded-xl text-center shadow-sm ring-1 ring-zinc-200">
        <span class="bg-club-navy py-0.5 text-[10px] font-bold tracking-widest text-white uppercase">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($trip->starts_on->translatedFormat('M'), 0, 3)) }}</span>
        <span class="bg-white py-1 font-display text-2xl leading-none font-bold text-zinc-900 tabular-nums">{{ $trip->starts_on->format('j') }}</span>
    </div>
    <div class="min-w-0 flex-1">
        <h3 class="font-semibold text-zinc-900">{{ $trip->title }}</h3>
        <p class="text-sm text-zinc-500">{{ collect([$trip->dateLabel(), $trip->city, $trip->venue, $trip->organizer])->filter()->implode(' · ') }}</p>
        @if($trip->description)<p class="mt-1 text-sm text-zinc-600">{{ $trip->description }}</p>@endif
        @foreach($trip->teams as $team)
            <div class="mt-3">
                <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-zinc-800">
                    {{ $team->name }}@if($showGroup && $team->group)<span class="font-normal text-zinc-500">· {{ $team->group->name }}</span>@endif
                    @if($team->place)
                        <span @class(['rounded-full px-2 py-0.5 text-xs font-bold', 'bg-amber-100 text-amber-800' => $team->place === 1, 'bg-zinc-200 text-zinc-700' => $team->place === 2, 'bg-orange-100 text-orange-800' => $team->place === 3, 'bg-sky-50 text-sky-800' => $team->place > 3])>{{ $team->place <= 3 ? ['🥇', '🥈', '🥉'][$team->place - 1].' ' : '' }}{{ $team->resultLabel() }}</span>
                    @endif
                </p>
                @if($site['mini_show_lineups'] && $team->players->isNotEmpty())<p class="text-sm text-zinc-600">{{ $team->players->map->displayName()->implode(', ') }}</p>@endif
                @if($team->note)<p class="text-sm text-zinc-500 italic">{{ $team->note }}</p>@endif
                @if($team->photoUrl())<img src="{{ $team->photoUrl() }}" alt="{{ $team->name }}, {{ $trip->title }}" loading="lazy" class="mt-2 max-h-64 rounded-xl object-cover">@endif
            </div>
        @endforeach
        @if($trip->tournament)
            <a href="{{ route('tournaments.show', $trip->tournament) }}" class="mt-2 inline-block text-sm font-semibold text-sky-club-dark hover:underline">{{ __('Pełne wyniki turnieju') }} →</a>
        @endif
        @if($showParentInfo && $trip->parent_info && ! $trip->isPast())
            <p class="mt-3 rounded-xl border border-dashed border-amber-400 bg-amber-50 px-3 py-2 text-sm whitespace-pre-line text-amber-900">🔒 {{ __('Dla rodziców') }}: {{ $trip->parent_info }}</p>
        @endif
    </div>
</article>

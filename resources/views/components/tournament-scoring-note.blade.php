@props(['groups', 'tournament', 'bracket' => null])
{{-- Punktacja i kryteria kolejności grup (wspólne albo osobno dla grup z własnymi zasadami), plus ręczne decyzje organizatora. --}}
@php
    use App\Support\TournamentTable;

    $notes = $groups->mapWithKeys(function ($group) use ($tournament) {
        $scoring = TournamentTable::scoring($group, $tournament);

        return [$group->name => __('Punktacja').': '.TournamentTable::describe($scoring).'. '.(($scoring['criteria'][0] ?? 'points') === 'points' ? __('Przy równej liczbie punktów') : __('Kolejność w tabeli')).': '.TournamentTable::describeCriteria($scoring).'.'];
    });
    $manual = $groups->filter(fn ($group) => filled($group->manual_order))->pluck('name');
@endphp
@if($notes->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'rounded-2xl bg-white px-5 py-4 text-xs leading-relaxed text-zinc-500 ring-1 ring-zinc-200']) }}>
        <p class="mb-1 font-semibold tracking-widest text-zinc-400 uppercase">{{ __('Zasady tabel grup') }}</p>
        @if($notes->unique()->count() === 1)
            <p>{{ $notes->first() }}</p>
        @else
            @foreach($notes as $groupName => $note)
                <p><span class="font-semibold text-zinc-700">{{ $groupName }}:</span> {{ $note }}</p>
            @endforeach
        @endif
        @if($manual->isNotEmpty())
            <p class="mt-1">{{ __('Kolejność ustalona przez organizatora (np. na podstawie małej tabeli)') }}: {{ $manual->implode(', ') }}.</p>
        @endif
    </div>
@endif

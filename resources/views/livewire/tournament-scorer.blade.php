@php
    $chip = 'shrink-0 rounded-full px-3.5 py-1.5 text-sm font-semibold';
    $on = 'bg-club-navy text-white';
    $off = 'bg-white text-zinc-700 ring-1 ring-zinc-200';
@endphp
<div class="container-site space-y-4 py-6">
    @if(count($days) > 1)
        <nav class="scrollbar-none -mx-4 flex gap-2 overflow-x-auto px-4" aria-label="{{ __('Dzień turnieju') }}">
            @foreach($days as $value)
                <button type="button" wire:click="$set('day', '{{ $value }}')" class="{{ $chip }} {{ $day === $value ? $on : $off }}">{{ \Illuminate\Support\Carbon::parse($value)->translatedFormat('D d.m') }}</button>
            @endforeach
        </nav>
    @endif
    @if($courts->count() > 1)
        <nav class="scrollbar-none -mx-4 flex gap-2 overflow-x-auto px-4" aria-label="{{ __('Boisko') }}">
            <button type="button" wire:click="$set('court', '')" class="{{ $chip }} {{ $court === '' ? $on : $off }}">{{ __('Wszystkie boiska') }}</button>
            @foreach($courts as $value)
                <button type="button" wire:click="$set('court', @js($value))" class="{{ $chip }} {{ $court === $value ? $on : $off }}">{{ $value }}</button>
            @endforeach
        </nav>
    @endif

    <p class="text-sm text-zinc-500">{{ __('Godziny w harmonogramie się nie zmieniają. Gdy mecz zacznie się później, kolejne mecze na tym boisku dostaną informację „możliwe opóźnienie”.') }}</p>

    <ul class="space-y-3">
        @forelse($matches as $match)
            @php
                $planned = $match->planned_starts_at ?? $match->starts_at;
                $finished = $match->isFinished();
                $live = $match->is_live;
            @endphp
            <li wire:key="score-{{ $match->id }}" @class(['rounded-2xl bg-white p-4 shadow-sm ring-1', 'ring-red-300' => $live, 'ring-zinc-200' => ! $live])>
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-500">
                    <span><span class="font-semibold text-zinc-800">{{ __('Mecz nr :n', ['n' => $match->number]) }}</span> · {{ $match->category?->name }}@if($match->group) · {{ $match->group->name }}@endif @if($match->title()) · {{ $match->title() }}@endif</span>
                    <span class="flex items-center gap-2">
                        @if($match->court)<span>{{ $match->court }}</span>@endif
                        <span>{{ __('wg planu') }} <span class="font-semibold text-zinc-800 tabular-nums">{{ $planned?->format('H:i') }}</span></span>
                        <x-match-delay :match="$match" />
                        @if($live)<span class="rounded-full bg-red-600 px-2 py-0.5 text-[10px] font-bold text-white uppercase">{{ __('trwa') }}</span>@endif
                        @if($finished)<span class="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white uppercase">{{ __('koniec') }}</span>@endif
                    </span>
                </div>
                <p class="mt-2 font-display text-xl leading-tight font-semibold text-zinc-900">{{ $match->homeName() }} <span class="text-zinc-400">–</span> {{ $match->awayName() }}
                    @if($match->home_sets !== null)<span class="ml-1 tabular-nums text-sky-club-dark">{{ $match->home_sets }}:{{ $match->away_sets }}</span>@endif
                </p>

                <div class="mt-3 grid gap-3 sm:grid-cols-[9rem_1fr]">
                    <div>
                        <label class="block text-xs font-medium text-zinc-600" for="start-{{ $match->id }}">{{ __('Rozpoczęcie') }}</label>
                        <x-time-input id="start-{{ $match->id }}" :model="'rows.'.$match->id.'.start'" :disabled="$live || $finished" class="mt-1 block w-full rounded-lg border border-zinc-300 text-base tabular-nums disabled:bg-zinc-50 disabled:text-zinc-500" />
                        @error("rows.{$match->id}.start")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    @if($live || $finished)
                        <div>
                            <label class="block text-xs font-medium text-zinc-600" for="sets-{{ $match->id }}">{{ __('Sety (gospodarze:goście)') }}</label>
                            <input id="sets-{{ $match->id }}" type="text" inputmode="numeric" wire:model="rows.{{ $match->id }}.sets" placeholder="25:20, 23:25, 15:12" class="mt-1 block w-full rounded-lg border border-zinc-300 text-base tabular-nums">
                            @error("rows.{$match->id}.sets")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endif
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    @if(! $live && ! $finished)
                        <button type="button" wire:click="start({{ $match->id }})" class="btn-primary">{{ __('Rozpocznij mecz') }}</button>
                    @elseif($live)
                        <button type="button" wire:click="save({{ $match->id }})" class="rounded-full px-4 py-2 text-sm font-semibold text-zinc-800 ring-1 ring-zinc-300">{{ __('Zapisz wynik') }}</button>
                        <button type="button" wire:click="save({{ $match->id }}, true)" wire:confirm="{{ __('Zakończyć mecz? Wynik trafi do tabeli i drabinki.') }}" class="btn-primary">{{ __('Zakończ mecz') }}</button>
                    @else
                        <button type="button" wire:click="save({{ $match->id }}, true)" class="rounded-full px-4 py-2 text-sm font-semibold text-zinc-800 ring-1 ring-zinc-300">{{ __('Popraw wynik') }}</button>
                        <button type="button" wire:click="reopen({{ $match->id }})" class="rounded-full px-4 py-2 text-sm font-semibold text-zinc-500">{{ __('Wznów mecz') }}</button>
                    @endif
                </div>
            </li>
        @empty
            <li class="rounded-2xl bg-white p-8 text-center text-sm text-zinc-500 ring-1 ring-zinc-200">{{ __('Brak meczów z godziną w harmonogramie tego dnia.') }}</li>
        @endforelse
    </ul>
</div>

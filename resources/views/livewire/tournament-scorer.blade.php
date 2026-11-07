@php
    $chip = 'shrink-0 rounded-full px-3.5 py-1.5 text-sm font-semibold';
    $on = 'bg-club-navy text-white';
    $off = 'bg-white text-zinc-700 ring-1 ring-zinc-200';
@endphp
<div class="container-site space-y-4 py-6"
    x-data="{ online: navigator.onLine, ping() { fetch(@js(route('keepalive')), { headers: { Accept: 'application/json' }, redirect: 'manual' }).then((response) => { if (response.status === 401 || response.type === 'opaqueredirect') window.location.reload() }).catch(() => {}) } }"
    x-init="setInterval(() => ping(), 5 * 60 * 1000)"
    x-on:online.window="online = true; ping()" x-on:offline.window="online = false"
    x-on:visibilitychange.document="document.visibilityState === 'visible' && ping()">
    {{-- Sesja odświeżana co 5 minut i po odblokowaniu telefonu; bez internetu czerwony pasek. --}}
    <p x-show="! online" x-cloak class="sticky top-16 z-20 rounded-xl bg-red-600 px-4 py-3 text-sm font-semibold text-white shadow-lg" role="alert">📵 {{ __('Brak internetu. Wpisane wyniki nie zapiszą się, dopóki połączenie nie wróci.') }}</p>
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
                <button type="button" wire:click="$set('court', @js($value))" class="{{ $chip }} {{ $court === $value ? $on : $off }} inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap"><x-court-icon :court="$value" :category="$categoryIds[$value] ?? null" class="rounded-[3px] bg-white" />{{ \App\Support\TournamentHalls::label($value, $categoryIds[$value] ?? null) }}</button>
            @endforeach
        </nav>
    @endif

    @if($categories->count() > 1)
        <nav class="scrollbar-none -mx-4 flex gap-2 overflow-x-auto px-4" aria-label="{{ __('Kategoria') }}">
            <button type="button" wire:click="$set('category', '')" class="{{ $chip }} {{ $category === '' ? $on : $off }}">{{ __('Wszystkie kategorie') }}</button>
            @foreach($categories as $option)
                <button type="button" wire:click="$set('category', '{{ $option->id }}')" class="{{ $chip }} {{ $category === (string) $option->id ? $on : $off }} inline-flex items-center gap-1.5 whitespace-nowrap"><span class="size-2.5 rounded-full" style="background: {{ $option->color() }}"></span>{{ $option->name }}</button>
            @endforeach
        </nav>
    @endif

    <p class="text-sm text-zinc-500">{{ __('Godziny w harmonogramie się nie zmieniają. Gdy mecz zacznie się później, kolejne mecze na tym boisku dostaną informację „możliwe opóźnienie”.') }}</p>

    {{-- Na szerszym ekranie (laptop, tablet poziomo) dwa mecze obok siebie; trwające są na górze, więc stoją razem. --}}
    <ul class="grid grid-cols-1 items-start gap-3 lg:grid-cols-2">
        @forelse($matches as $match)
            @php
                $planned = $match->planned_starts_at ?? $match->starts_at;
                $finished = $match->isFinished();
                $live = $match->is_live;
            @endphp
            <li wire:key="score-{{ $match->id }}" @class(['rounded-2xl bg-white p-4 shadow-sm ring-1', 'ring-red-300' => $live, 'ring-zinc-200' => ! $live])>
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-500">
                    {{-- Kategoria w kolorze na początku: te same kluby grają w kilku kategoriach --}}
                    <span class="flex flex-wrap items-center gap-x-1.5 gap-y-1"><span class="rounded-md px-2 py-0.5 text-xs font-bold text-white" style="background: {{ $match->category?->color() }}">{{ $match->category?->name }}</span> <span class="font-semibold text-zinc-800">{{ __('Mecz nr :n', ['n' => $match->number]) }}</span>@if($match->group && mb_strtolower($match->group->name) !== mb_strtolower((string) $match->category?->name)) · {{ $match->group->name }}@endif @if($match->title() && $match->title() !== $match->group?->name) · {{ $match->title() }}@endif</span>
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
                        <div class="flex items-center justify-between gap-2">
                            <label class="block text-xs font-medium text-zinc-600" for="start-{{ $match->id }}">{{ __('Rozpoczęcie') }}</label>
                            @unless($finished)
                                <button type="button" x-on:click="$wire.set('rows.{{ $match->id }}.start', new Date().toLocaleTimeString('pl-PL', { timeZone: @js(config('app.timezone')), hour: '2-digit', minute: '2-digit', hour12: false }))" class="text-xs font-semibold text-sky-club-dark hover:underline">{{ __('Teraz') }}</button>
                            @endunless
                        </div>
                        <x-time-input id="start-{{ $match->id }}" :model="'rows.'.$match->id.'.start'" :disabled="$finished" class="mt-1 block w-full rounded-lg border border-zinc-300 text-base tabular-nums disabled:bg-zinc-50 disabled:text-zinc-500" />
                        <p class="mt-1 text-[11px] leading-snug text-zinc-500">{{ $live ? __('Pomyłka w godzinie? Popraw i kliknij „Zapisz wynik”.') : __('Godzina z harmonogramu. Gdy mecz zaczął się później, wpisz właściwą.') }}</p>
                        @error("rows.{$match->id}.start")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    @if($live || $finished)
                        <div class="sm:col-span-2">
                            <p class="text-xs font-medium text-zinc-600">{{ __('Sety') }}</p>
                            <x-sets-input wire:key="sets-{{ $match->id }}" :model="'rows.'.$match->id.'.sets'" :home="$match->homeName()" :away="$match->awayName()" :live="$live" class="mt-1 max-w-sm" />
                            @if($live)
                                <p class="mt-1.5 max-w-sm text-[11px] leading-snug text-zinc-500">{{ __('Wpisuj też set, który trwa, i co kilka punktów klikaj „Zapisz wynik”. Set do 25 z 2 pkt przewagi kończy się sam, krótszy (tie-break) dopiero z następnym setem albo końcem meczu.') }}</p>
                                @if($match->currentSetLabel())<p class="mt-1 text-xs font-semibold text-red-600">{{ __('Kibice widzą') }}: {{ $match->home_sets ?? 0 }}:{{ $match->away_sets ?? 0 }} · {{ $match->currentSetLabel() }}</p>@endif
                            @endif
                            @error("rows.{$match->id}.sets")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-zinc-600" for="mvp-{{ $match->id }}">{{ __('Wyróżnienie (opcjonalnie)') }}</label>
                            <input id="mvp-{{ $match->id }}" type="text" maxlength="120" wire:model="rows.{{ $match->id }}.mvp" placeholder="{{ __('np. MVP: Jan Kowalski') }}" class="mt-1 block w-full rounded-lg border border-zinc-300 text-base placeholder:text-zinc-300">
                        </div>
                    @endif
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    @if(! $live && ! $finished)
                        <button type="button" wire:click="start({{ $match->id }})" class="btn-primary">{{ __('Rozpocznij mecz') }}</button>
                    @elseif($live)
                        <button type="button" wire:click="save({{ $match->id }})" class="rounded-full px-4 py-2 text-sm font-semibold text-zinc-800 ring-1 ring-zinc-300">{{ __('Zapisz wynik') }}</button>
                        <button type="button" wire:click="save({{ $match->id }}, true)" wire:confirm="{{ __('Zakończyć mecz? Wynik trafi do tabeli i drabinki.') }}" class="btn-primary">{{ __('Zakończ mecz') }}</button>
                        {{-- Pomyłka: mecz wraca do nierozpoczętych, z godziną z harmonogramu --}}
                        <button type="button" wire:click="cancelStart({{ $match->id }})" wire:confirm="{{ __('Cofnąć rozpoczęcie? Mecz wróci do nierozpoczętych, a wpisane sety zostaną usunięte.') }}" class="ml-auto rounded-full px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">{{ __('Cofnij rozpoczęcie') }}</button>
                    @else
                        <button type="button" wire:click="save({{ $match->id }}, true)" class="rounded-full px-4 py-2 text-sm font-semibold text-zinc-800 ring-1 ring-zinc-300">{{ __('Popraw wynik') }}</button>
                        <button type="button" wire:click="reopen({{ $match->id }})" class="rounded-full px-4 py-2 text-sm font-semibold text-zinc-500">{{ __('Wznów mecz') }}</button>
                    @endif
                </div>
            </li>
        @empty
            <li class="rounded-2xl bg-white p-8 text-center lg:col-span-2 text-sm text-zinc-500 ring-1 ring-zinc-200">{{ __('Brak meczów z godziną w harmonogramie tego dnia.') }}</li>
        @endforelse
    </ul>
</div>

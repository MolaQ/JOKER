@php
    $field = 'mt-1 block w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-base shadow-sm focus:border-sky-club focus:ring-sky-club';
    $labelClass = 'block text-sm font-semibold text-zinc-800';
@endphp
<div class="mx-auto max-w-3xl">
    <p class="rounded-2xl bg-sky-50 px-4 py-3 text-sm text-sky-950 ring-1 ring-sky-100">
        @if($publishes)
            {{ __('Masz uprawnienie do minisiatkówki: turnieje i wyniki zapisują się od razu. Relacje czekają w „Propozycjach treści”.') }}
        @else
            {{ __('Zgłoszenia trafiają do zatwierdzenia. Na stronie pojawią się, gdy administrator je przejrzy.') }}
        @endif
    </p>

    <nav class="scrollbar-none mt-6 flex gap-1 overflow-x-auto rounded-2xl bg-zinc-100 p-1" role="tablist">
        @foreach(['przeglad' => __('Przegląd'), 'turniej' => '＋ '.__('Nowy turniej'), 'wyniki' => __('Skład i wyniki'), 'relacja' => __('Relacja'), 'dzieci' => __('Dzieci')] as $key => $name)
            <button type="button" role="tab" aria-selected="{{ $section === $key ? 'true' : 'false' }}" wire:click="$set('section', '{{ $key }}')" @class(['flex-1 shrink-0 rounded-xl px-3 py-2.5 text-sm font-semibold whitespace-nowrap transition', 'bg-white text-zinc-900 shadow' => $section === $key, 'text-zinc-600 hover:text-zinc-900' => $section !== $key])>{{ $name }}</button>
        @endforeach
    </nav>

    {{-- Przegląd: zaplanowane turnieje z reprezentacją w kategoriach i wyniki zakończonych; szukanie i filtry --}}
    @if($section === 'przeglad')
        <div class="mt-6 space-y-3">
            <div class="relative">
                <svg class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-zinc-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd"/></svg>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Szukaj turnieju albo miejscowości') }}" aria-label="{{ __('Szukaj turnieju') }}" class="{{ $field }} mt-0 pl-11">
            </div>
            <div class="scrollbar-none -mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
                @foreach(['' => __('Wszystkie'), 'upcoming' => __('Zaplanowane'), 'past' => __('Zakończone')] as $when => $whenLabel)
                    <button type="button" wire:click="$set('filterWhen', '{{ $when }}')" @class(['shrink-0 rounded-full px-3.5 py-1.5 text-sm font-semibold ring-1 transition', 'bg-club-navy text-white ring-club-navy' => $filterWhen === $when, 'bg-white text-zinc-700 ring-zinc-200 hover:bg-zinc-50' => $filterWhen !== $when])>{{ $whenLabel }}</button>
                @endforeach
                <span class="mx-1 w-px shrink-0 bg-zinc-200" aria-hidden="true"></span>
                <button type="button" wire:click="$set('filterGroup', '')" @class(['shrink-0 rounded-full px-3.5 py-1.5 text-sm font-semibold ring-1 transition', 'bg-sky-club text-white ring-sky-club' => $filterGroup === '', 'bg-white text-zinc-700 ring-zinc-200 hover:bg-zinc-50' => $filterGroup !== ''])>{{ __('Wszystkie kategorie') }}</button>
                @foreach($groups as $groupOption)
                    <button type="button" wire:click="$set('filterGroup', '{{ $groupOption->id }}')" @class(['inline-flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-semibold ring-1 transition', 'bg-sky-club text-white ring-sky-club' => $filterGroup === (string) $groupOption->id, 'bg-white text-zinc-700 ring-zinc-200 hover:bg-zinc-50' => $filterGroup !== (string) $groupOption->id])>
                        <x-mini-players :group="$groupOption" size="xs" aria-hidden="true" />{{ $groupOption->name }}
                    </button>
                @endforeach
            </div>
        </div>

        @foreach(['upcoming' => __('Zaplanowane turnieje'), 'past' => __('Zakończone turnieje')] as $kind => $heading)
            @if($overview[$kind]->isNotEmpty() || $filterWhen === $kind)
                <h3 class="mt-8 flex items-center gap-2 font-display text-xl font-bold text-zinc-900 uppercase">{{ $heading }} <span class="text-sm font-normal text-zinc-500">({{ $overview[$kind]->count() }})</span></h3>
                <div class="mt-3 space-y-3">
                    @forelse($overview[$kind] as $tripCard)
                        <article wire:key="trip-card-{{ $tripCard->id }}" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-zinc-200 sm:p-5">
                            <div class="flex gap-4">
                                <div class="flex w-14 shrink-0 flex-col self-start overflow-hidden rounded-xl text-center shadow-sm ring-1 ring-zinc-200">
                                    <span class="bg-club-navy py-0.5 text-[10px] font-bold tracking-widest text-white uppercase">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($tripCard->starts_on->translatedFormat('M'), 0, 3)) }}</span>
                                    <span class="bg-white py-1 font-display text-2xl leading-none font-bold text-zinc-900 tabular-nums">{{ $tripCard->starts_on->format('j') }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-zinc-900">{{ $tripCard->title }}
                                        @if($tripCard->status === \App\Models\MiniTrip::STATUS_PENDING)<span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 align-middle text-[11px] font-semibold text-amber-800">{{ __('czeka na zatwierdzenie') }}</span>@endif
                                        @if($tripCard->pending_results)<span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 align-middle text-[11px] font-semibold text-amber-800">{{ __('wyniki czekają na zatwierdzenie') }}</span>@endif
                                    </p>
                                    <p class="text-sm text-zinc-500">{{ collect([$tripCard->dateLabel(), $tripCard->city, $tripCard->venue])->filter()->implode(' · ') }}</p>
                                </div>
                            </div>
                            {{-- Reprezentacja: drużyny w kategoriach, liczba dzieci w składzie i miejsce --}}
                            <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                                @forelse($tripCard->teams as $team)
                                    <li class="flex items-center gap-2 rounded-xl bg-zinc-50 px-3 py-2 text-sm ring-1 ring-zinc-100">
                                        @if($team->group)<x-mini-players :group="$team->group" size="xs" aria-hidden="true" />@endif
                                        <span class="min-w-0 flex-1 truncate"><span class="font-semibold text-zinc-800">{{ $team->name }}</span>@if($team->group)<span class="text-zinc-500"> · {{ $team->group->name }}</span>@endif</span>
                                        @if($team->place)
                                            <span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800">{{ $team->place <= 3 ? ['🥇', '🥈', '🥉'][$team->place - 1].' ' : '' }}{{ $team->resultLabel() }}</span>
                                        @elseif($team->players->isNotEmpty())
                                            <span class="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700" title="{{ $team->players->map->name()->implode(', ') }}">{{ trans_choice(':count dziecko|:count dzieci|:count dzieci', $team->players->count(), ['count' => $team->players->count()]) }}</span>
                                        @else
                                            <span class="shrink-0 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">{{ __('brak składu') }}</span>
                                        @endif
                                    </li>
                                @empty
                                    <li class="text-sm text-zinc-500">{{ __('Bez drużyn: dodaj je w „Skład i wyniki”.') }}</li>
                                @endforelse
                            </ul>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" wire:click="openTrip({{ $tripCard->id }}, 'wyniki')" class="btn-primary px-4 py-2 text-sm">{{ $kind === 'upcoming' ? __('Ustal skład') : ($tripCard->teams->contains(fn ($team) => $team->place) ? __('Popraw wyniki') : __('Wpisz wyniki')) }}</button>
                                @if($kind === 'past')<button type="button" wire:click="openTrip({{ $tripCard->id }}, 'relacja')" class="btn bg-white px-4 py-2 text-sm text-zinc-800 ring-1 ring-zinc-300 hover:bg-zinc-50">{{ __('Napisz relację') }}</button>@endif
                            </div>
                        </article>
                    @empty
                        <p class="rounded-2xl bg-white px-4 py-6 text-center text-sm text-zinc-500 ring-1 ring-zinc-200">{{ __('Brak turniejów dla tych filtrów.') }}</p>
                    @endforelse
                </div>
            @endif
        @endforeach
        @if($overview['upcoming']->isEmpty() && $overview['past']->isEmpty() && $filterWhen === '')
            <div class="mt-6 rounded-2xl bg-white px-4 py-8 text-center ring-1 ring-zinc-200">
                <p class="text-sm text-zinc-500">{{ $search !== '' || $filterGroup !== '' ? __('Brak turniejów dla tych filtrów.') : __('Nie ma jeszcze turniejów.') }}</p>
                <button type="button" wire:click="$set('section', 'turniej')" class="btn-primary mt-3 px-4 py-2 text-sm">＋ {{ __('Dodaj turniej') }}</button>
            </div>
        @endif
    @endif

    @if($section === 'turniej')
        <form wire:submit="saveTrip" class="card mt-6 space-y-4 p-5 sm:p-6">
            <div>
                <label class="{{ $labelClass }}" for="mini-trip-title">{{ __('Nazwa turnieju') }}</label>
                <input id="mini-trip-title" type="text" wire:model="trip.title" class="{{ $field }}" placeholder="{{ __('np. Kinder+Sport – faza powiatowa') }}">
                @error('trip.title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $labelClass }}" for="mini-trip-date">{{ __('Data') }}</label>
                    <input id="mini-trip-date" type="date" wire:model="trip.starts_on" class="{{ $field }}">
                    @error('trip.starts_on') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="mini-trip-end">{{ __('Do (gdy kilka dni)') }}</label>
                    <input id="mini-trip-end" type="date" wire:model="trip.ends_on" class="{{ $field }}">
                    @error('trip.ends_on') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="mini-trip-city">{{ __('Miejscowość') }}</label>
                    <input id="mini-trip-city" type="text" wire:model="trip.city" class="{{ $field }}" placeholder="{{ __('np. Piła') }}">
                    @error('trip.city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="mini-trip-venue">{{ __('Hala (opcjonalnie)') }}</label>
                    <input id="mini-trip-venue" type="text" wire:model="trip.venue" class="{{ $field }}" placeholder="{{ __('np. Hala Lotnisko') }}">
                </div>
            </div>
            <fieldset>
                <legend class="{{ $labelClass }}">{{ __('Kto gra') }}</legend>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach($groups as $groupOption)
                        <label class="cursor-pointer">
                            <input type="checkbox" wire:model="tripGroups" value="{{ $groupOption->id }}" class="peer sr-only">
                            <span class="inline-block rounded-full px-4 py-2 text-sm font-semibold ring-1 ring-zinc-300 peer-checked:bg-club-navy peer-checked:text-white peer-checked:ring-club-navy peer-focus-visible:outline-2">{{ $groupOption->name }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-1 text-xs text-zinc-500">{{ __('Składy i miejsca wpiszesz po turnieju w zakładce „Wyniki”.') }}</p>
            </fieldset>
            <button type="submit" class="btn-primary w-full sm:w-auto">{{ $publishes ? __('Dodaj do kalendarza') : __('Wyślij do zatwierdzenia') }}</button>
        </form>
    @endif

    @if($section === 'wyniki')
        <form wire:submit="saveResults" class="card mt-6 space-y-5 p-5 sm:p-6">
            <div>
                <label class="{{ $labelClass }}" for="mini-results-trip">{{ __('Turniej') }}</label>
                <select id="mini-results-trip" wire:model.live="resultsTrip" class="{{ $field }}">
                    <option value="">{{ __('wybierz turniej') }}</option>
                    @foreach([__('Zaplanowane (ustal skład)') => $trips->reject->isPast()->sortBy('starts_on'), __('Zakończone (wpisz wyniki)') => $trips->filter->isPast()] as $optLabel => $optTrips)
                        @if($optTrips->isNotEmpty())
                            <optgroup label="{{ $optLabel }}">
                                @foreach($optTrips as $tripOption)
                                    <option value="{{ $tripOption->id }}">{{ $tripOption->starts_on->format('d.m') }} · {{ $tripOption->title }}{{ $tripOption->city ? ' · '.$tripOption->city : '' }}{{ $tripOption->status === \App\Models\MiniTrip::STATUS_PENDING ? ' ('.__('czeka na zatwierdzenie').')' : '' }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
                @error('resultsTrip') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @if($trips->isEmpty())<p class="mt-1 text-sm text-zinc-500">{{ __('Najpierw dodaj turniej w „Nowy turniej”.') }}</p>@endif
                @if($selectedTrip && ! $selectedTrip->isPast())<p class="mt-2 rounded-xl bg-sky-50 px-3 py-2 text-sm text-sky-950 ring-1 ring-sky-100">{{ __('Turniej jeszcze się nie odbył: zaznacz dzieci, które jadą. Skład pokaże się w terminarzu i na stronie grupy. Miejsce wpiszesz po turnieju.') }}</p>@endif
            </div>

            @foreach($rows as $index => $row)
                @php $rowGroup = $groups->firstWhere('id', (int) $row['group']); @endphp
                <fieldset wire:key="mini-row-{{ $index }}" class="rounded-2xl bg-zinc-50 p-4 ring-1 ring-zinc-200">
                    <div class="flex items-center justify-between gap-3">
                        <legend class="font-display text-lg font-semibold uppercase">{{ $row['name'] ?: __('Drużyna') }}</legend>
                        <button type="button" wire:click="removeRow({{ $index }})" wire:confirm="{{ __('Usunąć tę drużynę z wyników?') }}" class="text-sm text-red-600 hover:text-red-800">{{ __('Usuń') }}</button>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="col-span-2">
                            <label class="{{ $labelClass }}">{{ __('Kategoria') }}</label>
                            <select wire:model.live="rows.{{ $index }}.group" class="{{ $field }}">
                                <option value="">—</option>
                                @foreach($groups as $groupOption)
                                    <option value="{{ $groupOption->id }}">{{ $groupOption->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="{{ $labelClass }}">{{ __('Nazwa drużyny') }}</label>
                            <input type="text" wire:model.blur="rows.{{ $index }}.name" class="{{ $field }}">
                            @error("rows.{$index}.name") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        @if(! $selectedTrip || $selectedTrip->isPast())
                        <div>
                            <label class="{{ $labelClass }}">{{ __('Miejsce') }}</label>
                            <input type="number" inputmode="numeric" min="1" wire:model="rows.{{ $index }}.place" class="{{ $field }} text-center font-display text-2xl font-bold" placeholder="1">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">{{ $rowGroup?->players_on_court === 1 ? __('Na ilu zawodników') : __('Na ile drużyn') }}</label>
                            <input type="number" inputmode="numeric" min="1" wire:model="rows.{{ $index }}.teams_count" class="{{ $field }} text-center font-display text-2xl font-bold" placeholder="12">
                        </div>
                        @endif
                    </div>
                    @php $children = $rowGroup ? $rowGroup->players : $groups->flatMap->players; @endphp
                    @if($children->isNotEmpty())
                        <p class="mt-4 {{ $labelClass }}">{{ __('Dzieci w składzie') }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($children as $child)
                                <label class="cursor-pointer">
                                    <input type="checkbox" value="{{ $child->id }}" wire:model="rows.{{ $index }}.players" class="peer sr-only">
                                    <span class="inline-flex items-center rounded-full bg-white px-3 py-1.5 text-sm text-zinc-700 ring-1 ring-zinc-300 peer-checked:bg-sky-club peer-checked:text-white peer-checked:ring-sky-club">{{ $child->name() }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </fieldset>
            @endforeach

            @if($resultsTrip !== '')
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" wire:click="addRow" class="btn bg-white text-zinc-800 ring-1 ring-zinc-300 hover:bg-zinc-50">+ {{ __('Dodaj drużynę') }}</button>
                    <button type="submit" class="btn-primary">{{ $publishes ? ($selectedTrip && ! $selectedTrip->isPast() ? __('Zapisz skład') : __('Zapisz wyniki')) : __('Wyślij do zatwierdzenia') }}</button>
                </div>
            @endif
        </form>
    @endif

    @if($section === 'relacja')
        {{-- Relacja jak artykuł w aktualnościach: tytuł, lead, treść z @oznaczeniami i zdjęcie w kadrze aktualności --}}
        <form wire:submit="sendReport" class="card mt-6 space-y-5 p-5 sm:p-6">
            <div>
                <label class="{{ $labelClass }}" for="mini-report-trip">{{ __('Turniej') }}</label>
                <select id="mini-report-trip" wire:model="reportTrip" class="{{ $field }}">
                    <option value="">{{ __('wybierz turniej') }}</option>
                    @foreach($trips as $tripOption)
                        <option value="{{ $tripOption->id }}">{{ $tripOption->starts_on->format('d.m') }} · {{ $tripOption->title }}{{ $tripOption->city ? ' · '.$tripOption->city : '' }}</option>
                    @endforeach
                </select>
                @error('reportTrip') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-2xl bg-zinc-50 p-4 ring-1 ring-zinc-200">
                <label class="text-xs font-semibold tracking-wider text-zinc-500 uppercase" for="mini-report-title">{{ __('Tytuł') }}</label>
                <input id="mini-report-title" type="text" wire:model="reportTitle" maxlength="160" class="mt-1 block w-full border-0 border-b-2 border-zinc-300 bg-transparent px-0 py-2 font-display text-2xl font-bold text-zinc-900 uppercase placeholder:text-zinc-300 placeholder:normal-case focus:border-sky-club focus:ring-0 sm:text-3xl" placeholder="{{ __('np. Złoto czwórek w Wałczu!') }}">
                @error('reportTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                <p class="mt-5 text-xs font-semibold tracking-wider text-zinc-500 uppercase">{{ __('Lead') }} <span class="font-normal normal-case">· {{ __('2–3 zdania na kafelek w aktualnościach i wstęp artykułu') }}</span></p>
                <div class="mt-1"><x-rich-text-editor model="reportLead" compact :live="false" placeholder="{{ __('Najważniejsze w skrócie: kto grał, gdzie i z jakim wynikiem.') }}" height="min-h-[80px]" /></div>
                @error('reportLead') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                <p class="mt-5 text-xs font-semibold tracking-wider text-zinc-500 uppercase">{{ __('Rozwinięcie') }} <span class="font-normal normal-case">· {{ __('wpisz @ i początek imienia, żeby oznaczyć dziecko, rodzica albo trenera') }}</span></p>
                <div class="mt-1"><x-rich-text-editor model="reportContent" :mentions="route('mini.mentions')" :live="false" placeholder="{{ __('Jak poszło, kto się wyróżnił, podziękowania dla rodziców…') }}" height="min-h-[220px]" /></div>
                @error('reportContent') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="{{ $labelClass }}">{{ __('Zdjęcie główne (nieobowiązkowe)') }}</p>
                <x-photo-cropper model="reportPhoto" class="mt-2" aspect="16/10" :width="2000" :pending="$reportPhoto && ! $errors->has('reportPhoto') ? $reportPhoto->temporaryUrl() : null" :label="$reportPhoto ? __('Zmień zdjęcie') : __('Wybierz zdjęcie')" :hint="__('Kadr 16:10 jak w aktualnościach. Przesuń i powiększ zdjęcie, a potem kliknij „Użyj tego kadru”.')" />
                @error('reportPhoto') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <p class="text-xs text-zinc-500">{{ __('Po zatwierdzeniu relacja trafi do aktualności, a przy turnieju na stronie grupy pojawi się link do niej. Dzieci oznaczone przez @ widać tak jak na stronie grupy, bez profilu.') }}</p>
            <button type="submit" class="btn-primary w-full sm:w-auto">{{ __('Wyślij do zatwierdzenia') }}</button>
        </form>
    @endif
    @if($section === 'dzieci')
        <div class="card mt-6 space-y-5 p-5 sm:p-6">
            <form wire:submit="addChild" class="space-y-4">
                <p class="text-sm text-zinc-600">{{ __('Na stronie dziecko ma tylko imię, nazwisko i rocznik, bez profilu i bez linku. Zdjęcie dodaje administrator po zgodzie rodzica.') }}</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}" for="mini-child-first">{{ __('Imię') }}</label>
                        <input id="mini-child-first" type="text" wire:model="child.first_name" class="{{ $field }}" autocomplete="off">
                        @error('child.first_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="mini-child-last">{{ __('Nazwisko') }}</label>
                        <input id="mini-child-last" type="text" wire:model="child.last_name" class="{{ $field }}" autocomplete="off">
                        @error('child.last_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="mini-child-year">{{ __('Rocznik (opcjonalnie)') }}</label>
                        <input id="mini-child-year" type="number" inputmode="numeric" wire:model="child.birth_year" class="{{ $field }}" placeholder="{{ now()->year - 10 }}">
                        @error('child.birth_year') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="mini-child-number">{{ __('Numer na koszulce (opcjonalnie)') }}</label>
                        <input id="mini-child-number" type="number" inputmode="numeric" min="0" max="99" wire:model="child.number" class="{{ $field }}" placeholder="{{ __('np. 7') }}">
                        @error('child.number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="mini-child-group">{{ __('Grupa') }}</label>
                        <select id="mini-child-group" wire:model="child.group" class="{{ $field }}">
                            <option value="">{{ __('wybierz') }}</option>
                            @foreach($groups as $groupOption)<option value="{{ $groupOption->id }}">{{ $groupOption->name }}</option>@endforeach
                        </select>
                        @error('child.group') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <button type="submit" class="btn-primary w-full sm:w-auto">{{ __('Dopisz dziecko') }}</button>
            </form>

            <p class="border-t border-zinc-100 pt-4 text-xs text-zinc-500">{{ __('Przy każdym dziecku: lista „grupa” przenosi je do innej kategorii (np. z dwójek do trójek), a „Nie trenuje już” usuwa je z listy, gdy skończyło treningi. Wyniki dawnych turniejów zostają.') }}</p>
            @foreach($groups as $groupOption)
                <div wire:key="kids-{{ $groupOption->id }}" class="border-t border-zinc-100 pt-4">
                    <h3 class="flex items-center gap-2 font-display text-lg font-semibold uppercase"><x-mini-players :group="$groupOption" size="xs" /> {{ $groupOption->name }} <span class="text-sm font-normal text-zinc-500">({{ $groupOption->players->count() }})</span></h3>
                    <ul class="mt-2 divide-y divide-zinc-100">
                        @forelse($groupOption->players as $kid)
                            <li wire:key="kid-{{ $kid->id }}" class="flex flex-wrap items-center gap-2 py-2 text-sm">
                                <span class="min-w-0 flex-1 font-medium">@if($kid->number !== null)<span class="mr-1 font-bold text-sky-club-dark">#{{ $kid->number }}</span>@endif{{ $kid->first_name }} {{ $kid->last_name }}@if($kid->birth_year) <span class="text-zinc-500">· {{ $kid->birth_year }}</span>@endif</span>
                                <select wire:change="moveChild({{ $kid->id }}, $event.target.value)" class="rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs" aria-label="{{ __('Przenieś do grupy') }}">
                                    @foreach($groups as $target)<option value="{{ $target->id }}" @selected($target->id === $groupOption->id)>{{ $target->name }}</option>@endforeach
                                </select>
                                <button type="button" wire:click="retireChild({{ $kid->id }})" wire:confirm="{{ __('Dziecko nie trenuje już w klubie? Zniknie z listy grupy i z wyboru do składów, ale zostanie w wynikach dawnych turniejów.') }}" title="{{ __('Gdy dziecko przestało trenować') }}" class="rounded-lg px-2 py-1 text-xs font-semibold text-red-600 ring-1 ring-red-200 hover:bg-red-50">{{ __('Nie trenuje już') }}</button>
                            </li>
                        @empty
                            <li class="py-2 text-sm text-zinc-500">{{ __('Brak dzieci w tej grupie.') }}</li>
                        @endforelse
                    </ul>
                </div>
            @endforeach
        </div>
    @endif
</div>

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

    <nav class="mt-6 grid grid-cols-4 gap-1 rounded-2xl bg-zinc-100 p-1" role="tablist">
        @foreach(['turniej' => __('Turniej'), 'wyniki' => __('Wyniki'), 'relacja' => __('Relacja'), 'dzieci' => __('Dzieci')] as $key => $name)
            <button type="button" role="tab" wire:click="$set('section', '{{ $key }}')" @class(['rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-white text-zinc-900 shadow' => $section === $key, 'text-zinc-600 hover:text-zinc-900' => $section !== $key])>{{ $name }}</button>
        @endforeach
    </nav>

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
                    @foreach($trips as $tripOption)
                        <option value="{{ $tripOption->id }}">{{ $tripOption->starts_on->format('d.m') }} · {{ $tripOption->title }}{{ $tripOption->city ? ' · '.$tripOption->city : '' }}{{ $tripOption->status === \App\Models\MiniTrip::STATUS_PENDING ? ' ('.__('czeka na zatwierdzenie').')' : '' }}</option>
                    @endforeach
                </select>
                @error('resultsTrip') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @if($trips->isEmpty())<p class="mt-1 text-sm text-zinc-500">{{ __('Najpierw dodaj turniej. Wyniki wpiszesz w dniu turnieju albo później.') }}</p>@endif
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
                        <div>
                            <label class="{{ $labelClass }}">{{ __('Miejsce') }}</label>
                            <input type="number" inputmode="numeric" min="1" wire:model="rows.{{ $index }}.place" class="{{ $field }} text-center font-display text-2xl font-bold" placeholder="1">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">{{ $rowGroup?->players_on_court === 1 ? __('Na ilu zawodników') : __('Na ile drużyn') }}</label>
                            <input type="number" inputmode="numeric" min="1" wire:model="rows.{{ $index }}.teams_count" class="{{ $field }} text-center font-display text-2xl font-bold" placeholder="12">
                        </div>
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
                    <button type="submit" class="btn-primary">{{ $publishes ? __('Zapisz wyniki') : __('Wyślij do zatwierdzenia') }}</button>
                </div>
            @endif
        </form>
    @endif

    @if($section === 'relacja')
        <form wire:submit="sendReport" class="card mt-6 space-y-4 p-5 sm:p-6">
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
            <div>
                <label class="{{ $labelClass }}" for="mini-report-title">{{ __('Tytuł') }}</label>
                <input id="mini-report-title" type="text" wire:model="reportTitle" class="{{ $field }}" placeholder="{{ __('np. Złoto czwórek w Wałczu!') }}">
                @error('reportTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="{{ $labelClass }}" for="mini-report-content">{{ __('Relacja') }}</label>
                <textarea id="mini-report-content" wire:model="reportContent" rows="7" class="{{ $field }}" placeholder="{{ __('Jak poszło, kto się wyróżnił, podziękowania dla rodziców…') }}"></textarea>
                @error('reportContent') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="{{ $labelClass }}" for="mini-report-photos">{{ __('Zdjęcie na górę relacji (jedno, nieobowiązkowe)') }}</label>
                <input id="mini-report-photos" type="file" wire:model="reportPhoto" accept="image/*" class="mt-1 block w-full text-sm">
                @error('reportPhoto') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
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

            @foreach($groups as $groupOption)
                <div wire:key="kids-{{ $groupOption->id }}" class="border-t border-zinc-100 pt-4">
                    <h3 class="flex items-center gap-2 font-display text-lg font-semibold uppercase"><x-mini-players :group="$groupOption" size="xs" /> {{ $groupOption->name }} <span class="text-sm font-normal text-zinc-500">({{ $groupOption->players->count() }})</span></h3>
                    <ul class="mt-2 divide-y divide-zinc-100">
                        @forelse($groupOption->players as $kid)
                            <li wire:key="kid-{{ $kid->id }}" class="flex flex-wrap items-center gap-2 py-2 text-sm">
                                <span class="min-w-0 flex-1 font-medium">{{ $kid->first_name }} {{ $kid->last_name }}@if($kid->birth_year) <span class="text-zinc-500">· {{ $kid->birth_year }}</span>@endif</span>
                                <select wire:change="moveChild({{ $kid->id }}, $event.target.value)" class="rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs" aria-label="{{ __('Przenieś do grupy') }}">
                                    @foreach($groups as $target)<option value="{{ $target->id }}" @selected($target->id === $groupOption->id)>{{ $target->name }}</option>@endforeach
                                </select>
                                <button type="button" wire:click="retireChild({{ $kid->id }})" wire:confirm="{{ __('Zdjąć dziecko z listy grupy? Zostanie w składach z dawnych turniejów.') }}" class="text-xs text-red-600 hover:underline">{{ __('Zdejmij') }}</button>
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

@php
    $input = 'w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500';
    $label = 'mb-1 block text-xs font-semibold text-gray-600';
    $card = 'rounded-xl bg-white p-4 ring-1 ring-gray-200';
    $toggle = 'inline-flex items-center gap-2 text-sm text-gray-700';
@endphp
<div class="grid gap-6 xl:grid-cols-[minmax(0,26rem)_minmax(0,1fr)]">
    {{-- USTAWIENIA --}}
    <div class="min-w-0 space-y-4">
        {{-- MECZ --}}
        <section class="{{ $card }} space-y-3">
            <h2 class="text-sm font-bold tracking-wide text-gray-900 uppercase">1. Mecz</h2>
            <div>
                <label class="{{ $label }}" for="ytt-pick">Z bazy meczów albo ręcznie</label>
                <select id="ytt-pick" wire:model.live="pick" class="{{ $input }}">
                    <option value="">Wpiszę ręcznie</option>
                    @if($games->isNotEmpty())
                        <optgroup label="Mecze klubu">
                            @foreach($games as $game)
                                <option value="game:{{ $game->id }}">{{ $game->game_date?->format('d.m') }} · {{ implode(' – ', $game->sidesNames()) }}@if($game->competition) · {{ $game->competition->name }}@endif</option>
                            @endforeach
                        </optgroup>
                    @endif
                    @foreach($matches as $tournamentName => $tournamentMatches)
                        <optgroup label="{{ $tournamentName }}">
                            @foreach($tournamentMatches as $tournamentMatch)
                                <option value="match:{{ $tournamentMatch->id }}">{{ $tournamentMatch->starts_at?->format('d.m H:i') }} · {{ $tournamentMatch->category->name }} #{{ $tournamentMatch->number }} · {{ $tournamentMatch->homeName() }} – {{ $tournamentMatch->awayName() }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">Po wyborze meczu wszystko da się poprawić poniżej.</p>
            </div>

            @foreach(['home' => 'Gospodarze (lewa strona)', 'away' => 'Goście (prawa strona)'] as $side => $sideLabel)
                <fieldset wire:key="side-{{ $side }}" class="space-y-2 rounded-lg bg-gray-50 p-3">
                    <legend class="text-xs font-bold text-gray-700">{{ $sideLabel }}</legend>
                    <div class="grid grid-cols-[1fr_1fr_auto] gap-2">
                        <input type="text" wire:model.live.debounce.400ms="m.{{ $side }}.name" class="{{ $input }}" placeholder="Nazwa" aria-label="Nazwa, pierwsza linia">
                        <input type="text" wire:model.live.debounce.400ms="m.{{ $side }}.sub" class="{{ $input }}" placeholder="Druga linia, np. miasto" aria-label="Nazwa, druga linia">
                        <input type="color" wire:model.live.debounce.300ms="m.{{ $side }}.color" class="h-9 w-11 cursor-pointer rounded-md border border-gray-300 p-0.5" title="Kolor połowy grafiki">
                    </div>
                    @if($s['tint_gradient'])
                        <label class="flex items-center gap-2 text-xs text-gray-600">
                            <input type="color" wire:model.live.debounce.300ms="m.{{ $side }}.color2" value="{{ $m[$side]['color2'] ?: '#ffffff' }}" class="h-7 w-10 cursor-pointer rounded border border-gray-300 p-0.5">
                            Drugi kolor gradientu (przy krawędzi)
                            @if(filled($m[$side]['color2'] ?? null))<button type="button" wire:click="$set('m.{{ $side }}.color2', '')" class="text-gray-400 hover:text-red-600">automatyczny</button>@endif
                        </label>
                    @endif
                    <div class="flex items-center gap-2">
                        @if(filled($m[$side]['logo'] ?? null))
                            <img src="{{ $m[$side]['logo'] }}" alt="" class="size-9 shrink-0 rounded bg-white object-contain p-0.5 ring-1 ring-gray-200">
                        @endif
                        <select wire:change="useLogo('{{ $side }}', $event.target.value)" class="{{ $input }} min-w-0 flex-1" aria-label="Logo">
                            <option value="">Logo z bazy…</option>
                            <option value="">— bez logo (inicjały) —</option>
                            @foreach($logos as $logo)
                                <option value="{{ $logo['url'] }}">{{ $logo['name'] }}</option>
                            @endforeach
                        </select>
                        <label class="shrink-0 cursor-pointer rounded-md px-2 py-1.5 text-xs font-semibold text-sky-700 ring-1 ring-sky-200 hover:bg-sky-50">
                            Wgraj
                            <input type="file" accept="image/*" wire:model="{{ $side }}Logo" class="sr-only">
                        </label>
                    </div>
                    @error($side.'Logo') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    @if(str_starts_with((string) ($m[$side]['key'] ?? ''), 'rival:'))
                        <p class="text-[11px] text-gray-500">Kolor rywala zapamięta się przy jego drużynie.</p>
                    @endif
                </fieldset>
            @endforeach
            <button type="button" wire:click="swapSides" class="text-xs font-semibold text-sky-700 hover:underline">⇄ Zamień strony</button>

            <div class="grid grid-cols-2 gap-2">
                <div><label class="{{ $label }}">Data</label><input type="date" wire:model.live="m.date" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Godzina</label><input type="time" wire:model.live.debounce.400ms="m.time" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Hala</label><input type="text" wire:model.live.debounce.400ms="m.place" class="{{ $input }}" placeholder="np. Hala POLA"></div>
                <div><label class="{{ $label }}">Adres / miasto (opcjonalnie)</label><input type="text" wire:model.live.debounce.400ms="m.place2" class="{{ $input }}" placeholder="np. ul. Lotnicza 12, Piła"></div>
                <div class="col-span-2"><label class="{{ $label }}">Etap (opcjonalnie)</label><input type="text" wire:model.live.debounce.400ms="m.stage" class="{{ $input }}" placeholder="np. Kadetki · Grupa A · mecz #1"></div>
            </div>

            <div class="grid grid-cols-[6rem_1fr] gap-2 border-t border-gray-100 pt-3">
                <div><label class="{{ $label }}">Wynik</label><input type="text" wire:model.live.debounce.400ms="m.score" class="{{ $input }}" placeholder="z setów"></div>
                <div><label class="{{ $label }}">Sety</label><input type="text" wire:model.live.debounce.400ms="m.sets" class="{{ $input }}" placeholder="25:20, 23:25, 15:12"></div>
                <div class="col-span-2"><label class="{{ $label }}">Wyróżnienie (opcjonalnie)</label><input type="text" wire:model.live.debounce.400ms="m.mvp" class="{{ $input }}" placeholder="np. MVP: Jan Kowalski"></div>
            </div>
        </section>

        {{-- SZABLON --}}
        <section class="{{ $card }} space-y-3">
            <h2 class="text-sm font-bold tracking-wide text-gray-900 uppercase">2. Szablon</h2>
            <div>
                <label class="{{ $label }}" for="ytt-template">Wygląd zapisany dla rozgrywek</label>
                <select id="ytt-template" wire:model.live="templateId" class="{{ $input }}">
                    <option value="">— nowy szablon —</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}">{{ $template->name }}@if($template->user) ({{ $template->user->name }})@endif</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="col-span-2"><label class="{{ $label }}">Nazwa szablonu</label><input type="text" wire:model="templateName" class="{{ $input }}">@error('templateName') <p class="text-xs text-red-600">{{ $message }}</p> @enderror</div>
                <div>
                    <label class="{{ $label }}">Wybieraj sam dla rozgrywek</label>
                    <select wire:model="templateCompetition" class="{{ $input }}">
                        <option value="">—</option>
                        @foreach($competitions as $competition)<option value="{{ $competition->id }}">{{ $competition->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">… albo turnieju</label>
                    <select wire:model="templateTournament" class="{{ $input }}">
                        <option value="">—</option>
                        @foreach($tournaments as $tournament)<option value="{{ $tournament->id }}">{{ $tournament->name }}@if($tournament->edition) ({{ $tournament->edition }})@endif</option>@endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 border-t border-gray-100 pt-3">
                <div class="col-span-2">
                    <label class="{{ $label }}">Układ</label>
                    <select wire:model.live="s.layout" class="{{ $input }}">
                        @foreach(\App\Models\ThumbnailTemplate::LAYOUTS as $value => $layoutLabel)<option value="{{ $value }}">{{ $layoutLabel }}</option>@endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="{{ $label }}">Czcionka</label>
                    <select wire:model.live="s.font" class="{{ $input }}">
                        @foreach(\App\Models\ThumbnailTemplate::FONTS as $value => [$fontLabel])<option value="{{ $value }}">{{ $fontLabel }}</option>@endforeach
                    </select>
                </div>
                <div class="col-span-2"><label class="{{ $label }}">Nazwa rozgrywek na grafice</label><input type="text" wire:model.live.debounce.400ms="s.label" class="{{ $input }}" placeholder="np. Wielkopolska Liga Juniorów"><p class="mt-1 text-[11px] text-gray-500">W układzie z belkami znak | łamie linię.</p></div>
                @if($s['layout'] === 'modern')
                    <div><label class="{{ $label }}">Tytuł zapowiedzi</label><input type="text" wire:model.live.debounce.400ms="s.preview_title" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}">Tytuł podsumowania</label><input type="text" wire:model.live.debounce.400ms="s.summary_title" class="{{ $input }}"></div>
                @endif
                <div><label class="{{ $label }}">Napis o transmisji (puste = brak)</label><input type="text" wire:model.live.debounce.400ms="s.live_text" class="{{ $input }}"></div>
                <div>
                    <label class="{{ $label }}">Gdzie napis o transmisji</label>
                    <select wire:model.live="s.live_position" class="{{ $input }}">
                        @foreach(\App\Models\ThumbnailTemplate::LIVE_POSITIONS as $value => $positionLabel)<option value="{{ $value }}">{{ $positionLabel }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Czcionka napisu VS</label>
                    <select wire:model.live="s.vs_font" class="{{ $input }}">
                        @foreach(\App\Models\ThumbnailTemplate::VS_FONTS as $value => [$fontLabel])<option value="{{ $value }}">{{ $fontLabel }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Kolor napisu VS</label>
                    <input type="color" wire:model.live.debounce.300ms="s.vs_color" class="h-9 w-full cursor-pointer rounded-md border border-gray-300 p-0.5">
                </div>
            </div>

            <div class="grid grid-cols-4 gap-2 border-t border-gray-100 pt-3">
                @foreach(['accent' => 'Wyróżnienie', 'bar_from' => 'Belka od', 'bar_to' => 'Belka do', 'panel' => 'Ramki'] as $key => $colorLabel)
                    <label class="text-center text-[11px] font-semibold text-gray-600">
                        <input type="color" wire:model.live.debounce.300ms="s.{{ $key }}" class="mx-auto mb-1 block h-9 w-full cursor-pointer rounded-md border border-gray-300 p-0.5">
                        {{ $colorLabel }}
                    </label>
                @endforeach
                <p class="col-span-4 text-[11px] text-gray-500">Belki (gradient od–do) są w układzie z belkami. Kolory połówek tła to kolory drużyn z części „Mecz”.</p>
            </div>

            <div class="space-y-2 border-t border-gray-100 pt-3">
                <label class="{{ $toggle }}"><input type="checkbox" wire:model.live="s.tint" class="rounded border-gray-300"> Zabarw tło kolorami drużyn</label>
                <label class="{{ $toggle }}"><input type="checkbox" wire:model.live="s.tint_gradient" class="rounded border-gray-300"> Połówki gradientem (drugi kolor przy drużynie)</label>
                <label class="{{ $toggle }}"><input type="checkbox" wire:model.live="s.show_names" class="rounded border-gray-300"> Nazwy drużyn pod logo</label>
                <label class="{{ $toggle }}"><input type="checkbox" wire:model.live="s.show_sponsors" class="rounded border-gray-300"> Pasek sponsorów na dole</label>
                <label class="{{ $toggle }}"><input type="checkbox" wire:model.live="s.tournament_logo" class="rounded border-gray-300"> Logo turnieju w lewym górnym rogu (gdy turniej je ma)</label>
            </div>

            <div class="space-y-2 border-t border-gray-100 pt-3">
                <p class="{{ $label }}">Tło (zdjęcie, zostanie czarno-białe i zabarwione)</p>
                <div class="flex items-center gap-2">
                    <img src="{{ \App\Support\ThumbnailData::src($s['background'] ?: '/img/miniatury/hala.jpg') }}" alt="" class="h-12 w-20 rounded object-cover grayscale">
                    <label class="cursor-pointer rounded-md px-2 py-1.5 text-xs font-semibold text-sky-700 ring-1 ring-sky-200 hover:bg-sky-50">Wgraj zdjęcie<input type="file" accept="image/*" wire:model="background" class="sr-only"></label>
                    @if($s['background'])<button type="button" wire:click="$set('s.background', null)" class="text-xs text-gray-500 hover:text-red-600">Przywróć domyślne</button>@endif
                </div>
                @error('background') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-2 border-t border-gray-100 pt-3">
                <p class="{{ $label }}">Obrazki w rogach</p>
                @foreach($s['images'] as $index => $image)
                    <div wire:key="corner-{{ $index }}-{{ $image['path'] }}" class="flex items-center gap-2">
                        <img src="{{ \App\Support\ThumbnailData::src($image['path']) }}" alt="" class="size-10 shrink-0 rounded bg-gray-800 object-contain p-0.5">
                        <select wire:model.live="s.images.{{ $index }}.position" class="{{ $input }} min-w-0 flex-1" aria-label="Róg">
                            @foreach(\App\Models\ThumbnailTemplate::POSITIONS as $position => $positionLabel)<option value="{{ $position }}">{{ $positionLabel }}</option>@endforeach
                        </select>
                        <input type="range" min="80" max="360" step="10" wire:model.live.debounce.200ms="s.images.{{ $index }}.size" class="w-24" aria-label="Wielkość">
                        <button type="button" wire:click="removeImage({{ $index }})" class="text-gray-400 hover:text-red-600" aria-label="Usuń obrazek">✕</button>
                    </div>
                @endforeach
                <div class="flex items-center gap-2">
                    <select wire:model="cornerPosition" class="{{ $input }} min-w-0 flex-1" aria-label="Róg nowego obrazka">
                        @foreach(\App\Models\ThumbnailTemplate::POSITIONS as $position => $positionLabel)<option value="{{ $position }}">{{ $positionLabel }}</option>@endforeach
                    </select>
                    <label class="shrink-0 cursor-pointer rounded-md px-2 py-1.5 text-xs font-semibold text-sky-700 ring-1 ring-sky-200 hover:bg-sky-50">+ Dodaj obrazek<input type="file" accept="image/*" wire:model="cornerImage" class="sr-only"></label>
                </div>
                @error('cornerImage') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-3">
                @if($templateId && $canChange)
                    <button type="button" wire:click="saveTemplate" class="rounded-md bg-sky-600 px-3 py-2 text-sm font-semibold text-white hover:bg-sky-700">Zapisz szablon</button>
                @endif
                <button type="button" wire:click="saveTemplate(true)" class="rounded-md px-3 py-2 text-sm font-semibold text-sky-700 ring-1 ring-sky-200 hover:bg-sky-50">Zapisz jako nowy</button>
                @if($templateId && $canChange)
                    <button type="button" wire:click="deleteTemplate" wire:confirm="Usunąć szablon „{{ $templateName }}”?" class="ml-auto text-sm text-red-600 hover:underline">Usuń</button>
                @endif
            </div>
            @if($templateId && ! $canChange)
                <p class="text-[11px] text-gray-500">To szablon innej osoby: zmiany zapiszesz jako nowy szablon.</p>
            @endif
        </section>
    </div>

    {{-- PODGLĄD --}}
    <div class="min-w-0 space-y-6 xl:sticky xl:top-20 xl:self-start">
        @foreach(['preview' => 'Zapowiedź', 'summary' => 'Podsumowanie'] as $mode => $modeLabel)
            <section wire:key="ytt-{{ $mode }}">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <h2 class="text-sm font-bold tracking-wide text-gray-900 uppercase">{{ $modeLabel }}</h2>
                    <button type="button" data-ytt-download="#ytt-{{ $mode }}" data-file-name="{{ $fileBase }}-{{ $mode === 'preview' ? 'zapowiedz' : 'wynik' }}.png" data-busy-label="Przygotowuję…" data-error-message="Nie udało się przygotować obrazka. Spróbuj ponownie." class="rounded-md bg-gray-900 px-3 py-1.5 text-sm font-semibold text-white hover:bg-gray-700">Pobierz PNG 1920×1080</button>
                </div>
                <div class="ytt-frame" data-ytt-frame>
                    <div class="ytt-scaler" data-ytt-scaler wire:ignore.self>
                        <x-yt-thumbnail id="ytt-{{ $mode }}" :settings="$s" :data="$m" :mode="$mode" :sponsors="$sponsors" />
                    </div>
                </div>
            </section>
        @endforeach
    </div>
</div>

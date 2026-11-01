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
            {{-- Filtry listy jak na stronie meczów --}}
            <div class="grid grid-cols-2 gap-2 rounded-lg bg-gray-50 p-2">
                <select wire:model.live="fSeason" class="{{ $input }}" aria-label="Sezon">
                    <option value="">Wszystkie sezony</option>
                    @foreach($seasons as $season)<option value="{{ $season->id }}">{{ $season->name }}</option>@endforeach
                </select>
                <select wire:model.live="fTeam" class="{{ $input }}" aria-label="Drużyna">
                    <option value="">Wszystkie drużyny</option>
                    @foreach($teams as $team)<option value="{{ $team->id }}">{{ $team->name }}</option>@endforeach
                </select>
                <select wire:model.live="fCompetition" class="{{ $input }}" aria-label="Rozgrywki">
                    <option value="">Wszystkie rozgrywki</option>
                    @foreach($competitions as $competition)<option value="{{ $competition->id }}">{{ $competition->name }}</option>@endforeach
                    <option value="turnieje">Turnieje</option>
                    <option value="sparingi">Sparingi</option>
                </select>
                <div class="flex rounded-md bg-white p-0.5 text-xs font-semibold ring-1 ring-gray-300" role="group" aria-label="Okres">
                    @foreach(['all' => 'Wszystkie', 'upcoming' => 'Nadchodzące', 'results' => 'Rozegrane'] as $value => $periodLabel)
                        <button type="button" wire:click="$set('fPeriod', '{{ $value }}')" @class(['flex-1 rounded px-1 py-1.5', 'bg-sky-600 text-white' => $fPeriod === $value, 'text-gray-600 hover:bg-gray-100' => $fPeriod !== $value])>{{ $periodLabel }}</button>
                    @endforeach
                </div>
                <input type="search" wire:model.live.debounce.400ms="fSearch" placeholder="Szukaj rywala…" class="{{ $input }} col-span-2">
                <p class="col-span-2 flex justify-between text-[11px] text-gray-500">
                    <span>{{ $games->count() + $matches->flatten()->count() }} meczów na liście</span>
                    <button type="button" wire:click="resetFilters" class="hover:text-sky-700">Wyczyść filtry</button>
                </p>
            </div>
            <div>
                <label class="{{ $label }}" for="ytt-pick">Z bazy meczów albo ręcznie</label>
                <select id="ytt-pick" wire:model.live="pick" class="{{ $input }}">
                    <option value="">Wpiszę ręcznie</option>
                    @if($games->isNotEmpty())
                        <optgroup label="Mecze klubu (najbliższe, potem rozegrane; z wynikiem ✓)">
                            @foreach($games as $game)
                                <option value="game:{{ $game->id }}">@if($game->isFinished() && $game->home_score !== null)✓ {{ $game->home_score }}:{{ $game->away_score }} · @endif{{ $game->game_date?->format('d.m.y') }} · {{ implode(' – ', $game->sidesNames()) }}@if($game->competition) · {{ $game->competition->name }}@endif</option>
                            @endforeach
                        </optgroup>
                    @endif
                    @foreach($matches as $tournamentName => $tournamentMatches)
                        <optgroup label="{{ $tournamentName }}">
                            @foreach($tournamentMatches as $tournamentMatch)
                                <option value="match:{{ $tournamentMatch->id }}">@if($tournamentMatch->home_sets !== null)✓ {{ $tournamentMatch->home_sets }}:{{ $tournamentMatch->away_sets }} · @endif{{ $tournamentMatch->starts_at?->format('d.m H:i') }} · {{ $tournamentMatch->category->name }} #{{ $tournamentMatch->number }} · {{ $tournamentMatch->homeName() }} – {{ $tournamentMatch->awayName() }}</option>
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

        </section>

        {{-- WYNIK --}}
        <section class="{{ $card }} space-y-3">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-bold tracking-wide text-gray-900 uppercase">2. Wynik (do podsumowania)</h2>
                <button type="button" wire:click="clearResult" class="text-xs text-gray-500 hover:text-red-600">Wyczyść</button>
            </div>
            <div class="grid grid-cols-[1fr_auto_1fr] items-end gap-2">
                <div>
                    <label class="{{ $label }} truncate" for="ytt-score-home">{{ $m['home']['name'] ?: 'Gospodarze' }}</label>
                    <input id="ytt-score-home" type="number" min="0" max="3" inputmode="numeric" wire:model.live.debounce.300ms="scoreHome" class="{{ $input }} text-center text-2xl font-bold" placeholder="–">
                </div>
                <span class="pb-2 text-2xl font-bold text-gray-400">:</span>
                <div>
                    <label class="{{ $label }} truncate text-right" for="ytt-score-away">{{ $m['away']['name'] ?: 'Goście' }}</label>
                    <input id="ytt-score-away" type="number" min="0" max="3" inputmode="numeric" wire:model.live.debounce.300ms="scoreAway" class="{{ $input }} text-center text-2xl font-bold" placeholder="–">
                </div>
            </div>
            <p class="text-[11px] text-gray-500">Puste pola wyniku: wynik policzy się z setów.</p>
            <div class="space-y-1.5">
                @foreach($setRows as $index => $row)
                    <div wire:key="set-{{ $index }}" class="grid grid-cols-[3.5rem_1fr_auto_1fr] items-center gap-2">
                        <span class="text-xs font-semibold text-gray-500">Set {{ $index + 1 }}</span>
                        <input type="number" min="0" max="99" inputmode="numeric" wire:model.live.debounce.300ms="setRows.{{ $index }}.h" class="{{ $input }} text-center" aria-label="Set {{ $index + 1 }}, gospodarze">
                        <span class="text-gray-400">:</span>
                        <input type="number" min="0" max="99" inputmode="numeric" wire:model.live.debounce.300ms="setRows.{{ $index }}.a" class="{{ $input }} text-center" aria-label="Set {{ $index + 1 }}, goście">
                    </div>
                @endforeach
            </div>
            <div><label class="{{ $label }}">Wyróżnienie (opcjonalnie)</label><input type="text" wire:model.live.debounce.400ms="m.mvp" class="{{ $input }}" placeholder="np. MVP: Jan Kowalski"></div>
        </section>

        {{-- SZABLON --}}
        <section class="{{ $card }} space-y-3">
            <h2 class="text-sm font-bold tracking-wide text-gray-900 uppercase">3. Szablon</h2>
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
                    <div><label class="{{ $label }}">Tytuł zapowiedzi</label><input type="text" wire:model.live.debounce.400ms="s.preview_title" class="{{ $input }}" placeholder="np. Zapowiedź meczu"></div>
                    <div><label class="{{ $label }}">Tytuł podsumowania</label><input type="text" wire:model.live.debounce.400ms="s.summary_title" class="{{ $input }}" placeholder="np. Wynik meczu"></div>
                    <div>
                        <label class="{{ $label }}">Kolor tytułu (1. słowo)</label>
                        <input type="color" wire:model.live.debounce.300ms="s.title_color" class="h-9 w-full cursor-pointer rounded-md border border-gray-300 p-0.5">
                    </div>
                    <div>
                        <label class="{{ $label }}">Kolor tytułu (dalsze słowa)</label>
                        <input type="color" wire:model.live.debounce.300ms="s.accent" class="h-9 w-full cursor-pointer rounded-md border border-gray-300 p-0.5">
                    </div>
                    <label class="{{ $toggle }} col-span-2"><input type="checkbox" wire:model.live="s.title_two_lines" class="rounded border-gray-300"> Tytuł zapowiedzi w dwóch wierszach</label>
                @else
                    <p class="col-span-2 text-[11px] text-gray-500">Tytuły „Zapowiedź meczu” / „Wynik meczu” i ich kolory są w układzie nowoczesnym.</p>
                @endif
                <label class="{{ $toggle }} col-span-2"><input type="checkbox" wire:model.live="s.show_stage" class="rounded border-gray-300"> Pokaż etap / grupę pod tytułem</label>
                @if($s['show_stage'])
                    <div class="col-span-2"><label class="{{ $label }}">Własny tekst zamiast etapu (puste = z meczu)</label><input type="text" wire:model.live.debounce.400ms="s.stage_text" class="{{ $input }}" placeholder="np. Półfinał"></div>
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
                <div>
                    <label class="{{ $label }}">Grafika VS</label>
                    <select wire:model.live="s.vs_style" class="{{ $input }}">
                        @foreach(\App\Models\ThumbnailTemplate::VS_STYLES as $value => $styleLabel)<option value="{{ $value }}">{{ $styleLabel }}</option>@endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    @if($s['vs_image'])<img src="{{ \App\Support\ThumbnailData::src($s['vs_image']) }}" alt="" class="h-9 w-14 rounded bg-gray-800 object-contain">@endif
                    <label class="cursor-pointer rounded-md px-2 py-2 text-xs font-semibold text-sky-700 ring-1 ring-sky-200 hover:bg-sky-50">{{ $s['vs_image'] ? 'Zmień obrazek VS' : 'Wgraj obrazek VS (PNG)' }}<input type="file" accept="image/*" wire:model="vsImage" class="sr-only"></label>
                </div>
                @error('vsImage') <p class="col-span-2 text-xs text-red-600">{{ $message }}</p> @enderror
                @if($s['vs_style'] === 'burst')
                    <div class="col-span-2 space-y-2 rounded-lg bg-gray-50 p-2">
                        <label class="{{ $toggle }}"><input type="checkbox" wire:model.live="s.vs_team_colors" class="rounded border-gray-300"> Litery w barwach drużyn (V w kolorze gości, S gospodarzy)</label>
                        @unless($s['vs_team_colors'])
                            @foreach(['v' => 'Litera V', 's' => 'Litera S'] as $letter => $letterLabel)
                                <div class="grid grid-cols-5 items-end gap-1.5">
                                    <span class="pb-2 text-xs font-semibold text-gray-600">{{ $letterLabel }}</span>
                                    @foreach([1 => 'Góra', 2 => 'Środek', 3 => 'Dół', 4 => 'Płomienie'] as $i => $toneLabel)
                                        <label class="text-center text-[10px] font-semibold text-gray-500">
                                            <input type="color" wire:model.live.debounce.300ms="s.vs_{{ $letter }}{{ $i }}" class="mb-0.5 block h-8 w-full cursor-pointer rounded-md border border-gray-300 p-0.5">{{ $toneLabel }}
                                        </label>
                                    @endforeach
                                </div>
                            @endforeach
                        @endunless
                        <label class="flex items-center gap-2 text-xs font-semibold text-gray-600"><input type="color" wire:model.live.debounce.300ms="s.vs_outline" class="h-8 w-14 cursor-pointer rounded-md border border-gray-300 p-0.5"> Obwódka i cień liter</label>
                    </div>
                @endif
                <div class="col-span-2">
                    <label class="{{ $label }}">Wielkość VS: {{ (int) $s['vs_size'] }}%</label>
                    <div class="flex items-center gap-2">
                        <input type="range" min="30" max="250" step="5" wire:model.live.debounce.150ms="s.vs_size" class="w-full">
                        <button type="button" wire:click="nudgeVs(-20)" title="W górę" class="rounded-md px-2.5 py-1 text-sm font-bold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">▲</button>
                        <button type="button" wire:click="nudgeVs(20)" title="W dół" class="rounded-md px-2.5 py-1 text-sm font-bold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">▼</button>
                        <button type="button" wire:click="$set('s.vs_offset', 0)" class="whitespace-nowrap text-xs text-gray-500 hover:text-sky-700">Środek ({{ (int) $s['vs_offset'] }} px)</button>
                    </div>
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

@php
    $input = 'w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500';
    $label = 'mb-1 block text-xs font-semibold text-gray-600';
    $card = 'rounded-xl bg-white p-4 ring-1 ring-gray-200';
    $toggle = 'inline-flex items-center gap-2 text-sm text-gray-700';
@endphp
<div class="space-y-5" x-data="{ tab: 'mecz' }">
    {{-- SZABLONY: kafelki z podglądem; klik wybiera wygląd --}}
    <section class="{{ $card }}">
        <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="text-sm font-bold tracking-wide text-gray-900 uppercase">Szablon</h2>
            <p class="text-xs text-gray-500">Kliknij szablon, potem wybierz mecz. Podgląd pokazuje wybrany mecz w każdym szablonie.</p>
        </div>
        <div class="flex gap-3 overflow-x-auto pb-2">
            @foreach($templates as $template)
                <button type="button" wire:key="tpl-{{ $template->id }}" wire:click="chooseTemplate({{ $template->id }})"
                    @class(['group w-56 shrink-0 rounded-lg p-1.5 text-left transition', 'bg-sky-50 ring-2 ring-sky-500' => $templateId === $template->id, 'ring-1 ring-gray-200 hover:ring-sky-300' => $templateId !== $template->id])>
                    <div class="ytt-frame pointer-events-none" data-ytt-frame>
                        <div class="ytt-scaler" data-ytt-scaler wire:ignore.self>
                            <x-yt-thumbnail :settings="$template->resolvedSettings()" :data="$m" mode="preview" :sponsors="$sponsors" />
                        </div>
                    </div>
                    <p class="mt-1.5 truncate text-sm font-semibold text-gray-900">{{ $template->name }}</p>
                    <p class="truncate text-[11px] text-gray-500">
                        @if($template->competition){{ $template->competition->name }}@elseif($template->tournament){{ $template->tournament->name }}@else Dowolne mecze @endif
                        @if($template->user) · {{ $template->user->name }}@endif
                    </p>
                </button>
            @endforeach
            <button type="button" wire:click="chooseTemplate(null)"
                @class(['flex w-40 shrink-0 flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed p-3 text-sm font-semibold', 'border-sky-500 bg-sky-50 text-sky-700' => ! $templateId, 'border-gray-300 text-gray-500 hover:border-sky-400 hover:text-sky-700' => $templateId])>
                <span class="text-2xl leading-none">+</span> Nowy szablon
            </button>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,28rem)_minmax(0,1fr)]">
        {{-- USTAWIENIA w trzech zakładkach --}}
        <div class="min-w-0 space-y-3">
            <nav class="grid grid-cols-3 gap-1 rounded-xl bg-gray-100 p-1 text-sm font-semibold" role="tablist">
                @foreach(['mecz' => '1. Mecz', 'wynik' => '2. Wynik', 'wyglad' => '3. Wygląd'] as $key => $tabLabel)
                    <button type="button" role="tab" x-on:click="tab = '{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-white text-sky-700 shadow-sm' : 'text-gray-600 hover:text-gray-900'" class="rounded-lg px-2 py-2">{{ $tabLabel }}</button>
                @endforeach
            </nav>

            {{-- MECZ --}}
            <section x-show="tab === 'mecz'" class="{{ $card }} space-y-3">
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
                <select wire:model.live="fCompetition" class="{{ $input }} col-span-2" aria-label="Rozgrywki">
                    <option value="">Wszystkie rozgrywki</option>
                    @foreach($competitions as $competition)<option value="{{ $competition->id }}">{{ $competition->name }}</option>@endforeach
                    <option value="turnieje">Turnieje</option>
                    <option value="sparingi">Sparingi</option>
                </select>
                <div class="col-span-2 flex rounded-md bg-white p-0.5 text-xs font-semibold ring-1 ring-gray-300" role="group" aria-label="Okres">
                    @foreach(['all' => 'Wszystkie', 'upcoming' => 'Nadchodzące', 'results' => 'Rozegrane'] as $value => $periodLabel)
                        <button type="button" wire:click="$set('fPeriod', '{{ $value }}')" @class(['flex-1 rounded px-1 py-1.5', 'bg-sky-600 text-white' => $fPeriod === $value, 'text-gray-600 hover:bg-gray-100' => $fPeriod !== $value])>{{ $periodLabel }}</button>
                    @endforeach
                </div>
                <input type="search" wire:model.live.debounce.400ms="fSearch" placeholder="Szukaj: rywal, drużyna, data (np. 15.11)…" class="{{ $input }} col-span-2">
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
                        @php
                            $currentLogo = (string) ($m[$side]['logo'] ?? '');
                            // Para logo: zwykłe ↔ wariant na ciemne tło (np. białe logo rywala na ciemną miniaturę).
                            $darkVariant = $currentLogo !== '' ? collect($logos)->first(fn ($logo) => $logo['dark'] && $logo['pair'] === $currentLogo) : null;
                            $currentDark = $currentLogo !== '' ? collect($logos)->first(fn ($logo) => $logo['dark'] && $logo['url'] === $currentLogo) : null;
                        @endphp
                        @if($currentLogo !== '')
                            <img src="{{ $currentLogo }}" alt="" @class(['size-9 shrink-0 rounded object-contain p-0.5 ring-1 ring-gray-200', 'bg-slate-900' => $currentDark, 'bg-white' => ! $currentDark])>
                        @endif
                        {{-- Logo z bazy z wyszukiwarką: po nazwie klubu albo drużyny (także dawnej nazwy), bez polskich znaków --}}
                        <div class="relative min-w-0 flex-1" x-data="{
                                q: '', open: false, active: 0, logos: @js($logos),
                                n(s) { return (s || '').toLocaleLowerCase('pl').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/ł/g, 'l'); },
                                get matches() { const q = this.n(this.q).trim(); return (q ? this.logos.filter((l) => [l.name, ...l.teams].some((s) => this.n(s).includes(q))) : this.logos).slice(0, 80); },
                                hit(l) { const q = this.n(this.q).trim(); return q && ! this.n(l.name).includes(q) ? l.teams.filter((s) => this.n(s).includes(q)).join(', ') : ''; },
                                choose(url) { this.open = false; this.q = ''; $wire.useLogo(@js($side), url); },
                            }" x-on:click.outside="open = false" x-on:keydown.escape.stop="open = false">
                            <input type="search" x-model="q" x-on:focus="open = true" x-on:input="open = true; active = 0" x-on:keydown.down.prevent="active = Math.min(active + 1, matches.length - 1)" x-on:keydown.up.prevent="active = Math.max(active - 1, 0)" x-on:keydown.enter.prevent="matches[active] && choose(matches[active].url)"
                                class="{{ $input }}" placeholder="Logo z bazy: wpisz klub lub drużynę…" aria-label="Logo" autocomplete="off">
                            <ul x-show="open" x-cloak class="absolute z-30 mt-1 max-h-72 w-full overflow-y-auto overscroll-contain rounded-lg bg-white py-1 text-sm shadow-lg ring-1 ring-gray-200">
                                <li><button type="button" x-on:click="choose('')" class="block w-full px-3 py-2 text-left text-gray-500 hover:bg-gray-50">— bez logo (inicjały) —</button></li>
                                <template x-for="(logo, index) in matches" :key="logo.url">
                                    <li>
                                        <button type="button" x-on:click="choose(logo.url)" x-on:mouseenter="active = index" :class="active === index ? 'bg-sky-50 text-sky-800' : 'text-gray-800'" class="flex w-full items-center gap-2 px-3 py-1.5 text-left">
                                            <img :src="logo.url" alt="" loading="lazy" :class="logo.dark ? 'bg-slate-900' : 'bg-white'" class="size-7 shrink-0 rounded object-contain p-0.5 ring-1 ring-gray-200">
                                            <span class="min-w-0"><span class="block truncate" x-text="logo.name"></span><span x-show="hit(logo)" class="block truncate text-[11px] text-gray-500" x-text="hit(logo)"></span></span>
                                        </button>
                                    </li>
                                </template>
                                <li x-show="! matches.length" class="px-3 py-2 text-gray-500">Nic nie pasuje.</li>
                            </ul>
                        </div>
                        <label class="shrink-0 cursor-pointer rounded-md px-2 py-1.5 text-xs font-semibold text-sky-700 ring-1 ring-sky-200 hover:bg-sky-50">
                            Wgraj
                            <input type="file" accept="image/*" wire:model="{{ $side }}Logo" class="sr-only">
                        </label>
                    </div>
                    @if($darkVariant)
                        <button type="button" wire:click="useLogo(@js($side), @js($darkVariant['url']))" class="inline-flex items-center gap-1.5 text-xs font-semibold text-sky-700 hover:underline"><span class="size-3 rounded-sm bg-slate-900 ring-1 ring-gray-300"></span>Użyj wariantu na ciemne tło</button>
                    @elseif($currentDark && $currentDark['pair'])
                        <button type="button" wire:click="useLogo(@js($side), @js($currentDark['pair']))" class="inline-flex items-center gap-1.5 text-xs font-semibold text-sky-700 hover:underline"><span class="size-3 rounded-sm bg-white ring-1 ring-gray-300"></span>Wróć do zwykłego logo</button>
                    @endif
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
                <button type="button" x-on:click="tab = 'wynik'" class="w-full rounded-md bg-sky-600 py-2 text-sm font-semibold text-white hover:bg-sky-700">Dalej: wynik →</button>
            </section>

            {{-- WYNIK --}}
            <section x-show="tab === 'wynik'" x-cloak class="{{ $card }} space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs text-gray-500">Wynik pokazuje się na grafice „Podsumowanie”. Rozegrany mecz z bazy wypełnia go sam.</p>
                    <button type="button" wire:click="clearResult" class="shrink-0 text-xs text-gray-500 hover:text-red-600">Wyczyść</button>
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
                <button type="button" x-on:click="tab = 'wyglad'" class="w-full rounded-md bg-sky-600 py-2 text-sm font-semibold text-white hover:bg-sky-700">Dalej: wygląd →</button>
            </section>

            {{-- WYGLĄD (szablon) --}}
            <section x-show="tab === 'wyglad'" x-cloak class="{{ $card }} space-y-4">
                <p class="text-xs text-gray-500">Zmiany widać od razu na podglądzie. Zapisz je na dole, żeby wróciły przy następnym meczu.</p>

                <div class="space-y-2">
                    <h3 class="text-xs font-bold tracking-wide text-gray-900 uppercase">Układ i napisy</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="{{ $label }}">Układ</label>
                            <select wire:model.live="s.layout" class="{{ $input }}">
                                @foreach(\App\Models\ThumbnailTemplate::LAYOUTS as $value => $layoutLabel)<option value="{{ $value }}">{{ \Illuminate\Support\Str::before($layoutLabel, ':') }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Czcionka</label>
                            <select wire:model.live="s.font" class="{{ $input }}">
                                @foreach(\App\Models\ThumbnailTemplate::FONTS as $value => [$fontLabel])<option value="{{ $value }}">{{ $fontLabel }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-span-2"><label class="{{ $label }}">Nazwa rozgrywek (u góry)</label><input type="text" wire:model.live.debounce.400ms="s.label" class="{{ $input }}" placeholder="np. Wielkopolska Liga Juniorów">@if($s['layout'] === 'bars')<p class="mt-1 text-[11px] text-gray-500">Znak | łamie linię.</p>@endif</div>
                        @if($s['layout'] === 'modern')
                            <div><label class="{{ $label }}">Tytuł zapowiedzi</label><input type="text" wire:model.live.debounce.400ms="s.preview_title" class="{{ $input }}" placeholder="np. Zapowiedź meczu"></div>
                            <div><label class="{{ $label }}">Tytuł wyniku</label><input type="text" wire:model.live.debounce.400ms="s.summary_title" class="{{ $input }}" placeholder="np. Wynik meczu"></div>
                            <div class="col-span-2 flex flex-wrap items-center gap-x-4 gap-y-2">
                                <label class="flex items-center gap-2 text-xs font-semibold text-gray-600"><input type="color" wire:model.live.debounce.300ms="s.title_color" class="h-8 w-11 cursor-pointer rounded-md border border-gray-300 p-0.5"> 1. słowo</label>
                                <label class="flex items-center gap-2 text-xs font-semibold text-gray-600"><input type="color" wire:model.live.debounce.300ms="s.accent" class="h-8 w-11 cursor-pointer rounded-md border border-gray-300 p-0.5"> dalsze słowa</label>
                                <label class="{{ $toggle }}"><input type="checkbox" wire:model.live="s.title_two_lines" class="rounded border-gray-300"> w dwóch wierszach</label>
                            </div>
                        @endif
                        <label class="{{ $toggle }} col-span-2"><input type="checkbox" wire:model.live="s.show_stage" class="rounded border-gray-300"> Etap / grupa pod tytułem</label>
                        @if($s['show_stage'])
                            <div class="col-span-2"><input type="text" wire:model.live.debounce.400ms="s.stage_text" class="{{ $input }}" placeholder="Własny tekst, np. Półfinał (puste = z meczu)" aria-label="Własny tekst etapu"></div>
                        @endif
                        <div><label class="{{ $label }}">Napis o transmisji</label><input type="text" wire:model.live.debounce.400ms="s.live_text" class="{{ $input }}" placeholder="puste = bez napisu"></div>
                        <div>
                            <label class="{{ $label }}">Gdzie</label>
                            <select wire:model.live="s.live_position" class="{{ $input }}">
                                @foreach(\App\Models\ThumbnailTemplate::LIVE_POSITIONS as $value => $positionLabel)<option value="{{ $value }}">{{ $positionLabel }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="space-y-2 border-t border-gray-100 pt-3">
                    <h3 class="text-xs font-bold tracking-wide text-gray-900 uppercase">Napis VS</h3>
                    <div class="grid grid-cols-3 gap-1 rounded-lg bg-gray-100 p-1 text-xs font-semibold">
                        @foreach(['burst' => 'Graffiti', 'text' => 'Sam napis', 'image' => 'Własny obrazek'] as $value => $styleLabel)
                            <button type="button" wire:click="$set('s.vs_style', '{{ $value }}')" @class(['rounded-md px-2 py-1.5', 'bg-white text-sky-700 shadow-sm' => $s['vs_style'] === $value, 'text-gray-600 hover:text-gray-900' => $s['vs_style'] !== $value])>{{ $styleLabel }}</button>
                        @endforeach
                    </div>
                    @if($s['vs_style'] === 'burst')
                        <label class="{{ $toggle }}"><input type="checkbox" wire:model.live="s.vs_team_colors" class="rounded border-gray-300"> Litery w kolorach drużyn na krzyż</label>
                        @unless($s['vs_team_colors'])
                            @foreach(['v' => 'Litera V', 's' => 'Litera S'] as $letter => $letterLabel)
                                <div class="grid grid-cols-4 items-end gap-1.5">
                                    <span class="pb-2 text-xs font-semibold text-gray-600">{{ $letterLabel }}</span>
                                    @foreach([1 => 'Początek', 2 => 'Środek', 3 => 'Koniec'] as $i => $toneLabel)
                                        <label class="text-center text-[10px] font-semibold text-gray-500"><input type="color" wire:model.live.debounce.300ms="s.vs_{{ $letter }}{{ $i }}" class="mb-0.5 block h-8 w-full cursor-pointer rounded-md border border-gray-300 p-0.5">{{ $toneLabel }}</label>
                                    @endforeach
                                </div>
                            @endforeach
                        @endunless
                    @elseif($s['vs_style'] === 'text')
                        <div class="grid grid-cols-[1fr_auto] gap-2">
                            <select wire:model.live="s.vs_font" class="{{ $input }}" aria-label="Czcionka VS">
                                @foreach(\App\Models\ThumbnailTemplate::VS_FONTS as $value => [$fontLabel])<option value="{{ $value }}">{{ $fontLabel }}</option>@endforeach
                            </select>
                            <input type="color" wire:model.live.debounce.300ms="s.vs_color" class="h-9 w-12 cursor-pointer rounded-md border border-gray-300 p-0.5" title="Kolor napisu VS">
                        </div>
                    @else
                        <div class="flex items-center gap-2">
                            @if($s['vs_image'])<img src="{{ \App\Support\ThumbnailData::src($s['vs_image']) }}" alt="" class="h-10 w-16 rounded bg-gray-800 object-contain">@endif
                            <label class="cursor-pointer rounded-md px-3 py-2 text-xs font-semibold text-sky-700 ring-1 ring-sky-200 hover:bg-sky-50">{{ $s['vs_image'] ? 'Zmień obrazek' : 'Wgraj obrazek (PNG z przezroczystym tłem)' }}<input type="file" accept="image/*" wire:model="vsImage" class="sr-only"></label>
                        </div>
                        @error('vsImage') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    @endif
                    <div class="flex items-center gap-2">
                        <span class="w-24 shrink-0 text-xs font-semibold text-gray-600">Wielkość {{ (int) $s['vs_size'] }}%</span>
                        <input type="range" min="30" max="250" step="5" wire:model.live.debounce.150ms="s.vs_size" class="w-full" aria-label="Wielkość VS">
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-24 shrink-0 text-xs font-semibold text-gray-600">Położenie</span>
                        <button type="button" wire:click="nudgeVs(-20)" title="W górę" class="rounded-md px-3 py-1 text-sm font-bold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">▲ wyżej</button>
                        <button type="button" wire:click="nudgeVs(20)" title="W dół" class="rounded-md px-3 py-1 text-sm font-bold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">▼ niżej</button>
                        @if((int) $s['vs_offset'] !== 0)<button type="button" wire:click="$set('s.vs_offset', 0)" class="text-xs text-gray-500 hover:text-sky-700">na środek</button>@endif
                    </div>
                </div>

                <div class="space-y-2 border-t border-gray-100 pt-3">
                    <h3 class="text-xs font-bold tracking-wide text-gray-900 uppercase">Tło i kolory</h3>
                    <label class="{{ $toggle }}"><input type="checkbox" wire:model.live="s.tint" class="rounded border-gray-300"> Połówki w kolorach drużyn</label>
                    <label class="{{ $toggle }}"><input type="checkbox" wire:model.live="s.tint_gradient" class="rounded border-gray-300"> Połówki gradientem (drugi kolor ustawisz przy drużynie w „Mecz”)</label>
                    <div class="flex items-center gap-2">
                        <img src="{{ \App\Support\ThumbnailData::src($s['background'] ?: '/img/miniatury/hala.jpg') }}" alt="" class="h-12 w-20 rounded object-cover grayscale">
                        <label class="cursor-pointer rounded-md px-2 py-1.5 text-xs font-semibold text-sky-700 ring-1 ring-sky-200 hover:bg-sky-50">Inne zdjęcie tła<input type="file" accept="image/*" wire:model="background" class="sr-only"></label>
                        @if($s['background'])<button type="button" wire:click="$set('s.background', null)" class="text-xs text-gray-500 hover:text-red-600">Domyślne</button>@endif
                    </div>
                    @error('background') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    <div class="grid grid-cols-3 gap-2">
                        @foreach(['panel' => 'Ramki na dole', 'bar_from' => 'Belka od', 'bar_to' => 'Belka do'] as $key => $colorLabel)
                            @continue($key !== 'panel' && $s['layout'] !== 'bars')
                            <label class="text-center text-[11px] font-semibold text-gray-600"><input type="color" wire:model.live.debounce.300ms="s.{{ $key }}" class="mx-auto mb-1 block h-8 w-full cursor-pointer rounded-md border border-gray-300 p-0.5">{{ $colorLabel }}</label>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-2 border-t border-gray-100 pt-3">
                    <h3 class="text-xs font-bold tracking-wide text-gray-900 uppercase">Logo, nazwy, obrazki</h3>
                    <label class="{{ $toggle }}"><input type="checkbox" wire:model.live="s.show_names" class="rounded border-gray-300"> Nazwy drużyn pod logo (bez nazw logo są większe)</label>
                    <label class="{{ $toggle }}"><input type="checkbox" wire:model.live="s.show_sponsors" class="rounded border-gray-300"> Pasek sponsorów na dole</label>
                    <label class="{{ $toggle }}"><input type="checkbox" wire:model.live="s.tournament_logo" class="rounded border-gray-300"> Logo turnieju w lewym górnym rogu</label>
                    <p class="{{ $label }} pt-1">Obrazki w rogach</p>
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

                <div class="space-y-2 rounded-lg bg-sky-50 p-3 ring-1 ring-sky-100">
                    <h3 class="text-xs font-bold tracking-wide text-gray-900 uppercase">Zapisz szablon</h3>
                    <div><label class="{{ $label }}">Nazwa</label><input type="text" wire:model="templateName" class="{{ $input }}" placeholder="np. Liga juniorów">@error('templateName') <p class="text-xs text-red-600">{{ $message }}</p> @enderror</div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="{{ $label }}">Sam wybierz dla rozgrywek</label>
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
                    <div class="flex flex-wrap gap-2 pt-1">
                        @if($templateId && $canChange)
                            <button type="button" wire:click="saveTemplate" class="rounded-md bg-sky-600 px-3 py-2 text-sm font-semibold text-white hover:bg-sky-700">Zapisz zmiany</button>
                        @endif
                        <button type="button" wire:click="saveTemplate(true)" @class(['rounded-md px-3 py-2 text-sm font-semibold', 'bg-sky-600 text-white hover:bg-sky-700' => ! $templateId || ! $canChange, 'text-sky-700 ring-1 ring-sky-200 hover:bg-white' => $templateId && $canChange])>Zapisz jako nowy</button>
                        @if($templateId && $canChange)
                            <button type="button" wire:click="deleteTemplate" wire:confirm="Usunąć szablon „{{ $templateName }}”?" class="ml-auto text-sm text-red-600 hover:underline">Usuń</button>
                        @endif
                    </div>
                    @if($templateId && ! $canChange)
                        <p class="text-[11px] text-gray-500">To szablon innej osoby: zmiany zapiszesz jako nowy.</p>
                    @endif
                </div>
            </section>
        </div>

        {{-- PODGLĄD --}}
        <div class="min-w-0 space-y-6 xl:sticky xl:top-20 xl:self-start">
            @foreach(['preview' => 'Zapowiedź', 'summary' => 'Podsumowanie (wynik)'] as $mode => $modeLabel)
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
</div>

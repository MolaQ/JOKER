@php $birthdayIcon = trim((string) $site['cal_birthday_icon']) ?: '🎂'; @endphp
<div x-data="{ away: false }" style="--cal-home-bg: {{ $site->color('cal_home_bg') }}; --cal-home-border: {{ $site->color('cal_home_border') }}; --cal-home-text: {{ $site->color('cal_home_text') }}; --cal-away-bg: {{ $site->color('cal_away_bg') }}; --cal-away-text: {{ $site->color('cal_away_text') }}; --cal-away-border: {{ $site->color('cal_away_border') }}; --cal-bday-bg: {{ $site->color('cal_birthday_bg') }}; --cal-bday-text: {{ $site->color('cal_birthday_text') }};">
    {{-- Telefon: po przewinięciu filtrów zostaje u góry zwarty pasek z drużynami i przyciskiem powrotu do wszystkich filtrów --}}
    <div x-show="away" x-cloak x-transition.opacity.duration.200ms class="fixed inset-x-0 top-16 z-40 border-b border-zinc-200 bg-white/95 py-2 shadow-sm backdrop-blur-xl lg:hidden">
        <div class="flex items-center gap-2 pl-4">
            <div data-scroll-fade x-on:click="if ($event.target.closest('button')) setTimeout(() => document.querySelector('[data-games-list]')?.scrollIntoView({ behavior: 'smooth' }), 400)" class="scrollbar-none flex min-w-0 flex-1 gap-2 overflow-x-auto">
                <button type="button" wire:click="allTeams" @class(['chip shrink-0 transition', 'bg-sky-club text-white' => $team === '' && $competition !== \App\Livewire\GamesBrowser::FILTER_MINI, 'bg-zinc-100 text-zinc-700' => $team !== '' || $competition === \App\Livewire\GamesBrowser::FILTER_MINI])>{{ __('Wszystkie') }}</button>
                @foreach($teams as $teamOption)
                    <button type="button" wire:click="toggleTeam('{{ $teamOption->slug }}')" style="--tile: {{ \App\Support\AgeColors::forTeam($teamOption) }}" @class(['chip shrink-0 border-l-[5px] border-(--tile) transition', 'bg-[color-mix(in_srgb,var(--tile)_82%,black)] text-white' => $team === $teamOption->slug, 'bg-zinc-100 text-zinc-700' => $team !== $teamOption->slug])>{{ $teamOption->name }}</button>
                @endforeach
                @if($hasMini)
                    <button type="button" wire:click="toggleMini" style="--tile: {{ \App\Support\AgeColors::forMini(4) }}" @class(['chip shrink-0 border-l-[5px] border-(--tile) transition', 'bg-[color-mix(in_srgb,var(--tile)_82%,black)] text-white' => $competition === \App\Livewire\GamesBrowser::FILTER_MINI, 'bg-zinc-100 text-zinc-700' => $competition !== \App\Livewire\GamesBrowser::FILTER_MINI])>{{ __('Minisiatkówka') }}</button>
                @endif
            </div>
            <button type="button" x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })" class="mr-3 flex h-9 shrink-0 items-center gap-1.5 rounded-full bg-ink px-3 text-xs font-semibold text-white" aria-label="{{ __('Pokaż wszystkie filtry') }}">
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h18M6 12h12M10 19h4"/></svg>
                {{ __('Filtry') }}
            </button>
        </div>
    </div>

    {{-- Filtry: działają od razu, bez przeładowania strony; na komputerze przyklejone pod menu (na telefonie zajęłyby pół ekranu) --}}
    <div x-init="new IntersectionObserver(([entry]) => away = ! entry.isIntersecting && entry.boundingClientRect.top < 136, { rootMargin: '-136px 0px 0px 0px' }).observe($el)" class="relative border-y border-zinc-200 bg-white lg:sticky lg:top-20 lg:z-30 lg:bg-white/90 lg:backdrop-blur-xl">
        <div class="container-site space-y-3 py-3">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <label class="relative flex-1">
                    <span class="sr-only">{{ __('Szukaj meczu') }}</span>
                    <svg class="pointer-events-none absolute top-1/2 left-4 h-5 w-5 -translate-y-1/2 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                    <input type="search" wire:model.live.debounce.150ms="search" placeholder="{{ __('Szukaj: rywal, drużyna, hala, wynik, czwórki, dziecko, data…') }}" autocomplete="off" enterkeyhint="search"
                        class="h-12 w-full rounded-full border-0 bg-zinc-100 pr-4 pl-12 text-base text-zinc-900 ring-1 ring-transparent transition outline-none placeholder:text-zinc-400 focus:bg-white focus:ring-sky-club">
                </label>

                <div class="flex rounded-full bg-zinc-100 p-1 text-[13px] font-semibold sm:grid sm:grid-cols-3 sm:text-sm" role="tablist" aria-label="{{ __('Które mecze pokazać') }}">
                    @foreach(\App\Livewire\GamesBrowser::PERIODS as $key => $label)
                        <button type="button" role="tab" aria-selected="{{ $period === $key ? 'true' : 'false' }}" wire:click="setPeriod('{{ $key }}')" @class([
                            'flex flex-auto items-center justify-center gap-1 rounded-full px-2.5 py-2 whitespace-nowrap transition sm:gap-1.5 sm:px-4',
                            'bg-ink text-white shadow' => $period === $key,
                            'text-zinc-600 hover:text-zinc-900' => $period !== $key,
                        ])>
                            {{ __($label) }} <span class="text-xs tabular-nums opacity-60">{{ $periodCounts[$key] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            @php
                $chip = 'chip shrink-0 transition';
                $chipOn = 'bg-sky-club text-white shadow-sm';
                $chipOff = 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200';
                // Kafelek drużyny ma brzeg w kolorze kategorii, tak jak kafelki w kalendarzu; wybrany wypełnia się tym kolorem.
                $chipEdge = 'border-l-[5px] border-(--tile)';
                $chipTeamOn = 'bg-[color-mix(in_srgb,var(--tile)_82%,black)] text-white shadow-sm';
                $rowLabel = 'hidden w-24 shrink-0 text-[11px] font-bold tracking-wider text-zinc-600 uppercase lg:block';
                $sheetLabel = 'text-[11px] font-bold tracking-wider text-zinc-600 uppercase';
                $anyFilter = $team !== '' || $competition !== '' || $place !== '' || $search !== '' || $miniGroup !== '';
                $isMini = $competition === \App\Livewire\GamesBrowser::FILTER_MINI;
                $moreActive = (int) ($competition !== '' && ! $isMini) + (int) ($place !== '') + (int) ($season !== '');
            @endphp
            {{-- Drużyny i minisiatkówka: zawsze na wierzchu, także na telefonie --}}
            <div class="flex min-w-0 items-center gap-2">
                <span class="{{ $rowLabel }}">{{ __('Drużyna') }}</span>
                <div data-scroll-fade class="scrollbar-none -mx-4 flex min-w-0 flex-1 gap-2 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                    <button type="button" wire:click="allTeams" @class([$chip, $chipOn => $team === '' && ! $isMini, $chipOff => $team !== '' || $isMini])>{{ __('Wszystkie drużyny') }}</button>
                    @foreach($teams as $teamOption)
                        <button type="button" data-track="{{ __('Mecze') }}: {{ $teamOption->name }}" wire:click="toggleTeam('{{ $teamOption->slug }}')" aria-pressed="{{ $team === $teamOption->slug ? 'true' : 'false' }}" style="--tile: {{ \App\Support\AgeColors::forTeam($teamOption) }}" @class([$chip, $chipEdge, $chipTeamOn => $team === $teamOption->slug, $chipOff => $team !== $teamOption->slug])>{{ $teamOption->name }}</button>
                    @endforeach
                    @if($hasMini)
                        <button type="button" data-track="{{ __('Mecze') }}: {{ __('Minisiatkówka') }}" wire:click="toggleMini" aria-pressed="{{ $isMini ? 'true' : 'false' }}" style="--tile: {{ \App\Support\AgeColors::forMini(4) }}" @class([$chip, $chipEdge, $chipTeamOn => $isMini, $chipOff => ! $isMini])>{{ __('Minisiatkówka') }}</button>
                    @endif
                </div>
            </div>

            @if($isMini && $miniGroups->isNotEmpty())
                <div class="flex min-w-0 items-center gap-2">
                    <span class="{{ $rowLabel }}">{{ __('Grupa') }}</span>
                    <div data-scroll-fade class="scrollbar-none -mx-4 flex min-w-0 flex-1 gap-2 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                        <button type="button" wire:click="$set('miniGroup', '')" @class([$chip, $chipOn => $miniGroup === '', $chipOff => $miniGroup !== ''])>{{ __('Wszystkie grupy') }}</button>
                        @foreach($miniGroups as $groupOption)
                            <button type="button" wire:key="mini-group-{{ $groupOption->id }}" wire:click="setMiniGroup('{{ $groupOption->id }}')" @class([$chip, 'inline-flex items-center gap-1.5 !pl-1.5', $chipOn => $miniGroup === (string) $groupOption->id, $chipOff => $miniGroup !== (string) $groupOption->id])><x-mini-players :group="$groupOption" size="xs" />{{ $groupOption->name }}</button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Telefon: rozgrywki, dom/wyjazd i sezon w panelu od dołu; komputer: w rzędzie pod drużynami --}}
            <div x-data="{ sheet: false }" x-on:keydown.escape.window="sheet = false">
                <button type="button" x-on:click="sheet = true" class="flex h-10 w-full items-center justify-center gap-2 rounded-full text-sm font-semibold text-zinc-700 ring-1 ring-zinc-200 lg:hidden">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h18M6 12h12M10 19h4"/></svg>
                    {{ __('Więcej filtrów') }}
                    @if($moreActive)<span class="rounded-full bg-sky-club px-1.5 text-[11px] text-white tabular-nums">{{ $moreActive }}</span>@endif
                </button>
                <div x-show="sheet" x-cloak x-transition.opacity x-on:click="sheet = false" class="fixed inset-0 z-[60] bg-ink/40 backdrop-blur-sm lg:hidden"></div>
                <div :class="sheet ? 'max-lg:translate-y-0' : 'max-lg:translate-y-full max-lg:invisible'" class="flex flex-col gap-4 transition-transform duration-300 ease-out max-lg:fixed max-lg:inset-x-0 max-lg:bottom-0 max-lg:z-[61] max-lg:max-h-[85vh] max-lg:overflow-y-auto max-lg:rounded-t-3xl max-lg:bg-white max-lg:px-4 max-lg:pt-3 max-lg:pb-[max(1rem,env(safe-area-inset-bottom))] max-lg:shadow-2xl lg:grid lg:grid-cols-[1fr_auto] lg:items-center lg:gap-x-6 lg:gap-y-3" role="group" aria-label="{{ __('Więcej filtrów') }}">
                    <div class="mx-auto h-1 w-10 rounded-full bg-zinc-300 lg:hidden" aria-hidden="true"></div>
                    <p class="font-display text-xl font-semibold uppercase lg:hidden">{{ __('Filtry') }}</p>

                    @if($competitions->count() > 1 || $hasTournaments || $hasFriendlies)
                        <div class="flex min-w-0 flex-col gap-2 lg:col-span-2 lg:flex-row lg:items-center">
                            <span class="{{ $sheetLabel }} lg:w-24 lg:shrink-0">{{ __('Rozgrywki') }}</span>
                            <div data-scroll-fade class="flex min-w-0 flex-1 flex-wrap gap-2">
                                <button type="button" wire:click="setCompetition('')" @class([$chip, $chipOn => $competition === '' || $isMini, $chipOff => $competition !== '' && ! $isMini])>{{ __('Wszystkie') }}</button>
                                @foreach($competitions as $competitionOption)
                                    <button type="button" wire:key="comp-{{ $competitionOption->id }}" wire:click="setCompetition('{{ $competitionOption->id }}')" @class([$chip, $chipOn => $competition === (string) $competitionOption->id, $chipOff => $competition !== (string) $competitionOption->id])>{{ $competitionOption->name }}</button>
                                @endforeach
                                @if($hasTournaments)
                                    <button type="button" wire:click="setCompetition('{{ \App\Livewire\GamesBrowser::FILTER_TOURNAMENTS }}')" @class([$chip, $chipOn => $competition === \App\Livewire\GamesBrowser::FILTER_TOURNAMENTS, $chipOff => $competition !== \App\Livewire\GamesBrowser::FILTER_TOURNAMENTS])>{{ __('Turnieje') }}</button>
                                @endif
                                @if($hasFriendlies)
                                    <button type="button" wire:click="setCompetition('{{ \App\Livewire\GamesBrowser::FILTER_FRIENDLIES }}')" @class([$chip, $chipOn => $competition === \App\Livewire\GamesBrowser::FILTER_FRIENDLIES, $chipOff => $competition !== \App\Livewire\GamesBrowser::FILTER_FRIENDLIES])>{{ __('Sparingi') }}</button>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                        <span class="{{ $sheetLabel }} lg:w-24 lg:shrink-0">{{ __('Miejsce') }}</span>
                        <div class="flex rounded-full bg-zinc-100 p-1 text-[13px] font-semibold" role="radiogroup" aria-label="{{ __('U siebie czy na wyjeździe') }}">
                            @foreach(['' => __('Wszędzie'), 'home' => __('U siebie'), 'away' => __('Na wyjeździe')] as $placeKey => $placeLabel)
                                <button type="button" role="radio" aria-checked="{{ $place === $placeKey ? 'true' : 'false' }}" wire:click="setPlace('{{ $placeKey }}')" @class(['flex-1 rounded-full px-3 py-1.5 whitespace-nowrap transition', 'bg-ink text-white shadow' => $place === $placeKey, 'text-zinc-600 hover:text-zinc-900' => $place !== $placeKey])>{{ $placeLabel }}</button>
                            @endforeach
                        </div>
                    </div>

                    @if($seasons->count() > 1)
                        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-self-end">
                            <span class="{{ $sheetLabel }} lg:sr-only">{{ __('Sezon') }}</span>
                            <select wire:model.live="season" class="h-9 rounded-full border-0 bg-zinc-100 px-4 text-sm font-medium text-zinc-700" aria-label="{{ __('Sezon') }}">
                                @foreach($seasons as $seasonOption)
                                    <option value="{{ $seasonOption->is_current ? '' : $seasonOption->id }}">{{ __('Sezon') }} {{ $seasonOption->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($anyFilter)
                        <button type="button" wire:click="resetFilters" class="h-10 shrink-0 rounded-full text-sm font-semibold text-zinc-700 ring-1 ring-zinc-200 lg:hidden">✕ {{ __('Wyczyść filtry') }}</button>
                    @endif
                    <button type="button" x-on:click="sheet = false" class="h-12 shrink-0 rounded-full bg-ink text-sm font-bold text-white lg:hidden">{{ trans_choice('Pokaż :count mecz|Pokaż :count mecze|Pokaż :count meczów', $total) }}</button>
                </div>
            </div>
        </div>
    </div>

    <section data-games-list class="container-site scroll-mt-32 py-10 transition-opacity" wire:loading.class="opacity-50" wire:target="search,team,competition,place,season,miniGroup,setPeriod,setMonth,toggleTeam,allTeams,toggleMini,setMiniGroup,setCompetition,setPlace,resetFilters">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-zinc-500" aria-live="polite">
                @if($competition === \App\Livewire\GamesBrowser::FILTER_MINI)
                    {{ trans_choice('{1} :count turniej minisiatkówki|[2,4] :count turnieje minisiatkówki|[0,*] :count turniejów minisiatkówki', $miniTotal) }}
                @else
                    {{ trans_choice(':count mecz|:count mecze|:count meczów', $total) }}
                @endif
                @if($search !== '') {{ __('dla') }} „<span class="font-semibold text-zinc-800">{{ $search }}</span>”@endif
            </p>
            @if($hasFilters)
                <button type="button" wire:click="resetFilters" class="text-sm font-semibold text-sky-club-dark hover:underline">{{ __('Wyczyść filtry') }} ✕</button>
            @endif
        </div>

        {{-- Nasze miejsca w tabelach: kliknięcie pokazuje mecze drużyny i jej tabelę obok kalendarza --}}
        @if($ourPlaces->isNotEmpty())
            <div class="mb-6">
                <p class="text-xs font-semibold tracking-wider text-zinc-500 uppercase">{{ __('Nasze miejsca w tabelach') }}</p>
                <div data-scroll-fade class="scrollbar-none -mx-4 mt-2 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0">
                    @foreach($ourPlaces as $place)
                        @php $isActive = $team === $place['team']->slug; $isLeader = (int) $place['row']->position === 1; @endphp
                        <button type="button" wire:key="place-{{ $place['team']->id }}" wire:click="toggleTeam('{{ $place['team']->slug }}')" aria-pressed="{{ $isActive ? 'true' : 'false' }}" title="{{ $place['competition']->name }}{{ $place['row']->stage_name ? ' · '.$place['row']->stage_name : '' }}{{ $place['row']->group_name ? ' · '.$place['row']->group_name : '' }}" @if($isLeader) style="--leader: {{ $site->color('cal_first_place') }}" @endif @class([
                            'flex shrink-0 items-center gap-2.5 rounded-2xl px-3 py-2 text-left transition',
                            'bg-club-navy text-white shadow' => $isActive,
                            'bg-white text-zinc-800 ring-1 ring-zinc-200 hover:bg-zinc-50' => ! $isActive && ! $isLeader,
                            // Pierwsze miejsce: złote wyróżnienie (kolor w Ustawieniach → Kalendarz meczów).
                            'bg-linear-to-br from-(--leader)/15 to-white text-zinc-800 ring-1 ring-(--leader)/50 hover:from-(--leader)/25' => ! $isActive && $isLeader,
                        ])>
                            <span @class(['font-display text-2xl leading-none font-bold tabular-nums', 'text-sky-club-light' => $isActive && ! $isLeader, 'text-(--leader)' => $isLeader, 'text-sky-club-dark' => ! $isActive && ! $isLeader])>{{ $place['row']->position }}.</span>
                            <span class="leading-tight">
                                <span class="block text-sm font-semibold">{{ $place['team']->name }}</span>
                                <span @class(['block max-w-44 truncate text-[11px]', 'text-white/75' => $isActive, 'text-zinc-500' => ! $isActive])>{{ __('z :count drużyn', ['count' => $place['rows']->count()]) }} · {{ $place['competition']->name }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <div @class(['flex flex-col xl:grid xl:grid-cols-[minmax(0,1fr)_19rem] xl:items-start xl:gap-8' => $teamTable])>
        <div class="min-w-0">
        {{-- Miesiące jako przyciski: domyślnie bieżący, „Wszystkie” pokazuje cały sezon jedną kartką pod drugą --}}
        @if($monthCounts->count() > 1 || $selectedMonth === 'all')
            @php $multiYear = $monthCounts->keys()->map(fn ($key) => substr($key, 0, 4))->unique()->count() > 1; @endphp
            <nav data-scroll-fade class="scrollbar-none -mx-4 mb-2 flex gap-1 overflow-x-auto border-b border-zinc-200 px-4 sm:mx-0 sm:px-0" aria-label="{{ __('Miesiące') }}"
                 x-init="$nextTick(() => $el.querySelector('[aria-current]')?.scrollIntoView({ inline: 'center', block: 'nearest' }))">
                <button type="button" wire:click="setMonth('all')" @if($selectedMonth === 'all') aria-current="true" @endif @class([
                    '-mb-px shrink-0 border-b-[3px] px-3.5 pt-2 pb-2.5 text-sm font-semibold transition',
                    'border-club-navy text-club-navy' => $selectedMonth === 'all',
                    'border-transparent text-zinc-600 hover:border-zinc-300 hover:text-zinc-900' => $selectedMonth !== 'all',
                ])>{{ __('Wszystkie') }}</button>
                @foreach($monthCounts as $monthKey => $monthCount)
                    @php $monthDate = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $monthKey.'-01'); $isCurrentMonth = $monthKey === now()->format('Y-m'); $isPastMonth = $monthKey < now()->format('Y-m'); $isSelected = $selectedMonth === $monthKey; @endphp
                    {{-- Miesiące jak zakładki: wybrany podkreślony granatem, minione wyszarzone, bieżący z kropką --}}
                    <button type="button" wire:key="month-btn-{{ $monthKey }}" wire:click="setMonth('{{ $monthKey }}')" @if($isSelected) aria-current="true" @endif title="{{ $monthDate->translatedFormat('F Y') }}" @class([
                        'relative -mb-px flex shrink-0 flex-col items-center border-b-[3px] px-3 pt-1.5 pb-2 leading-tight transition',
                        'border-club-navy text-club-navy' => $isSelected,
                        'border-transparent hover:border-zinc-300' => ! $isSelected,
                        'text-zinc-400 hover:text-zinc-600' => ! $isSelected && $isPastMonth,
                        'text-zinc-700 hover:text-zinc-900' => ! $isSelected && ! $isPastMonth,
                    ])>
                        <span class="text-sm font-semibold capitalize">{{ $monthDate->translatedFormat('M') }}@if($multiYear) <span class="text-[11px] font-normal opacity-70">{{ $monthDate->format('y') }}</span>@endif</span>
                        <span class="text-[11px] tabular-nums opacity-70">{{ $monthCount ?: '–' }}</span>
                        @if($isCurrentMonth && ! $isSelected)<span class="absolute top-1 right-1 size-1.5 rounded-full bg-sky-club" aria-hidden="true"></span>@endif
                    </button>
                @endforeach
            </nav>
        @endif

        @forelse($months as $month)
            @php
                $finished = $month['games']->filter(fn ($game) => $game->isFinished());
                $wins = $finished->filter(fn ($game) => in_array($game->outcome(), ['win', 'derby'], true))->count();
                $count = $month['games']->count();
            @endphp
            <article class="mt-14 first:mt-4" wire:key="month-{{ $month['key'] }}" aria-label="{{ $month['month']->translatedFormat('F Y') }}">
                {{-- Kartka kalendarza ściennego --}}
                <div class="relative rounded-3xl bg-white shadow-xl shadow-zinc-900/5 ring-1 ring-zinc-200">
                    <div class="pointer-events-none absolute inset-x-6 -top-3 flex justify-between sm:inset-x-12" aria-hidden="true">
                        @for($ring = 0; $ring < 12; $ring++)
                            <span @class(['h-7 w-2.5 rounded-full bg-gradient-to-b from-zinc-200 via-zinc-400 to-zinc-600 ring-2 ring-white', 'hidden sm:block' => $ring % 2 === 1])></span>
                        @endfor
                    </div>

                    <header class="flex flex-wrap items-end justify-between gap-3 rounded-t-3xl bg-gradient-to-r from-club-navy via-club-navy to-sky-club px-5 pt-9 pb-5 text-white sm:px-8">
                        <h2 class="font-display leading-none font-bold uppercase">
                            <span class="block text-4xl sm:text-6xl">{{ $month['month']->translatedFormat('F') }}</span>
                            <span class="mt-1 block text-lg tracking-[0.3em] text-sky-club-light">{{ $month['month']->year }}</span>
                        </h2>
                        <p class="text-sm text-white/85">
                            {{ trans_choice(':count mecz|:count mecze|:count meczów', $count) }}@if($month['tournaments']->isNotEmpty()) · {{ trans_choice('{1} :count turniej|[2,4] :count turnieje|[0,*] :count turniejów', $month['tournaments']->count()) }}@endif @if($month['mini']->isNotEmpty()) · {{ trans_choice('{1} :count turniej mini|[2,4] :count turnieje mini|[0,*] :count turniejów mini', $month['mini']->count()) }}@endif @if($finished->isNotEmpty()) · <span class="font-semibold text-emerald-300">{{ trans_choice('{1} :count wygrana|[0,*] :count wygranych', $wins) }}</span>@endif
                        </p>
                    </header>

                    <div class="grid grid-cols-7 border-b border-zinc-200 text-center text-[11px] font-semibold tracking-wider uppercase sm:text-xs">
                        @foreach([__('Pn'), __('Wt'), __('Śr'), __('Cz'), __('Pt'), __('So'), __('Nd')] as $weekday)
                            <div @class(['py-2', 'text-red-600' => $loop->index >= 5, 'text-zinc-500' => $loop->index < 5])>{{ $weekday }}</div>
                        @endforeach
                    </div>

                    <div class="overflow-hidden rounded-b-3xl">
                        @foreach($month['weeks'] as $week)
                            <div class="grid grid-cols-7">
                                @foreach($week as $day)
                                    <div @class([
                                        'relative min-h-14 border-b border-zinc-100 p-1 sm:min-h-20 sm:p-2 lg:min-h-32',
                                        'border-r' => ! $loop->last,
                                        'bg-zinc-50/70' => ! $day['inMonth'],
                                        'bg-sky-club/5' => $day['inMonth'] && $day['tiles']->isNotEmpty(),
                                    ])>
                                        <p @class([
                                            'font-display text-base leading-none font-semibold tabular-nums sm:text-2xl',
                                            'text-zinc-300' => ! $day['inMonth'],
                                            'text-red-600' => $day['inMonth'] && $day['date']->isWeekend(),
                                            'text-zinc-800' => $day['inMonth'] && ! $day['date']->isWeekend(),
                                        ])>
                                            <span @class(['inline-flex h-7 min-w-7 items-center justify-center rounded-full sm:h-9 sm:min-w-9', 'bg-sky-club text-white' => $day['isToday']])>{{ $day['date']->day }}</span>
                                        </p>

                                        @if($day['inMonth'] && ($day['tiles']->isNotEmpty() || $day['birthdays']->isNotEmpty()))
                                            <div class="mt-1 space-y-1 md:mt-1.5">
                                                @foreach($day['tiles'] as $tile)
                                                    <x-calendar.tile :tile="$tile" wire:key="tile-{{ $tile['key'] }}" />
                                                @endforeach
                                                @foreach($day['birthdays'] as $person)
                                                    <a href="#urodziny-{{ $month['key'] }}" class="block truncate rounded-lg bg-(--cal-bday-bg) px-1.5 py-1 text-[11px] leading-tight text-(--cal-bday-text)" title="{{ __('Urodziny') }}: {{ $person['name'] }}{{ $person['showAge'] ? ' '.__('kończy').' '.$person['ageLabel'] : '' }} ({{ __($person['role']) }})">
                                                        {{ $birthdayIcon }}<span class="hidden md:inline"> <span class="font-semibold">{{ $person['name'] }}</span>{{ $person['showAge'] ? ' · '.$person['age'] : '' }}</span>
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Telefon i tablet: szczegóły meczów miesiąca pod kartką --}}
                <ul class="mt-4 space-y-3 lg:hidden">
                @foreach($month['games'] as $game)
                    @php
                        $result = $game->ourSetsResult();
                        $outcome = $game->outcome();
                        $won = $outcome && $outcome !== 'derby' ? $outcome === 'win' : null;
                        $ourLabel = $game->ourLabel();
                        $opponentName = $game->opponentShortName();
                        $article = $game->latestArticle();
                        $hasTime = $game->game_date && $game->game_date->format('H:i') !== '00:00';
                    @endphp
                    <li id="mecz-{{ $game->id }}" wire:key="game-{{ $game->id }}" class="card scroll-mt-24 lg:scroll-mt-48 grid grid-cols-[3.75rem_1fr] items-center gap-4 p-4 sm:grid-cols-[4.5rem_1fr_auto] sm:gap-6 sm:p-5">
                        <div @class(['rounded-2xl py-2 text-center leading-none', 'bg-(--cal-home-bg) text-(--cal-home-text) ring-1 ring-(--cal-home-border)' => $game->is_home, 'bg-(--cal-away-bg) text-(--cal-away-text) ring-1 ring-(--cal-away-border)' => ! $game->is_home])>
                            <p class="text-[11px] font-semibold uppercase opacity-70">{{ $game->game_date?->translatedFormat('D') }}</p>
                            <p class="mt-1 font-display text-2xl font-bold tabular-nums">{{ $game->game_date?->format('d') }}</p>
                            <p class="mt-1 text-[11px] font-semibold tabular-nums opacity-80">{{ $hasTime ? $game->game_date->format('H:i') : '--:--' }}</p>
                        </div>

                        <div class="min-w-0">
                            <p class="flex min-w-0 items-center gap-2 text-xs font-semibold tracking-wide text-zinc-500 uppercase">
                                @if($ourLabel['badge'])<x-team-chip :name="$ourLabel['badge']" class="text-zinc-700" />@endif
                                <span class="truncate">{{ $game->competitionLabel() }}@if($game->round_label) · {{ $game->round_label }}@endif</span>
                            </p>
                            <div class="mt-1.5 flex flex-wrap items-baseline gap-x-3 gap-y-1 font-display text-lg leading-tight font-semibold uppercase sm:text-xl">
                                @if($game->is_home)
                                    <x-team-label :label="$ourLabel" :badge="false" class="text-zinc-900" />
                                @else
                                    <span class="text-zinc-500">{{ $opponentName }}</span>
                                @endif
                                @if($game->isFinished())
                                    <span class="rounded-lg bg-ink px-2 py-0.5 text-base text-white tabular-nums">{{ $game->home_score }}:{{ $game->away_score }}</span>
                                @else
                                    <span class="text-sm text-zinc-400 normal-case">{{ __('vs') }}</span>
                                @endif
                                @if($game->is_home)
                                    <span class="text-zinc-500">{{ $opponentName }}</span>
                                @else
                                    <x-team-label :label="$ourLabel" :badge="false" class="text-zinc-900" />
                                @endif
                            </div>
                            @if($game->isFinished() && is_array($game->sets_score) && count($game->sets_score))
                                <p class="mt-1 text-xs text-zinc-500 tabular-nums">{{ collect($game->sets_score)->map(fn ($set) => implode(':', (array) $set))->implode(' · ') }}@if($game->result_note) · <span class="font-semibold text-amber-700">{{ $game->result_note }}</span>@endif@if($game->mvp) · <span class="font-semibold text-amber-700">★ {{ $game->mvp }}</span>@endif</p>
                            @elseif($game->result_note)
                                <p class="mt-1 text-xs font-semibold text-amber-700">{{ $game->result_note }}</p>
                            @endif
                            @if($game->venue)
                                <p class="mt-1 flex items-center gap-1 text-xs text-zinc-500"><svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.2-7-11.5a7 7 0 1 1 14 0C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/></svg><span class="truncate">{{ $game->venue }}</span></p>
                            @endif
                        </div>

                        <div class="col-span-2 flex items-center justify-between gap-3 sm:col-span-1 sm:flex-col sm:items-end">
                            @if($outcome === 'derby')
                                <span class="inline-flex items-center gap-1 rounded-full bg-sky-club/15 px-3 py-1 text-xs font-bold text-sky-club-dark">
                                    <x-derby-heart class="h-3.5 w-3.5 text-sky-club" />
                                    {{ $site['derby_winner'] }} {{ $result[0] }}:{{ $result[1] }}
                                </span>
                            @elseif($won !== null)
                                <span @class(['inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold', 'bg-emerald-500/15 text-emerald-700' => $won, 'bg-red-500/10 text-red-600' => ! $won])>
                                    @if($won)
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7"/></svg>
                                    @else
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
                                    @endif
                                    {{ $won ? __('Wygrana') : __('Porażka') }} {{ $result[0] }}:{{ $result[1] }}
                                </span>
                            @else
                                <span class="shrink-0 whitespace-nowrap rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600">{{ $game->is_home ? __('U siebie') : __('Wyjazd') }}</span>
                            @endif
                            <a href="{{ route('games.show', $game) }}" class="inline-flex min-h-9 items-center gap-1.5 text-xs font-semibold text-sky-club-dark hover:underline">{{ __('Szczegóły meczu') }} →</a>
                            @if($article)
                                <a href="{{ route('articles.show', $article) }}" class="inline-flex min-h-9 items-center gap-1.5 text-xs font-semibold text-sky-club-dark hover:underline">{{ __('Czytaj artykuł') }} →</a>
                            @endif
                            @if($game->isUpcoming())
                                <a href="{{ route('games.calendar', $game) }}" class="inline-flex min-h-9 items-center gap-1.5 text-xs font-semibold text-sky-club-dark hover:underline">+ {{ __('Do kalendarza') }}</a>
                            @endif
                        </div>
                    </li>
                @endforeach
                @foreach($month['mini'] as $trip)
                    <li id="mini-{{ $trip->id }}" wire:key="list-mini-{{ $trip->id }}" class="card scroll-mt-24 lg:scroll-mt-48 bg-emerald-50 p-4 pl-5 text-sm text-emerald-950 ring-emerald-200" @if($tint = $trip->tintStyle()) style="{{ $tint }}" @endif>
                        <p class="text-xs font-semibold tracking-wide text-emerald-700 uppercase">{{ $trip->dateLabel() }}{{ $trip->city ? ' · '.$trip->city : '' }}</p>
                        <p class="mt-1 flex flex-wrap items-center gap-2 font-display text-lg font-semibold uppercase">@foreach($trip->teams->pluck('group')->filter()->unique('id') as $miniGroup)<a href="{{ $trip->url($miniGroup) }}" title="{{ $miniGroup->name }}"><x-mini-players :group="$miniGroup" size="sm" /></a>@endforeach<a href="{{ $trip->url() }}" class="hover:underline">{{ $trip->title }}</a></p>
                        @if($trip->venue)
                            <p class="text-xs text-emerald-800">{{ $trip->venue }}</p>
                        @endif
                        <ul class="mt-3 space-y-2">
                            @foreach($trip->teams->sortBy(fn ($team) => [-(int) $team->group?->players_on_court, $team->place ?? 99]) as $miniTeam)
                                <li>
                                    <details class="group/mini rounded-xl bg-white/70 ring-1 ring-emerald-200">
                                        <summary class="flex cursor-pointer list-none items-center gap-3 px-3 py-2 [&::-webkit-details-marker]:hidden">
                                            @if($miniTeam->place)
                                                <span @class(['flex size-9 shrink-0 items-center justify-center rounded-full font-display text-lg font-bold', 'bg-amber-300 text-amber-950' => $miniTeam->place === 1, 'bg-zinc-200 text-zinc-800' => $miniTeam->place === 2, 'bg-orange-300 text-orange-950' => $miniTeam->place === 3, 'bg-emerald-100 text-emerald-900' => $miniTeam->place > 3])>{{ $miniTeam->place }}.</span>
                                            @else
                                                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">–</span>
                                            @endif
                                            <span class="min-w-0 flex-1">
                                                <span class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-emerald-700 uppercase" @if($miniTeam->group) style="color: color-mix(in srgb, {{ $miniTeam->group->iconColor() }} 60%, black)" @endif>@if($miniTeam->group)<x-mini-players :group="$miniTeam->group" size="xs" />@endif{{ $miniTeam->group?->name }}{{ $miniTeam->teams_count ? ' | '.$miniTeam->fieldLabel() : '' }}</span>
                                                <span class="block font-semibold">{{ $miniTeam->name }}</span>
                                            </span>
                                            @if($site['mini_show_lineups'] && $miniTeam->players->isNotEmpty())
                                                <span class="text-xs text-emerald-700 group-open/mini:rotate-180">▾</span>
                                            @endif
                                        </summary>
                                        @if($site['mini_show_lineups'] && $miniTeam->players->isNotEmpty())
                                            <p class="border-t border-emerald-100 px-3 py-2 text-emerald-900">{{ $miniTeam->players->map->displayName()->implode(', ') }}</p>
                                        @endif
                                    </details>
                                </li>
                            @endforeach
                        </ul>
                        @foreach($trip->articles as $article)
                            <a href="{{ route('articles.show', $article) }}" class="mt-3 mr-4 inline-block text-xs font-semibold text-emerald-800 underline">📰 {{ __('Relacja') }} →</a>
                        @endforeach
                        <a href="{{ route('mini.index') }}" class="mt-3 inline-block text-xs font-semibold text-emerald-800 underline">{{ __('Strona minisiatkówki') }} →</a>
                    </li>
                @endforeach
                @foreach($month['tournaments'] as $tournament)
                    <li id="turniej-{{ $tournament->id }}" wire:key="list-tournament-{{ $tournament->id }}" class="card scroll-mt-24 lg:scroll-mt-48 bg-amber-50 p-4 text-sm text-amber-950 ring-amber-200">
                        <p class="text-xs font-semibold tracking-wide text-amber-700 uppercase">{{ __('Turniej') }} · {{ $tournament->dateRangeLabel() }}{{ $tournament->is_organizer ? ' · '.__('organizujemy') : '' }}</p>
                        <p class="mt-1 font-display text-lg font-semibold uppercase">🏆 {{ $tournament->fullName() }}</p>
                        @if($tournament->has_page)
                            <a href="{{ route('tournaments.show', $tournament) }}" class="mt-1 inline-block text-xs font-semibold text-amber-800 underline" data-track="Kalendarz: strona turnieju">{{ __('Strona turnieju: grupy, wyniki, informator') }} →</a>
                        @endif
                        @if($tournament->venue || $tournament->city)
                            <p class="text-xs text-amber-800">{{ collect([$tournament->venue, $tournament->city])->filter()->implode(', ') }}</p>
                        @endif
                        <ul class="mt-2 space-y-0.5">
                            @foreach($tournament->teams as $tournamentTeam)
                                <li>{{ $tournamentTeam->name }}@if($tournamentTeam->pivot->placement) · <strong>{{ $tournamentTeam->pivot->placement }}</strong>@endif</li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
                @if($month['birthdays']->isNotEmpty())
                    <li id="urodziny-{{ $month['key'] }}" class="card scroll-mt-24 lg:scroll-mt-48 bg-(--cal-bday-bg) p-4 text-sm text-(--cal-bday-text) ring-(--cal-bday-text)/20">
                        <p class="font-semibold">{{ $birthdayIcon }} {{ __('Urodziny w tym miesiącu') }}</p>
                        <ul class="mt-2 space-y-1">
                            @foreach($month['birthdays'] as $person)
                                <li><span class="tabular-nums">{{ $person['date']->format('d.m') }}</span> · @if($person['url'])<a href="{{ $person['url'] }}" class="font-semibold hover:underline">{{ $person['name'] }}</a>@else<span class="font-semibold">{{ $person['name'] }}</span>@endif{{ $person['showAge'] ? ' · '.__('kończy').' '.$person['ageLabel'] : '' }}</li>
                            @endforeach
                        </ul>
                    </li>
                @endif
            </ul>
            </article>
        @empty
            <div class="card p-12 text-center">
                <p class="font-display text-2xl font-semibold text-zinc-900 uppercase">{{ __('Brak meczów') }}</p>
                <p class="mt-2 text-zinc-500">{{ $hasFilters ? __('Żaden mecz nie pasuje do wybranych filtrów.') : __('Terminarz pojawi się tu, gdy tylko zostanie ogłoszony.') }}</p>
                @if($hasFilters)
                    <button type="button" wire:click="resetFilters" class="btn-primary mt-6">{{ __('Pokaż wszystkie mecze') }}</button>
                @elseif($period === 'upcoming' && $periodCounts['results'] > 0)
                    <button type="button" wire:click="setPeriod('results')" class="btn-primary mt-6">{{ __('Zobacz wyniki') }}</button>
                @endif
            </div>
        @endforelse
        </div>

        {{-- Skrócona tabela wybranej drużyny: obok kalendarza na dużym ekranie, rozwijana nad kalendarzem na telefonie --}}
        @if($teamTable)
            <aside wire:key="team-table-{{ $teamTable['row']->id }}" class="order-first mb-6 xl:sticky xl:top-64 xl:order-none xl:mb-0" x-data="{ open: window.matchMedia('(min-width: 1280px)').matches }">
                <div class="card overflow-hidden">
                    <button type="button" x-on:click="open = ! open" :aria-expanded="open" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left">
                        <span class="min-w-0">
                            <span class="block text-xs font-semibold tracking-wider text-zinc-500 uppercase">{{ __('Tabela') }}</span>
                            <span class="block truncate font-semibold text-zinc-900">{{ $teamTable['competition']->name }}</span>
                            @if($teamTable['row']->stage_name || $teamTable['row']->group_name)
                                <span class="block truncate text-xs text-zinc-500">{{ collect([$teamTable['row']->stage_name, $teamTable['row']->group_name])->filter()->join(' · ') }}</span>
                            @endif
                        </span>
                        <span class="flex shrink-0 items-center gap-2">
                            <span class="rounded-full bg-sky-club/15 px-2.5 py-1 text-sm font-bold text-sky-club-dark">{{ $teamTable['row']->position }}. {{ __('miejsce') }}</span>
                            <span class="text-sm text-zinc-400 transition" :class="open && 'rotate-180'" aria-hidden="true">▾</span>
                        </span>
                    </button>
                    <div x-show="open" x-collapse>
                        <table class="w-full border-t border-zinc-100 text-sm tabular-nums">
                            <thead class="text-[11px] font-semibold tracking-wider text-zinc-500 uppercase">
                                <tr>
                                    <th class="py-2 pr-1 pl-4 text-left" scope="col">#</th>
                                    <th class="px-1 py-2 text-left" scope="col">{{ __('Drużyna') }}</th>
                                    <th class="px-1 py-2 text-center" scope="col" title="{{ __('Mecze') }}">{{ __('M') }}</th>
                                    <th class="py-2 pr-4 pl-1 text-center" scope="col">{{ __('Pkt') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                @foreach($teamTable['rows'] as $tableRow)
                                    @php $isOurs = $tableRow->isOwnTeam(); @endphp
                                    <tr wire:key="tt-{{ $tableRow->id }}" @class(['bg-sky-club/10 font-semibold' => $isOurs])>
                                        <td class="py-2 pr-1 pl-4 text-zinc-500">{{ $tableRow->position }}</td>
                                        <td class="max-w-0 truncate px-1 py-2 text-zinc-800" title="{{ $tableRow->competitorName() }}">{{ $isOurs && $tableRow->competitor ? $tableRow->competitor->name : $tableRow->competitorShortName() }}</td>
                                        <td class="px-1 py-2 text-center text-zinc-500">{{ $tableRow->played }}</td>
                                        <td class="py-2 pr-4 pl-1 text-center font-bold text-sky-club-dark">{{ $tableRow->points }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <a href="{{ route('schedule.index', ['tab' => 'tables', 'league' => $teamTable['competition']->getRouteKey()]) }}" class="block border-t border-zinc-100 px-4 py-3 text-sm font-semibold text-sky-club-dark hover:bg-zinc-50">{{ __('Pełna tabela') }} →</a>
                    </div>
                </div>
            </aside>
        @endif
        </div>
    </section>

    {{-- Okno szczegółów meczów z kafelka kalendarza (wyrasta z klikniętego kafelka) --}}
    <div wire:ignore x-data="calendarDetails" x-on:match-details.window="open($event.detail)" x-on:keydown.escape.window="tile && close()">
        <template x-if="tile">
            <div class="fixed inset-0 z-[70] flex items-end justify-center p-3 sm:items-center sm:p-6" role="dialog" aria-modal="true" :aria-label="tile.label">
                <div class="absolute inset-0 bg-ink/50 backdrop-blur-sm" x-on:click="close()" x-transition.opacity></div>
                <div x-ref="panel" class="relative flex max-h-[88vh] w-full max-w-lg origin-center flex-col overflow-hidden rounded-3xl bg-white shadow-2xl" :style="`--tile: ${tile.color}`">
                    <div class="flex items-start justify-between gap-3 bg-club-navy px-5 pt-4 pb-3.5 text-white">
                        <div class="min-w-0">
                            <p class="truncate text-[11px] font-semibold tracking-[0.16em] text-white/70 uppercase" x-text="tile.competition"></p>
                            <p class="mt-0.5 font-display text-2xl leading-tight font-semibold uppercase" x-text="tile.label"></p>
                        </div>
                        <button type="button" data-close x-on:click="close()" class="grid size-9 shrink-0 place-items-center rounded-full bg-white/10 text-lg hover:bg-white/20" aria-label="{{ __('Zamknij') }}">✕</button>
                    </div>
                    <div class="space-y-3 overflow-y-auto p-4">
                        <template x-for="(match, index) in tile.matches" :key="match.id">
                            <article class="calendar-details-rise rounded-2xl p-4 ring-1 ring-zinc-200" :style="`animation-delay: ${80 + index * 90}ms`">
                                <div class="flex items-center justify-between gap-2 text-xs text-zinc-500">
                                    <span class="rounded-full bg-(--tile) px-2.5 py-0.5 text-[10px] font-bold tracking-wider text-white uppercase" x-text="tile.label"></span>
                                    <span class="truncate font-medium" x-text="match.date"></span>
                                </div>
                                <div class="mt-3 grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                                    <template x-for="side in ['home', 'away']" :key="side">
                                        <div class="flex flex-col items-center gap-1.5 text-center text-sm leading-tight font-semibold text-zinc-900" :class="side === 'away' && 'order-3'">
                                            <span class="relative grid size-14 place-items-center overflow-hidden rounded-full" :class="match[side].logo ? '' : 'bg-gradient-to-br from-graphite to-ink'">
                                                <template x-if="match[side].logo"><img :src="match[side].logo" alt="" class="size-full object-contain"></template>
                                                <template x-if="! match[side].logo"><span class="font-display text-lg font-bold text-sky-club" x-text="initials(match[side].name)"></span></template>
                                            </span>
                                            <span x-text="match[side].name"></span>
                                        </div>
                                    </template>
                                    <div class="order-2 text-center">
                                        <p class="font-display text-3xl leading-none font-bold tabular-nums" :class="resultClass(match)" x-text="match.mid || '{{ __('vs') }}'"></p>
                                        <p class="mt-1 text-[11px] font-semibold text-zinc-500" x-text="match.outcomeLabel"></p>
                                    </div>
                                </div>
                                <p x-show="match.sets" class="mt-2 text-center text-xs text-zinc-500 tabular-nums" x-text="'{{ __('Sety') }}: ' + match.sets"></p>
                                <p x-show="match.note" class="mt-1 text-center text-xs font-semibold text-amber-700" x-text="match.note"></p>
                                <p class="mt-3 text-xs text-zinc-500"><span x-text="[match.competition, match.round].filter(Boolean).join(' · ')"></span><template x-if="match.venue"><span> · <span x-text="match.venue"></span></span></template></p>
                                <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                                    <template x-if="match.url"><a :href="match.url" class="rounded-full bg-sky-club/10 px-3 py-1.5 text-sky-club-dark hover:bg-sky-club/20">{{ __('Szczegóły meczu') }} →</a></template>
                                    <template x-if="match.article"><a :href="match.article" class="rounded-full bg-sky-club/10 px-3 py-1.5 text-sky-club-dark hover:bg-sky-club/20">{{ __('Czytaj artykuł') }} →</a></template>
                                    <template x-if="match.calendar"><a :href="match.calendar" class="rounded-full bg-zinc-100 px-3 py-1.5 text-zinc-700 hover:bg-zinc-200">+ {{ __('Do kalendarza') }}</a></template>
                                </div>
                            </article>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

@php $birthdayIcon = trim((string) $site['cal_birthday_icon']) ?: '🎂'; @endphp
<div style="--cal-home-bg: {{ $site->color('cal_home_bg') }}; --cal-home-border: {{ $site->color('cal_home_border') }}; --cal-home-text: {{ $site->color('cal_home_text') }}; --cal-away-bg: {{ $site->color('cal_away_bg') }}; --cal-away-text: {{ $site->color('cal_away_text') }}; --cal-away-border: {{ $site->color('cal_away_border') }}; --cal-bday-bg: {{ $site->color('cal_birthday_bg') }}; --cal-bday-text: {{ $site->color('cal_birthday_text') }};">
    {{-- Filtry: działają od razu, bez przeładowania strony; na komputerze przyklejone pod menu (na telefonie zajęłyby pół ekranu) --}}
    <div class="relative z-30 lg:sticky border-y border-zinc-200 bg-white/90 backdrop-blur-xl lg:top-20">
        <div class="container-site space-y-3 py-3">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <label class="relative flex-1">
                    <span class="sr-only">{{ __('Szukaj meczu') }}</span>
                    <svg class="pointer-events-none absolute top-1/2 left-4 h-5 w-5 -translate-y-1/2 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                    <input type="search" wire:model.live.debounce.150ms="search" placeholder="{{ __('Szukaj: rywal, drużyna, hala, data (np. 28.10)…') }}" autocomplete="off" enterkeyhint="search"
                        class="h-12 w-full rounded-full border-0 bg-zinc-100 pr-4 pl-12 text-base text-zinc-900 ring-1 ring-transparent transition outline-none placeholder:text-zinc-400 focus:bg-white focus:ring-sky-club">
                </label>

                <div class="grid grid-cols-3 rounded-full bg-zinc-100 p-1 text-[13px] font-semibold sm:text-sm" role="tablist" aria-label="{{ __('Które mecze pokazać') }}">
                    @foreach(\App\Livewire\GamesBrowser::PERIODS as $key => $label)
                        <button type="button" role="tab" aria-selected="{{ $period === $key ? 'true' : 'false' }}" wire:click="setPeriod('{{ $key }}')" @class([
                            'flex items-center justify-center gap-1 rounded-full px-2 py-2 transition sm:gap-1.5 sm:px-4',
                            'bg-ink text-white shadow' => $period === $key,
                            'text-zinc-600 hover:text-zinc-900' => $period !== $key,
                        ])>
                            {{ __($label) }} <span class="text-xs tabular-nums opacity-60">{{ $periodCounts[$key] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="scrollbar-none -mx-4 flex min-w-0 flex-1 basis-full gap-2 overflow-x-auto px-4 sm:mx-0 sm:basis-auto sm:px-0">
                    <button type="button" wire:click="$set('team', '')" @class(['chip shrink-0 transition', 'bg-sky-club text-white' => $team === '', 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200' => $team !== ''])>{{ __('Wszystkie drużyny') }}</button>
                    @foreach($teams as $teamOption)
                        <button type="button" data-track="{{ __('Mecze') }}: {{ $teamOption->name }}" wire:click="toggleTeam('{{ $teamOption->slug }}')" @class(['chip shrink-0 transition', 'bg-sky-club text-white' => $team === $teamOption->slug, 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200' => $team !== $teamOption->slug])>{{ $teamOption->name }}</button>
                    @endforeach
                </div>

                <div class="flex w-full flex-wrap gap-2 sm:w-auto">
                    @if($competitions->count() > 1 || $hasTournaments || $hasFriendlies || $hasMini)
                        <select wire:model.live="competition" class="h-9 min-w-0 flex-1 rounded-full border-0 bg-zinc-100 px-4 text-sm font-medium text-zinc-700 sm:flex-none" aria-label="{{ __('Rozgrywki') }}">
                            <option value="">{{ __('Wszystkie rozgrywki') }}</option>
                            @foreach($competitions as $competitionOption)
                                <option value="{{ $competitionOption->id }}">{{ $competitionOption->name }}</option>
                            @endforeach
                            @if($hasTournaments)
                                <option value="{{ \App\Livewire\GamesBrowser::FILTER_TOURNAMENTS }}">{{ __('Turnieje') }}</option>
                            @endif
                            @if($hasFriendlies)
                                <option value="{{ \App\Livewire\GamesBrowser::FILTER_FRIENDLIES }}">{{ __('Sparingi') }}</option>
                            @endif
                            @if($hasMini)
                                <option value="{{ \App\Livewire\GamesBrowser::FILTER_MINI }}">{{ __('Minisiatkówka') }}</option>
                            @endif
                        </select>
                    @endif
                    <select wire:model.live="place" class="h-9 flex-1 rounded-full border-0 bg-zinc-100 px-4 text-sm font-medium text-zinc-700 sm:flex-none" aria-label="{{ __('U siebie czy na wyjeździe') }}">
                        <option value="">{{ __('Dom i wyjazd') }}</option>
                        <option value="home">{{ __('U siebie') }}</option>
                        <option value="away">{{ __('Na wyjeździe') }}</option>
                    </select>
                    @if($seasons->count() > 1)
                        <select wire:model.live="season" class="h-9 flex-1 rounded-full border-0 bg-zinc-100 px-4 text-sm font-medium text-zinc-700 sm:flex-none" aria-label="{{ __('Sezon') }}">
                            @foreach($seasons as $seasonOption)
                                <option value="{{ $seasonOption->is_current ? '' : $seasonOption->id }}">{{ __('Sezon') }} {{ $seasonOption->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <section class="container-site py-10 transition-opacity" wire:loading.class="opacity-50" wire:target="search,team,competition,place,season,setPeriod,toggleTeam,resetFilters">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-zinc-500" aria-live="polite">
                {{ trans_choice(':count mecz|:count mecze|:count meczów', $total) }}
                @if($search !== '') {{ __('dla') }} „<span class="font-semibold text-zinc-800">{{ $search }}</span>”@endif
            </p>
            @if($hasFilters)
                <button type="button" wire:click="resetFilters" class="text-sm font-semibold text-sky-club-dark hover:underline">{{ __('Wyczyść filtry') }} ✕</button>
            @endif
        </div>

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
                                        'min-h-14 border-b border-zinc-100 p-1 sm:min-h-20 sm:p-2 lg:min-h-32',
                                        'border-r' => ! $loop->last,
                                        'bg-zinc-50/70' => ! $day['inMonth'],
                                        'bg-sky-club/5' => $day['inMonth'] && $day['games']->isNotEmpty(),
                                    ])>
                                        <p @class([
                                            'font-display text-base leading-none font-semibold tabular-nums sm:text-2xl',
                                            'text-zinc-300' => ! $day['inMonth'],
                                            'text-red-600' => $day['inMonth'] && $day['date']->isWeekend(),
                                            'text-zinc-800' => $day['inMonth'] && ! $day['date']->isWeekend(),
                                        ])>
                                            <span @class(['inline-flex h-7 min-w-7 items-center justify-center rounded-full sm:h-9 sm:min-w-9', 'bg-sky-club text-white' => $day['isToday']])>{{ $day['date']->day }}</span>
                                        </p>

                                        @if($day['inMonth'] && ($day['games']->isNotEmpty() || $day['tournaments']->isNotEmpty() || $day['mini']->isNotEmpty() || $day['birthdays']->isNotEmpty()))
                                            {{-- Telefon i tablet: znaczniki (granat = u siebie, szary = wyjazd, V/X = wynik), dotknięcie przewija do meczu na liście --}}
                                            <div class="mt-1 flex flex-wrap gap-1 lg:hidden">
                                                @foreach($day['games'] as $game)
                                                    @php $outcome = $game->outcome(); $won = $outcome === 'derby' ? null : ($outcome ? $outcome === 'win' : null); @endphp
                                                    <a href="#mecz-{{ $game->id }}" @class([
                                                        'inline-flex h-4 w-4 items-center justify-center rounded sm:h-5 sm:w-5',
                                                        'bg-(--cal-home-bg) ring-1 ring-(--cal-home-border)' => $game->is_home,
                                                        'bg-(--cal-away-bg) ring-1 ring-(--cal-away-border)' => ! $game->is_home,
                                                    ]) aria-label="{{ $game->is_home ? __('U siebie') : __('Wyjazd') }}: {{ $game->team?->name }} - {{ $game->opponentShortName() }}">
                                                        @if($outcome === 'derby')
                                                            <x-derby-heart class="h-3 w-3 text-sky-club-light" />
                                                        @elseif($won === true)
                                                            <svg class="h-3 w-3 text-emerald-400" fill="none" stroke="currentColor" stroke-width="4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7"/></svg>
                                                        @elseif($won === false)
                                                            <svg class="h-3 w-3 text-red-500" fill="none" stroke="currentColor" stroke-width="4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
                                                        @endif
                                                    </a>
                                                @endforeach
                                                @foreach($day['mini'] as $trip)
                                                    <a href="#mini-{{ $trip->id }}" class="text-xs leading-4 sm:text-sm" aria-label="{{ __('Minisiatkówka') }}: {{ $trip->title }}">🏐</a>
                                                @endforeach
                                                @foreach($day['tournaments'] as $tournament)
                                                    <a href="#turniej-{{ $tournament->id }}" class="text-xs leading-4 sm:text-sm" aria-label="{{ __('Turniej') }}: {{ $tournament->fullName() }}">🏆</a>
                                                @endforeach
                                                @foreach($day['birthdays'] as $person)
                                                    <a href="#urodziny-{{ $month['key'] }}" class="text-xs leading-4 sm:text-sm" aria-label="{{ __('Urodziny') }}: {{ $person['name'] }}">{{ $birthdayIcon }}</a>
                                                @endforeach
                                            </div>

                                            {{-- Komputer: mecze wpisane w kartkę; granat = u siebie, szary = wyjazd --}}
                                            <ul class="mt-1.5 hidden space-y-1 lg:block">
                                                @foreach($day['mini'] as $trip)
                                                    <li wire:key="cell-mini-{{ $trip->id }}" class="rounded-lg bg-gradient-to-br from-emerald-100 to-emerald-50 px-1.5 py-1 text-[11px] leading-tight text-emerald-950 ring-1 ring-emerald-300" title="{{ $trip->title }}{{ $trip->venue ? ' · '.$trip->venue : '' }}">
                                                        <p class="font-semibold"><a href="{{ route('mini.index') }}" class="hover:underline">🏐 {{ $trip->title }}</a></p>
                                                        <p class="text-emerald-800">{{ $trip->city }}</p>
                                                        @foreach($trip->teams as $miniTeam)
                                                            <details class="group/mini">
                                                                <summary class="cursor-pointer list-none truncate hover:underline [&::-webkit-details-marker]:hidden"><span class="text-emerald-700">{{ $miniTeam->group?->name }}</span> {{ $miniTeam->name }}@if($miniTeam->place) · <strong>{{ $miniTeam->place }}.</strong>@if($miniTeam->teams_count)<span class="text-emerald-700">/{{ $miniTeam->teams_count }}</span>@endif @endif</summary>
                                                                @if($site['mini_show_lineups'] && $miniTeam->players->isNotEmpty())
                                                                    <p class="mt-0.5 rounded bg-white/70 px-1 py-0.5 text-emerald-900">{{ $miniTeam->players->map->displayName()->implode(', ') }}</p>
                                                                @endif
                                                            </details>
                                                        @endforeach
                                                    </li>
                                                @endforeach
                                                @foreach($day['tournaments'] as $tournament)
                                                    <li wire:key="cell-tournament-{{ $tournament->id }}-{{ $day['date']->format('d') }}" class="rounded-lg bg-gradient-to-br from-amber-100 to-amber-50 px-1.5 py-1 text-[11px] leading-tight text-amber-950 ring-1 ring-amber-300" title="{{ $tournament->fullName() }} · {{ $tournament->dateRangeLabel() }}{{ $tournament->city ? ' · '.$tournament->city : '' }}">
                                                        <p class="font-semibold">@if($tournament->has_page)<a href="{{ route('tournaments.show', $tournament) }}" class="hover:underline">🏆 {{ $tournament->fullName() }}</a>@else🏆 {{ $tournament->fullName() }}@endif</p>
                                                        <p class="text-amber-800">{{ $tournament->is_organizer ? __('organizujemy') : ($tournament->city ?? __('wyjazd')) }}</p>
                                                        @foreach($tournament->teams as $tournamentTeam)
                                                            <p class="truncate">{{ $tournamentTeam->name }}@if($tournamentTeam->pivot->placement) · <strong>{{ $tournamentTeam->pivot->placement }}</strong>@endif</p>
                                                        @endforeach
                                                    </li>
                                                @endforeach
                                                @foreach($day['games'] as $game)
                                                    @php
                                                        $result = $game->ourSetsResult();
                                                        $outcome = $game->outcome();
                                                        $won = $outcome && $outcome !== 'derby' ? $outcome === 'win' : null;
                                                        $hasTime = $game->game_date->format('H:i') !== '00:00';
                                                        $article = $game->latestArticle();
                                                        $tag = 'a';
                                                    @endphp
                                                    <li wire:key="cell-{{ $game->id }}">
                                                        <{{ $tag }} href="{{ route('games.show', $game) }}" title="{{ $game->is_home ? __('U siebie') : __('Wyjazd') }} · {{ $game->competitionLabel() }}@if($game->round_label) · {{ $game->round_label }}@endif{{ $game->venue ? ' · '.$game->venue : '' }}{{ is_array($game->sets_score) && count($game->sets_score) ? ' · '.collect($game->sets_score)->map(fn ($set) => implode(':', (array) $set))->implode(', ') : '' }}{{ $article ? ' · '.__('Artykuł').': '.$article->localizedTitle() : '' }}" @class([
                                                            'block rounded-lg px-1.5 py-1 text-[11px] leading-tight transition',
                                                            'bg-(--cal-home-bg) text-(--cal-home-text) ring-1 ring-(--cal-home-border) shadow-sm' => $game->is_home,
                                                            'bg-(--cal-away-bg) text-(--cal-away-text) shadow-sm ring-1 ring-(--cal-away-border)' => ! $game->is_home,
                                                            'hover:ring-2 hover:ring-sky-club' => true,
                                                        ])>
                                                            <span class="flex items-start justify-between gap-1">
                                                                <x-team-label :label="$game->ourLabel()" class="min-w-0 font-semibold break-words" />
                                                                <span class="flex shrink-0 items-center gap-0.5 font-bold tabular-nums">
                                                                    @if($outcome === 'derby')
                                                                        <x-derby-heart class="h-3.5 w-3.5 {{ $game->is_home ? 'text-sky-club-light' : 'text-sky-club' }}" />
                                                                    @elseif($won === true)
                                                                        <svg class="h-3.5 w-3.5 {{ $game->is_home ? 'text-emerald-300' : 'text-emerald-600' }}" fill="none" stroke="currentColor" stroke-width="4" viewBox="0 0 24 24" aria-label="{{ __('Wygrana') }}"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7"/></svg>
                                                                    @elseif($won === false)
                                                                        <svg class="h-3.5 w-3.5 {{ $game->is_home ? 'text-red-300' : 'text-red-600' }}" fill="none" stroke="currentColor" stroke-width="4" viewBox="0 0 24 24" aria-label="{{ __('Porażka') }}"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
                                                                    @endif
                                                                    @if($game->isFinished())
                                                                        {{ $result ? $result[0].':'.$result[1] : '' }}
                                                                    @else
                                                                        {{ $hasTime ? $game->game_date->format('H:i') : '' }}
                                                                    @endif
                                                                </span>
                                                            </span>
                                                            <span @class(['flex items-center gap-1 truncate', 'text-sky-club-light' => $game->is_home, 'text-zinc-600' => ! $game->is_home])>
                                                                <span class="truncate">@if($game->isFriendly())<span class="mr-0.5 rounded bg-amber-300 px-1 text-[9px] font-bold text-amber-950 uppercase">{{ __('sparing') }}</span>@elseif($game->tournament_id)<span class="mr-0.5">🏆</span>@endif{{ $game->is_home ? __('vs') : __('u') }} {{ $game->opponentShortName() }}</span>
                                                                @if($article)
                                                                    <svg class="ml-auto h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-label="{{ __('Artykuł') }}"><path stroke-linecap="round" stroke-linejoin="round" d="M7 4h10a1 1 0 0 1 1 1v15l-3-2-3 2-3-2-3 2V5a1 1 0 0 1 1-1Zm2 5h6m-6 4h4"/></svg>
                                                                @endif
                                                            </span>
                                                        </{{ $tag }}>
                                                    </li>
                                                @endforeach
                                                @foreach($day['birthdays'] as $person)
                                                    <li class="truncate rounded-lg bg-(--cal-bday-bg) px-1.5 py-1 text-[11px] leading-tight text-(--cal-bday-text)" title="{{ __('Urodziny') }}: {{ $person['name'] }}{{ $person['showAge'] ? ' '.__('kończy').' '.$person['ageLabel'] : '' }} ({{ __($person['role']) }})">
                                                        {{ $birthdayIcon }} @if($person['url'])<a href="{{ $person['url'] }}" class="font-semibold hover:underline">{{ $person['name'] }}</a>@else<span class="font-semibold">{{ $person['name'] }}</span>@endif{{ $person['showAge'] ? ' · '.$person['age'] : '' }}
                                                    </li>
                                                @endforeach
                                            </ul>
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
                                <p class="mt-1 text-xs text-zinc-500 tabular-nums">{{ collect($game->sets_score)->map(fn ($set) => implode(':', (array) $set))->implode(' · ') }}@if($game->result_note) · <span class="font-semibold text-amber-700">{{ $game->result_note }}</span>@endif</p>
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
                    <li id="mini-{{ $trip->id }}" wire:key="list-mini-{{ $trip->id }}" class="card scroll-mt-24 lg:scroll-mt-48 bg-emerald-50 p-4 text-sm text-emerald-950 ring-emerald-200">
                        <p class="text-xs font-semibold tracking-wide text-emerald-700 uppercase">{{ $trip->dateLabel() }}{{ $trip->city ? ' · '.$trip->city : '' }}</p>
                        <p class="mt-1 font-display text-lg font-semibold uppercase">🏐 {{ $trip->title }}</p>
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
                                                <span class="block text-xs font-semibold tracking-wide text-emerald-700 uppercase">{{ $miniTeam->group?->name }}{{ $miniTeam->teams_count ? ' | '.$miniTeam->fieldLabel() : '' }}</span>
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
    </section>
</div>

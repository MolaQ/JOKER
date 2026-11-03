<div style="--tm-bar-bg: {{ $site->color('tm_bar_bg') }}; --tm-bar-text: {{ $site->color('tm_bar_text') }}; --tm-number-bg: {{ $site->color('tm_number_bg') }}; --tm-number-text: {{ $site->color('tm_number_text') }};">
    {{-- Kategorie wiekowe i zakładki --}}
    <div class="sticky top-16 z-30 border-b border-zinc-200 bg-white/90 backdrop-blur-xl lg:top-20">
        <div class="container-site space-y-3 py-3">
            @php
                $followable = $current && $current->participants->isNotEmpty() && $site['push_enabled'] && ($site['push_tournaments'] || $site['push_upcoming']);
                $teamFilter = $currentTab === 'matches' && $current && $current->participants->count() > 1;
                $sortedParticipants = $current?->participants->sortBy([fn ($a, $b) => (int) $b->isOwnTeam() <=> (int) $a->isOwnTeam(), fn ($a, $b) => strcoll($a->name(), $b->name())])->values() ?? collect();
            @endphp
            @if($categories->count() > 1 || $followable || $teamFilter)
                <div class="flex items-center gap-2 sm:flex-wrap">
                    @if($categories->count() > 1)
                        <div data-scroll-fade x-data x-init="const active = $el.querySelector('[aria-current]'); if (active && active.offsetLeft + active.offsetWidth > $el.clientWidth) $el.scrollLeft = active.offsetLeft - ($el.clientWidth - active.offsetWidth) / 2" class="scrollbar-none -ml-4 flex min-w-0 flex-1 gap-2 overflow-x-auto pl-4 sm:ml-0 sm:flex-none sm:flex-wrap sm:overflow-visible sm:pl-0" aria-label="{{ __('Kategoria wiekowa') }}">
                            @foreach($categories as $categoryOption)
                                <a href="{{ route('tournaments.show', ['tournament' => $tournament, 'category' => $loop->first ? null : $categoryOption->slug(), 'tab' => $tabs->has($currentTab) && $currentTab !== $tabs->keys()->first() ? $currentTab : null]) }}" data-track="{{ __('Kategoria') }}: {{ $categoryOption->name }}" @if($current?->id === $categoryOption->id) aria-current="page" @endif @class(['chip shrink-0 whitespace-nowrap transition', 'bg-club-navy text-white' => $current?->id === $categoryOption->id, 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200' => $current?->id !== $categoryOption->id])>{{ $categoryOption->name }}</a>
                            @endforeach
                        </div>
                    @endif
                    <div class="ml-auto flex shrink-0 items-center gap-2">
                        {{-- Filtr drużyny terminarza: stan trzyma komponent terminarza (zdarzenia okna) --}}
                        @if($teamFilter)
                            <label wire:key="team-filter-{{ $current->id }}" class="relative" x-data="{ team: '' }" x-on:tournament-team.window="team = $event.detail ?? ''">
                                <span class="sr-only">{{ __('Pokaż mecze drużyny') }}</span>
                                <select x-on:change="$dispatch('tournament-team-pick', $event.target.value || null)" x-effect="$el.value = team"
                                    class="h-8 max-w-32 appearance-none truncate rounded-full border-0 bg-zinc-100 pr-8 pl-3 text-xs font-semibold text-zinc-700 ring-1 ring-transparent transition hover:ring-sky-club focus:ring-2 focus:ring-sky-club sm:max-w-56"
                                    :class="team !== '' && 'bg-sky-club/10 text-sky-club-dark ring-sky-club/40!'">
                                    <option value="">{{ __('Wszystkie drużyny') }}</option>
                                    @foreach($sortedParticipants as $participant)
                                        <option value="{{ $participant->id }}">{{ $participant->name() }}</option>
                                    @endforeach
                                </select>
                                <svg class="pointer-events-none absolute top-1/2 right-2.5 size-3.5 -translate-y-1/2 text-zinc-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
                            </label>
                        @endif
                        {{-- Kibic wybiera drużynę i dostaje powiadomienia o jej wynikach i meczach za chwilę (obsługa w resources/js/push.js) --}}
                        @if($followable)
                            <div wire:ignore wire:key="push-follow-{{ $current->id }}" data-push-follow hidden data-unfollow-label="{{ __('Przestań obserwować') }}" class="relative" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
                                <button type="button" x-on:click="open = ! open" :aria-expanded="open" aria-expanded="false" title="{{ __('Powiadamiaj mnie o meczach drużyny') }}"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-full bg-zinc-100 px-3 text-xs font-semibold text-zinc-700 ring-1 ring-transparent transition hover:ring-sky-club">
                                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 2a6 6 0 0 0-6 6c0 1.887-.454 3.665-1.257 5.234a.75.75 0 0 0 .515 1.076 32.91 32.91 0 0 0 3.256.508 3.5 3.5 0 0 0 6.972 0 32.903 32.903 0 0 0 3.256-.508.75.75 0 0 0 .515-1.076A11.448 11.448 0 0 1 16 8a6 6 0 0 0-6-6Zm0 14.5a2 2 0 0 1-1.95-1.557 33.54 33.54 0 0 0 3.9 0A2 2 0 0 1 10 16.5Z"/></svg>
                                    <span class="hidden sm:inline">{{ __('Powiadomienia') }}</span>
                                    <span data-push-follow-count class="rounded-full bg-sky-club px-1.5 text-[10px] leading-4 text-white empty:hidden"></span>
                                </button>
                                <div x-cloak x-show="open" x-transition.origin.top.right class="absolute top-full right-0 z-40 mt-2 w-72 space-y-3 rounded-2xl bg-white p-4 text-sm shadow-xl ring-1 ring-zinc-200">
                                    <p class="font-semibold text-zinc-900">{{ __('Powiadamiaj mnie o meczach drużyny') }}</p>
                                    <div class="flex gap-2">
                                        <select class="h-8 min-w-0 flex-1 rounded-full border-zinc-200 bg-white py-0 text-xs" aria-label="{{ __('Drużyna') }}">
                                            <option value="">{{ __('wybierz drużynę') }}</option>
                                            @foreach($sortedParticipants as $participant)
                                                <option value="{{ $participant->id }}">{{ $participant->name() }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" data-push-follow-add class="h-8 rounded-full bg-sky-club px-3 text-xs font-semibold text-white hover:bg-sky-club-dark">{{ __('Obserwuj') }}</button>
                                    </div>
                                    <div data-push-followed class="flex flex-wrap gap-1.5 empty:hidden"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
            <div data-scroll-fade class="scrollbar-none -mx-4 flex gap-1 overflow-x-auto px-4 sm:mx-0 sm:px-0" role="tablist">
                @foreach($tabs as $key => $label)
                    <button type="button" role="tab" aria-selected="{{ $currentTab === $key ? 'true' : 'false' }}" wire:click="selectTab('{{ $key }}')" @class([
                        'shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition',
                        'bg-sky-club text-white shadow' => $currentTab === $key,
                        'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' => $currentTab !== $key,
                    ])>{{ $label }}</button>
                @endforeach
            </div>
        </div>
    </div>

    <section class="container-site py-10 transition-opacity" wire:loading.class="opacity-50">
        @if($tabs->isEmpty())
            <div class="card p-12 text-center text-zinc-500">{{ __('Szczegóły turnieju pojawią się wkrótce.') }}</div>
        @endif

        {{-- DRUŻYNY --}}
        @if($currentTab === 'teams' && $current)
            <h2 class="heading-md">{{ __('Drużyny') }} <span class="text-zinc-400">· {{ $current->name }}</span></h2>
            @php
                $cardDark = $site->isDark('tm_card_from', 'tm_card_to');
                $cardStyle = 'background-image: linear-gradient(150deg, '.$site->color('tm_card_from').', '.$site->color('tm_card_to').')';
            @endphp
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($current->participants->sortBy(fn ($participant) => ($participant->group?->name ?? 'zz').$participant->name()) as $participant)
                    <div wire:key="participant-{{ $participant->id }}" style="{{ $cardStyle }}" @class(['card border-0 p-5 shadow-lg', 'ring-2 ring-sky-club' => $participant->isOwnTeam(), 'ring-1 ring-white/10' => $cardDark && ! $participant->isOwnTeam()])>
                        <div class="flex items-center gap-4">
                            <x-avatar logo :photo="$participant->logoPath()" :name="$participant->name()" @class(['h-16 w-16 shrink-0 rounded-2xl text-xl', 'bg-white/5 ring-1 ring-white/15' => $cardDark]) />
                            <div class="min-w-0">
                                <p @class(['font-display text-lg leading-tight font-semibold uppercase', 'text-white' => $cardDark, 'text-zinc-900' => ! $cardDark])>{{ $participant->name() }}</p>
                                @if($participant->group)<p @class(['mt-1 text-sm', 'text-white/60' => $cardDark, 'text-zinc-500' => ! $cardDark])>{{ $participant->group->name }}</p>@endif
                            </div>
                        </div>
                        @if($participant->rosterLines())
                            <details class="group mt-4">
                                <summary @class(['cursor-pointer list-none text-sm font-semibold', 'text-sky-club-light' => $cardDark, 'text-sky-club-dark' => ! $cardDark]) data-track="{{ __('Skład') }}: {{ $participant->name() }}">{{ __('Skład') }} ({{ count($participant->rosterLines()) }}) <span class="inline-block transition group-open:rotate-180">▾</span></summary>
                                <ul @class(['mt-2 columns-2 gap-4 text-sm', 'text-white/80' => $cardDark, 'text-zinc-700' => ! $cardDark])>
                                    @foreach($participant->rosterLines() as $line)
                                        <li>{{ $line }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- GRUPY --}}
        @if($currentTab === 'groups' && $current)
            <div @class(['grid gap-8', 'lg:grid-cols-2' => $current->groups->count() > 1])>
                @foreach($current->groups as $group)
                    <div wire:key="group-{{ $group->id }}" class="card overflow-hidden">
                        <h2 class="flex items-center justify-between gap-2 bg-gradient-to-r from-club-navy to-sky-club-dark px-5 py-3 font-display text-xl font-semibold text-white uppercase">{{ $group->name }} <x-tournament-group-status :progress="$bracket->groupProgress($group)" dark /></h2>
                        <div class="overflow-x-auto">
                            <table data-sortable class="w-full text-sm">
                                <thead class="bg-zinc-50 text-xs font-semibold text-zinc-500 uppercase">
                                    <tr>
                                        <th class="px-3 sm:px-4 py-2 text-left">#</th>
                                        <th class="px-1.5 sm:px-2 py-2 text-left">{{ __('Drużyna') }}</th>
                                        <th class="px-1.5 sm:px-2 py-2 text-center" title="{{ __('Mecze') }}">M</th>
                                        <th class="hidden px-1.5 sm:px-2 py-2 text-center sm:table-cell" title="{{ __('Wygrane') }}">W</th>
                                        <th class="hidden px-1.5 sm:px-2 py-2 text-center sm:table-cell" title="{{ __('Porażki') }}">P</th>
                                        <th class="px-1.5 sm:px-2 py-2 text-center">{{ __('Sety') }}</th>
                                        <th class="hidden px-1.5 sm:px-2 py-2 text-center sm:table-cell">{{ __('Małe pkt') }}</th>
                                        <th class="px-3 sm:px-4 py-2 text-center">{{ __('Pkt') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100">
                                    @foreach($bracket->groupTable($group) as $row)
                                        <tr @class(['bg-sky-club/10 font-semibold' => $row['participant']->isOwnTeam()])>
                                            <td class="px-3 sm:px-4 py-2.5 font-bold">{{ $row['position'] }}</td>
                                            <td class="w-full max-w-0 px-1.5 sm:px-2 py-2.5">
                                                <span class="flex items-center gap-2">
                                                    <x-avatar logo :photo="$row['participant']->logoPath()" :name="$row['participant']->name()" class="h-6 w-6 shrink-0 rounded-full text-[9px]" />
                                                    <span class="line-clamp-2 leading-tight">{{ $row['participant']->name() }}</span>
                                                </span>
                                            </td>
                                            <td class="px-1.5 sm:px-2 py-2.5 text-center tabular-nums">{{ $row['played'] }}</td>
                                            <td class="hidden px-1.5 sm:px-2 py-2.5 text-center text-emerald-700 tabular-nums sm:table-cell">{{ $row['won'] }}</td>
                                            <td class="hidden px-1.5 sm:px-2 py-2.5 text-center text-red-600 tabular-nums sm:table-cell">{{ $row['lost'] }}</td>
                                            <td class="px-1.5 sm:px-2 py-2.5 text-center tabular-nums">{{ $row['sets_won'] }}:{{ $row['sets_lost'] }}</td>
                                            <td class="hidden px-1.5 sm:px-2 py-2.5 text-center text-zinc-500 tabular-nums sm:table-cell">{{ $row['small_won'] }}:{{ $row['small_lost'] }}</td>
                                            <td class="px-3 sm:px-4 py-2.5 text-center font-display text-lg font-bold tabular-nums">{{ $row['points'] }}</td>
                                        </tr>
                                    @endforeach
                                    @foreach($bracket->members($group)->whereNull('participant')->reject(fn ($member) => str_starts_with($member['key'], 'participant:')) as $member)
                                        <tr class="text-zinc-400 italic">
                                            <td class="px-3 sm:px-4 py-2.5 font-bold not-italic">–</td>
                                            <td class="px-1.5 sm:px-2 py-2.5" colspan="7">{{ $member['text'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($group->matches->isNotEmpty())
                            <details class="border-t border-zinc-100 px-5 py-3">
                                <summary class="cursor-pointer text-sm font-semibold text-sky-club-dark" data-track="{{ __('Mecze grupy') }}: {{ $group->name }}">{{ __('Mecze grupy') }} ({{ $group->matches->count() }})</summary>
                                {{-- Mecze dniami turnieju, w dniu według godziny --}}
                                @foreach($group->matches->sortBy(fn ($match) => [$match->starts_at?->timestamp ?? PHP_INT_MAX, $match->number ?? PHP_INT_MAX])->groupBy(fn ($match) => $match->starts_at?->toDateString() ?? '') as $day => $dayMatches)
                                    <h3 class="mt-4 mb-2 flex items-center gap-2 text-xs font-bold tracking-wide text-zinc-500 uppercase">
                                        <span>{{ $day ? \Illuminate\Support\Str::ucfirst(\Illuminate\Support\Carbon::parse($day)->translatedFormat('l, j F')) : __('Termin do ustalenia') }}</span>
                                        <span class="h-px flex-1 bg-zinc-200"></span>
                                    </h3>
                                    <div @class(['grid gap-2 sm:grid-cols-2', 'lg:grid-cols-3' => $current->groups->count() === 1])>
                                        @foreach($dayMatches as $match)
                                            <x-tournament-match :match="$match" :bracket="$bracket" :show-title="false" />
                                        @endforeach
                                    </div>
                                @endforeach
                            </details>
                        @endif
                    </div>
                @endforeach
            </div>
            <x-tournament-scoring-note :groups="$current->groups" :tournament="$tournament" class="mt-6" />
        @endif

        {{-- MECZE: terminarz dniami i godzinami, z filtrem drużyny --}}
        @if($currentTab === 'matches' && $current)
            {!! $schedule() !!}
            {{-- Legenda: hale, adresy i ikony boisk, żeby było wiadomo, gdzie gra się mecz --}}
            <x-tournament-halls :tournament="$tournament" :matches="$current->matches" class="mt-4" />
            <x-tournament-scoring-note :groups="$current->groups" :tournament="$tournament" class="mt-10" />
        @endif

        {{-- DRABINKA: grupy z postępem, mecze z numerami w kolumnach (na telefonie jedna pod drugą), podium --}}
        @if($currentTab === 'bracket' && $current)
            <p class="max-w-3xl text-sm text-zinc-500">{{ __('Każdy mecz ma numer. Plakietka przy drużynie mówi, skąd przychodzi (1A = 1. miejsce grupy A, #7 = mecz nr 7), a napis w rogu meczu, dokąd idą zwycięzca i przegrany. Szare nazwy to prognoza, dopóki grupa trwa.') }}</p>

            @if($current->groups->isNotEmpty())
                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($current->groups as $group)
                        @php $progress = $bracket->groupProgress($group); @endphp
                        <div class="rounded-2xl bg-white p-4 ring-1 ring-zinc-200" wire:key="board-group-{{ $group->id }}">
                            <p class="flex items-center justify-between gap-2 font-semibold text-zinc-900">
                                {{ $group->name }}
                                <x-tournament-group-status :progress="$progress" />
                            </p>
                            <ol class="mt-2 space-y-0.5 text-sm">
                                @foreach($bracket->groupTable($group) as $row)
                                    <li @class(['flex gap-2', 'italic text-zinc-400' => ! $progress['finished'], 'text-zinc-800' => $progress['finished']])>
                                        <span class="w-6 font-bold not-italic text-zinc-400">{{ $row['position'] }}{{ \App\Support\TournamentBracket::groupLetter($group) }}</span>
                                        <span class="truncate">{{ $row['participant']->name() }}</span>
                                    </li>
                                @endforeach
                                @foreach($bracket->members($group)->whereNull('participant') as $member)
                                    <li class="flex gap-2 text-zinc-400 italic"><span class="w-6 font-bold not-italic">–</span><span class="truncate">{{ $member['text'] }}</span></li>
                                @endforeach
                            </ol>
                        </div>
                    @endforeach
                </div>
            @endif

            <div x-data="{ list: false }" class="mt-8">
                {{-- Przełącznik: drabinka albo wszystkie mecze po kolei (wygodne na telefonie w hali) --}}
                <div class="inline-flex rounded-full bg-white p-1 shadow-sm ring-1 ring-zinc-200" role="group" aria-label="{{ __('Widok') }}">
                    <button type="button" @click="list = false" :aria-pressed="! list" :class="! list ? 'bg-club-navy text-white shadow' : 'text-zinc-600 hover:text-zinc-900'" class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold transition">
                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5h5v4H3zM3 15h5v4H3zM8 7h3v10H8M11 12h4M15 10h6v4h-6z"/></svg>
                        {{ __('Drabinka') }}
                    </button>
                    <button type="button" @click="list = true" :aria-pressed="list" :class="list ? 'bg-club-navy text-white shadow' : 'text-zinc-600 hover:text-zinc-900'" class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold transition">
                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/></svg>
                        {{ __('Po kolei') }}
                    </button>
                </div>

                <div x-show="! list" class="mt-6">
                    @if($standalone->isNotEmpty())
                        <section class="mb-8">
                            <h2 class="mb-3 text-xs font-semibold tracking-widest text-zinc-500 uppercase">{{ __('Mecze o dalsze miejsca') }}</h2>
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach($standalone as $match)
                                    <x-tournament-bracket-match :match="$match" :bracket="$bracket" wire:key="standalone-{{ $match->id }}" />
                                @endforeach
                            </div>
                        </section>
                    @endif
                    <div class="grid gap-8 lg:auto-cols-[minmax(19rem,1fr)] lg:grid-flow-col lg:overflow-x-auto lg:pb-2">
                        @foreach($board as $column)
                            <section wire:key="board-column-{{ $loop->index }}">
                                <h2 class="mb-3 text-xs font-semibold tracking-widest text-zinc-500 uppercase">{{ $column['name'] }}</h2>
                                <div class="space-y-3">
                                    @foreach($column['matches'] as $match)
                                        <x-tournament-bracket-match :match="$match" :bracket="$bracket" wire:key="board-{{ $match->id }}" />
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                </div>

                <ol x-show="list" x-cloak class="mt-6 divide-y divide-zinc-100 overflow-hidden rounded-2xl bg-white ring-1 ring-zinc-200">
                    @foreach($knockout as $match)
                        <li class="grid grid-cols-[4rem_minmax(0,1fr)] items-center gap-x-3 gap-y-1 px-4 py-3 sm:grid-cols-[4.5rem_3.5rem_minmax(0,1fr)_9rem]" wire:key="list-{{ $match->id }}">
                            <span class="font-display text-xl font-semibold tabular-nums text-zinc-900">{{ $match->starts_at?->format('H:i') ?? '–' }}</span>
                            <span class="hidden sm:block"><span class="rounded-md bg-(--tm-number-bg) px-1.5 text-sm font-bold text-(--tm-number-text) tabular-nums">#{{ $match->number }}</span></span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-zinc-900"><span class="sm:hidden">#{{ $match->number }} · </span>{{ $match->title() }} <x-match-delay :match="$match" /></span>
                                <span class="block truncate text-sm text-zinc-500">{{ $bracket->side($match, 'home')['text'] }} – {{ $bracket->side($match, 'away')['text'] }}@if($match->home_sets !== null) · <b class="text-zinc-800 tabular-nums">{{ $match->home_sets }}:{{ $match->away_sets }}</b>@endif</span>
                            </span>
                            <span class="col-start-2 inline-flex items-center gap-2 text-sm text-zinc-600 sm:col-start-auto">
                                <x-court-icon :court="$match->court" :category="$match->tournament_category_id" />{{ \App\Support\TournamentHalls::label($match->court, $match->tournament_category_id) }}
                            </span>
                        </li>
                    @endforeach
                </ol>

                <x-tournament-halls :tournament="$tournament" :matches="$knockout" class="mt-4" />
            </div>

            @if($standings->isNotEmpty())
                <h2 class="heading-md mt-12">{{ __('Klasyfikacja końcowa') }}</h2>
                <x-tournament-podium :standings="$standings" class="mt-6" />
            @endif
        @endif

        {{-- KLASYFIKACJA KOŃCOWA --}}
        @if($currentTab === 'ranking' && $current)
            {{-- Po ostatnim meczu turnieju: zwycięzcy wszystkich kategorii i miejsca naszych drużyn w jednym miejscu --}}
            @if($summary)
                <div class="mb-12 overflow-hidden rounded-3xl bg-ink text-white ring-1 ring-white/10" data-theme-keep>
                    <div class="px-5 pt-6 pb-4 sm:px-8 sm:pt-8">
                        <p class="text-xs font-semibold tracking-widest text-sky-club uppercase">{{ __('Turniej zakończony') }} · {{ $tournament->dateRangeLabel() }}</p>
                        <h2 class="mt-2 font-display text-3xl leading-none font-bold uppercase sm:text-4xl">{{ __('Podsumowanie') }}</h2>
                    </div>
                    <div @class(['grid gap-px bg-white/10 sm:grid-cols-2', 'lg:grid-cols-3' => $summary->count() === 3, 'lg:grid-cols-4' => $summary->count() >= 4])>
                        @foreach($summary as $entry)
                            <a href="{{ route('tournaments.show', ['tournament' => $tournament, 'category' => $loop->first ? null : $entry['category']->slug(), 'tab' => 'ranking']) }}" wire:key="summary-{{ $entry['category']->id }}" class="block bg-ink px-5 py-5 transition hover:bg-white/5 sm:px-6">
                                <p class="text-sm font-bold tracking-wide text-sky-club-light uppercase">{{ $entry['category']->name }}</p>
                                <ol class="mt-3 space-y-1.5 text-sm">
                                    @foreach($entry['podium'] as $row)
                                        <li class="flex items-center gap-2">
                                            <span @class(['flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-bold', 'bg-amber-400 text-amber-950' => $row['position'] === 1, 'bg-zinc-300 text-zinc-900' => $row['position'] === 2, 'bg-orange-400 text-orange-950' => $row['position'] === 3])>{{ $row['position'] }}</span>
                                            <span @class(['min-w-0 truncate', 'font-semibold' => $row['position'] === 1, 'text-sky-club-light' => $row['participant']->isOwnTeam()])>{{ $row['text'] }}</span>
                                        </li>
                                    @endforeach
                                </ol>
                                @foreach($entry['ours']->where('position', '>', 3) as $row)
                                    <p class="mt-2 text-xs text-zinc-400"><span class="font-semibold text-sky-club-light">{{ $row['position'] }}. {{ __('miejsce') }}</span> · {{ $row['text'] }}</p>
                                @endforeach
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
            @if($standings->isNotEmpty())
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="heading-md">{{ __('Klasyfikacja końcowa') }} <span class="text-zinc-400">· {{ $current->name }}</span></h2>
                    <x-share-card :card="\App\Support\ShareCards::standings($current, $standings)" />
                </div>
                <x-tournament-podium :standings="$standings" class="mt-8" />
            @endif
            @if($current->awards->isNotEmpty())
                <div @class(['flex flex-wrap items-center justify-between gap-3', 'mt-14' => $standings->isNotEmpty()])>
                    <h2 class="heading-md">{{ __('Wyróżnienia') }}</h2>
                    <x-share-card :card="\App\Support\ShareCards::awards($current)" />
                </div>
                <ul class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($current->awards as $award)
                        <li class="flex items-center gap-4 rounded-2xl bg-white p-4 ring-1 ring-zinc-200">
                            <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600" aria-hidden="true"><svg class="size-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8m-4-4v4M7 4h10v5a5 5 0 0 1-10 0V4Zm0 2H4v1a3 3 0 0 0 3 3m10-4h3v1a3 3 0 0 1-3 3"/></svg></span>
                            <span class="min-w-0">
                                <span class="block text-xs font-semibold tracking-wide text-sky-club-dark uppercase">{{ $award->name }}</span>
                                @if($award->player)
                                    <a href="{{ route('players.show', $award->player) }}" class="block font-display text-lg font-semibold uppercase hover:underline">{{ $award->personLabel() }}</a>
                                @else
                                    <span class="block font-display text-lg font-semibold uppercase">{{ $award->personLabel() }}</span>
                                @endif
                                @if($award->teamLabel())<span class="block text-sm text-zinc-500">{{ $award->teamLabel() }}</span>@endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif

        {{-- ZASADY I INFORMATOR --}}
        @foreach(['about' => $tournament->localized('about'), 'rules' => $tournament->localized('rules'), 'info' => $tournament->localized('guest_info')] as $key => $content)
            @if($currentTab === $key)
                <div class="card mx-auto max-w-3xl p-8">
                    <h2 class="heading-md">{{ $tabs[$key] }}</h2>
                    <div class="prose-club mt-5">{!! \App\Support\DeadLinks::strip(strip_tags($content, '<p><br><strong><em><b><i><u><s><mark><blockquote><hr><ul><ol><li><h2><h3><h4><a><table><thead><tbody><tr><th><td>')) !!}</div>
                </div>
            @endif
        @endforeach
    </section>
</div>

<div>
    {{-- Filtry: przyklejone pod menu, działają od razu, bez przeładowania strony --}}
    <div class="sticky top-16 z-30 border-y border-zinc-200 bg-white/90 backdrop-blur-xl lg:top-20">
        <div class="container-site space-y-3 py-3">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <label class="relative flex-1">
                    <span class="sr-only">Szukaj meczu</span>
                    <svg class="pointer-events-none absolute top-1/2 left-4 h-5 w-5 -translate-y-1/2 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                    <input type="search" wire:model.live.debounce.150ms="search" placeholder="Szukaj: rywal, drużyna, hala, data (np. 28.10)…" autocomplete="off" enterkeyhint="search"
                        class="h-12 w-full rounded-full border-0 bg-zinc-100 pr-4 pl-12 text-base text-zinc-900 ring-1 ring-transparent transition outline-none placeholder:text-zinc-400 focus:bg-white focus:ring-sky-club">
                </label>

                <div class="grid grid-cols-3 rounded-full bg-zinc-100 p-1 text-sm font-semibold" role="tablist" aria-label="Które mecze pokazać">
                    @foreach(\App\Livewire\GamesBrowser::PERIODS as $key => $label)
                        <button type="button" role="tab" aria-selected="{{ $period === $key ? 'true' : 'false' }}" wire:click="setPeriod('{{ $key }}')" @class([
                            'flex items-center justify-center gap-1.5 rounded-full px-4 py-2 transition',
                            'bg-ink text-white shadow' => $period === $key,
                            'text-zinc-600 hover:text-zinc-900' => $period !== $key,
                        ])>
                            {{ $label }} <span class="text-xs tabular-nums opacity-60">{{ $periodCounts[$key] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="scrollbar-none -mx-4 flex min-w-0 flex-1 basis-full gap-2 overflow-x-auto px-4 sm:mx-0 sm:basis-auto sm:px-0">
                    <button type="button" wire:click="$set('team', '')" @class(['chip shrink-0 transition', 'bg-sky-club text-white' => $team === '', 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200' => $team !== ''])>Wszystkie drużyny</button>
                    @foreach($teams as $teamOption)
                        <button type="button" wire:click="toggleTeam('{{ $teamOption->slug }}')" @class(['chip shrink-0 transition', 'bg-sky-club text-white' => $team === $teamOption->slug, 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200' => $team !== $teamOption->slug])>{{ $teamOption->name }}</button>
                    @endforeach
                </div>

                <div class="flex w-full flex-wrap gap-2 sm:w-auto">
                    @if($competitions->count() > 1)
                        <select wire:model.live="competition" class="h-9 min-w-0 flex-1 rounded-full border-0 bg-zinc-100 px-4 text-sm font-medium text-zinc-700 sm:flex-none" aria-label="Rozgrywki">
                            <option value="">Wszystkie rozgrywki</option>
                            @foreach($competitions as $competitionOption)
                                <option value="{{ $competitionOption->id }}">{{ $competitionOption->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <select wire:model.live="place" class="h-9 flex-1 rounded-full border-0 bg-zinc-100 px-4 text-sm font-medium text-zinc-700 sm:flex-none" aria-label="U siebie czy na wyjeździe">
                        <option value="">Dom i wyjazd</option>
                        <option value="dom">U siebie</option>
                        <option value="wyjazd">Na wyjeździe</option>
                    </select>
                    @if($seasons->count() > 1)
                        <select wire:model.live="season" class="h-9 flex-1 rounded-full border-0 bg-zinc-100 px-4 text-sm font-medium text-zinc-700 sm:flex-none" aria-label="Sezon">
                            @foreach($seasons as $seasonOption)
                                <option value="{{ $seasonOption->is_current ? '' : $seasonOption->id }}">Sezon {{ $seasonOption->name }}</option>
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
                {{ $total }} {{ $total === 1 ? 'mecz' : ($total % 10 >= 2 && $total % 10 <= 4 && ($total % 100 < 10 || $total % 100 >= 20) ? 'mecze' : 'meczów') }}
                @if($search !== '') dla „<span class="font-semibold text-zinc-800">{{ $search }}</span>”@endif
            </p>
            @if($hasFilters)
                <button type="button" wire:click="resetFilters" class="text-sm font-semibold text-sky-club-dark hover:underline">Wyczyść filtry ✕</button>
            @endif
        </div>

        @forelse($gamesByMonth as $month => $monthGames)
            <h2 class="mt-10 mb-4 font-display text-xl font-semibold tracking-wide text-zinc-400 uppercase first:mt-0">{{ $month }}</h2>
            <ul class="space-y-3">
                @foreach($monthGames as $game)
                    @php
                        $result = $game->ourSetsResult();
                        $won = $game->isFinished() && $result ? $result[0] > $result[1] : null;
                        $ourName = $game->team?->name ?? $site['club_name'];
                        $opponentName = $game->opponentShortName();
                        $home = $game->is_home ? $ourName : $opponentName;
                        $away = $game->is_home ? $opponentName : $ourName;
                        $hasTime = $game->game_date && $game->game_date->format('H:i') !== '00:00';
                    @endphp
                    <li wire:key="game-{{ $game->id }}" class="card grid grid-cols-[3.75rem_1fr] items-center gap-4 p-4 sm:grid-cols-[4.5rem_1fr_auto] sm:gap-6 sm:p-5">
                        <div class="rounded-2xl bg-zinc-100 py-2 text-center leading-none">
                            <p class="text-[11px] font-semibold text-zinc-500 uppercase">{{ $game->game_date?->translatedFormat('D') }}</p>
                            <p class="mt-1 font-display text-2xl font-bold text-zinc-900 tabular-nums">{{ $game->game_date?->format('d') }}</p>
                            <p class="mt-1 text-[11px] font-semibold text-sky-club-dark tabular-nums">{{ $hasTime ? $game->game_date->format('H:i') : '--:--' }}</p>
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold tracking-wide text-zinc-500 uppercase">
                                {{ $game->competition?->name ?? $game->league }}@if($game->round_label) · {{ $game->round_label }}@endif
                            </p>
                            <div class="mt-1.5 flex flex-wrap items-baseline gap-x-3 gap-y-1 font-display text-lg leading-tight font-semibold uppercase sm:text-xl">
                                <span @class(['text-zinc-900' => $game->is_home, 'text-zinc-500' => ! $game->is_home])>{{ $home }}</span>
                                @if($game->isFinished())
                                    <span class="rounded-lg bg-ink px-2 py-0.5 text-base text-white tabular-nums">{{ $game->home_score }}:{{ $game->away_score }}</span>
                                @else
                                    <span class="text-sm text-zinc-400 normal-case">vs</span>
                                @endif
                                <span @class(['text-zinc-900' => ! $game->is_home, 'text-zinc-500' => $game->is_home])>{{ $away }}</span>
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
                            @if($won !== null)
                                <span @class(['rounded-full px-3 py-1 text-xs font-bold', 'bg-emerald-500/15 text-emerald-700' => $won, 'bg-red-500/10 text-red-600' => ! $won])>{{ $won ? 'Wygrana' : 'Porażka' }}</span>
                            @else
                                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600">{{ $game->is_home ? 'U siebie' : 'Wyjazd' }}</span>
                            @endif
                            @if($game->isUpcoming())
                                <a href="{{ route('games.calendar', $game) }}" class="inline-flex min-h-9 items-center gap-1.5 text-xs font-semibold text-sky-club-dark hover:underline">+ Do kalendarza</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @empty
            <div class="card p-12 text-center">
                <p class="font-display text-2xl font-semibold text-zinc-900 uppercase">Brak meczów</p>
                <p class="mt-2 text-zinc-500">{{ $hasFilters ? 'Żaden mecz nie pasuje do wybranych filtrów.' : 'Terminarz pojawi się tu, gdy tylko zostanie ogłoszony.' }}</p>
                @if($hasFilters)
                    <button type="button" wire:click="resetFilters" class="btn-primary mt-6">Pokaż wszystkie mecze</button>
                @elseif($period === 'nadchodzace' && $periodCounts['wyniki'] > 0)
                    <button type="button" wire:click="setPeriod('wyniki')" class="btn-primary mt-6">Zobacz wyniki</button>
                @endif
            </div>
        @endforelse
    </section>
</div>

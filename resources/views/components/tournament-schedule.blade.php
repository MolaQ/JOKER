@props(['matches', 'bracket'])
{{--
    Terminarz kategorii: dni → godziny (oś czasu) → mecze o tej godzinie (zwykle po jednym na boisko).
    Filtr drużyny działa w Alpine bez zapytań do serwera; wybór pamiętany w localStorage.
--}}
@php
    use App\Support\TournamentBracket;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $now = now();
    $categoryId = $matches->first()?->tournament_category_id;

    // Strony meczów liczone raz: nazwa, stan (pewna / prognoza / czekamy) i drużyny do filtra.
    $rows = $matches->map(function ($match) use ($bracket) {
        $sides = ['home' => $bracket->side($match, 'home'), 'away' => $bracket->side($match, 'away')];
        $teams = collect($sides)
            ->filter(fn ($side) => $side['participant'] && $side['state'] !== TournamentBracket::PENDING)
            ->map(fn ($side) => (string) $side['participant']->id)
            ->values()
            ->all();
        $live = $match->is_live;

        return compact('match', 'sides', 'teams', 'live');
    });

    // Drużyny do filtra: najpierw własne (Joker), potem reszta alfabetycznie.
    $participants = $rows->flatMap(fn ($row) => collect($row['sides'])->filter(fn ($side) => $side['participant'] && $side['state'] !== TournamentBracket::PENDING)->pluck('participant'))
        ->unique('id')
        ->sortBy([fn ($a, $b) => (int) $b->isOwnTeam() <=> (int) $a->isOwnTeam(), fn ($a, $b) => strcoll($a->name(), $b->name())])
        ->values();
    $ownTeams = $participants->filter(fn ($participant) => $participant->isOwnTeam())->values();

    // Podsumowanie wybranej drużyny: liczba meczów i najbliższy mecz bez wyniku.
    $teamInfo = $participants->mapWithKeys(function ($participant) use ($rows) {
        $id = (string) $participant->id;
        $own = $rows->filter(fn ($row) => in_array($id, $row['teams'], true));
        $next = $own->first(fn ($row) => ! $row['match']->isFinished() && $row['match']->starts_at);

        return [$id => [
            'name' => $participant->name(),
            'count' => trans_choice(':count mecz|:count mecze|:count meczów', $own->count()),
            'next' => $next ? __('Najbliższy: :when · #:number', [
                'when' => Str::ucfirst($next['match']->starts_at->translatedFormat('D')).' '.$next['match']->starts_at->format('H:i'),
                'number' => $next['match']->number,
            ]) : null,
        ]];
    });

    $days = $rows->groupBy(fn ($row) => $row['match']->starts_at?->toDateString() ?? '')
        ->sortKeysUsing(fn ($a, $b) => ($a === '') <=> ($b === '') ?: strcmp($a, $b));

    // Boiska kategorii w naturalnej kolejności: na szerokim ekranie każde ma swoją kolumnę.
    $courts = $matches->pluck('court')->filter()->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();
    $courtColumns = $courts->count() >= 2 && $courts->count() <= 3 ? $courts->flip()->map(fn ($index) => $index + 1) : collect();
@endphp

<div
    {{ $attributes->merge(['class' => 'space-y-10']) }}
    x-data="{
        team: null,
        info: @js($teamInfo),
        key: @js('turniej-terminarz-'.$categoryId),
        init() {
            try {
                const saved = localStorage.getItem(this.key);
                if (saved && this.info[saved]) this.team = saved;
            } catch (e) {}
        },
        pick(id) {
            this.team = id && this.info[id] ? String(id) : null;
            try {
                this.team ? localStorage.setItem(this.key, this.team) : localStorage.removeItem(this.key);
            } catch (e) {}
        },
        shows(ids) { return this.team === null || ids.includes(this.team); },
    }"
>
    {{-- FILTR DRUŻYNY --}}
    @if($participants->count() > 1)
        <div class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-zinc-200 sm:p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:gap-5">
                <p class="text-xs font-semibold tracking-widest text-zinc-500 uppercase lg:shrink-0">{{ __('Pokaż mecze drużyny') }}</p>
                <div class="flex min-w-0 flex-wrap items-center gap-2" role="group" aria-label="{{ __('Filtr drużyny') }}">
                    <button type="button" x-on:click="pick(null)" :aria-pressed="team === null" aria-pressed="true"
                        class="inline-flex h-9 items-center rounded-full px-4 text-sm font-semibold ring-1 transition"
                        :class="team === null ? 'bg-club-navy text-white ring-club-navy' : 'bg-white text-zinc-700 ring-zinc-200 hover:ring-sky-club'">
                        {{ __('Wszystkie drużyny') }}
                    </button>
                    @foreach($ownTeams as $participant)
                        <button type="button" x-on:click="pick(@js((string) $participant->id))" :aria-pressed="team === @js((string) $participant->id)" aria-pressed="false"
                            title="{{ $participant->name() }}"
                            class="inline-flex h-9 max-w-full items-center gap-2 rounded-full py-1 pr-4 pl-1 text-sm font-semibold ring-1 transition"
                            :class="team === @js((string) $participant->id) ? 'bg-sky-club text-white ring-sky-club' : 'bg-sky-club/10 text-sky-club-dark ring-sky-club/30 hover:ring-sky-club'">
                            <x-avatar logo :photo="$participant->logoPath()" :name="$participant->name()" class="h-7 w-7 shrink-0 rounded-full text-[10px]" />
                            <span class="truncate">{{ $ownTeams->count() === 1 ? $site['club_short_name'] : $participant->name() }}</span>
                        </button>
                    @endforeach
                    <label class="relative min-w-0 flex-1 basis-56 sm:max-w-xs">
                        <span class="sr-only">{{ __('Wybierz drużynę') }}</span>
                        <select x-on:change="pick($event.target.value || null)" :value="team ?? ''" x-effect="$el.value = team ?? ''"
                            class="h-9 w-full appearance-none truncate rounded-full border-0 bg-zinc-100 pr-9 pl-4 text-sm font-semibold text-zinc-700 ring-1 ring-transparent transition hover:ring-sky-club focus:ring-2 focus:ring-sky-club"
                            :class="team !== null && ! @js($ownTeams->pluck('id')->map(fn ($id) => (string) $id)->values()).includes(team) && 'bg-sky-club/10 text-sky-club-dark ring-sky-club/40!'">
                            <option value="">{{ __('Wybierz drużynę…') }}</option>
                            @foreach($participants as $participant)
                                <option value="{{ $participant->id }}">{{ $participant->name() }}</option>
                            @endforeach
                        </select>
                        <svg class="pointer-events-none absolute top-1/2 right-3.5 size-4 -translate-y-1/2 text-zinc-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
                    </label>
                </div>
            </div>
            <p x-cloak x-show="team !== null" class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-zinc-100 pt-3 text-sm" aria-live="polite">
                <span class="font-semibold text-zinc-900" x-text="team && info[team].name"></span>
                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-600 tabular-nums" x-text="team && info[team].count"></span>
                <span class="text-zinc-500 tabular-nums" x-show="team && info[team].next" x-text="team && info[team].next"></span>
            </p>
        </div>
    @endif

    @forelse($days as $day => $dayRows)
        @php
            $date = $day !== '' ? Carbon::parse($day) : null;
            $slots = $dayRows->groupBy(fn ($row) => $row['match']->starts_at?->format('H:i') ?? '');
            $dayTeams = $dayRows->flatMap(fn ($row) => $row['teams'])->unique()->values()->all();
            $isToday = $date?->isSameDay($now);
        @endphp
        <section x-show="shows(@js($dayTeams))" aria-labelledby="dzien-{{ $day ?: 'brak' }}">
            {{-- Nagłówek dnia: kartka z kalendarza, nazwa dnia, liczba meczów --}}
            <header class="flex items-center gap-4 border-b-2 border-club-navy pb-3">
                @if($date)
                    <div class="flex w-14 shrink-0 flex-col overflow-hidden rounded-xl text-center shadow-sm ring-1 ring-zinc-200 sm:w-16">
                        <span class="bg-club-navy py-0.5 text-[10px] font-bold tracking-widest text-white uppercase">{{ Str::upper(Str::substr($date->translatedFormat('M'), 0, 3)) }}</span>
                        <span class="bg-white py-1 font-display text-2xl leading-none font-bold text-zinc-900 tabular-nums sm:text-3xl">{{ $date->format('j') }}</span>
                    </div>
                @else
                    <div class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-zinc-100 font-display text-2xl font-bold text-zinc-400 sm:size-16">?</div>
                @endif
                <div class="min-w-0">
                    <h2 id="dzien-{{ $day ?: 'brak' }}" class="heading-md text-zinc-900">
                        {{ $date ? Str::ucfirst($date->translatedFormat('l')) : __('Termin do ustalenia') }}
                        @if($isToday)<span class="ml-1 inline-block -translate-y-0.5 rounded-full bg-sky-club px-2 py-0.5 align-middle font-sans text-[11px] font-bold tracking-wide text-white normal-case">{{ __('Dziś') }}</span>@endif
                    </h2>
                    <p class="mt-0.5 text-sm text-zinc-500">
                        @if($date){{ $date->translatedFormat('j F Y') }} · @endif{{ trans_choice(':count mecz|:count mecze|:count meczów', $dayRows->count()) }}
                    </p>
                </div>
            </header>

            {{-- Nagłówki boisk nad kolumnami (tylko gdy mecze stoją w kolumnach boisk) --}}
            @if($date && $courtColumns->isNotEmpty())
                <div @class([
                    'mt-5 hidden gap-4 sm:pl-[calc(7rem+26px)]',
                    'md:grid md:grid-cols-2' => $courtColumns->count() === 2,
                    'lg:grid lg:grid-cols-3' => $courtColumns->count() === 3,
                ]) aria-hidden="true">
                    @foreach($courtColumns as $court => $column)
                        <p class="flex items-center gap-2 text-xs font-semibold tracking-widest text-zinc-400 uppercase">
                            <span class="h-px flex-1 bg-zinc-200"></span>{{ $court }}<span class="h-px flex-1 bg-zinc-200"></span>
                        </p>
                    @endforeach
                </div>
            @endif

            <ol @class(['mt-5' => ! ($date && $courtColumns->isNotEmpty()), 'mt-5 md:mt-3' => $date && $courtColumns->isNotEmpty() && $courtColumns->count() === 2, 'mt-5 lg:mt-3' => $date && $courtColumns->count() === 3])>
                @foreach($slots as $time => $slotRows)
                    @php
                        $slotTeams = $slotRows->flatMap(fn ($row) => $row['teams'])->unique()->values()->all();
                        $slotLive = $slotRows->contains('live', true);
                        $slotDone = $slotRows->every(fn ($row) => $row['match']->isFinished());
                    @endphp
                    <li class="relative grid sm:grid-cols-[7rem_minmax(0,1fr)]" x-show="shows(@js($slotTeams))">
                        {{-- Godzina: na telefonie nad meczami, na komputerze z lewej --}}
                        <div class="flex items-center gap-3 pb-2 sm:block sm:pt-2.5 sm:pr-6 sm:pb-0 sm:text-right">
                            <span @class([
                                'relative size-3 shrink-0 rounded-full sm:hidden',
                                'bg-red-500' => $slotLive,
                                'bg-club-navy' => ! $slotLive && $slotDone,
                                'bg-white ring-2 ring-sky-club ring-inset' => ! $slotLive && ! $slotDone,
                            ])>@if($slotLive)<span class="absolute inset-0 animate-ping rounded-full bg-red-500/60"></span>@endif</span>
                            @if($time !== '')
                                <time datetime="{{ $day }}T{{ $time }}" @class(['font-display text-2xl leading-none font-semibold tabular-nums sm:text-[2rem]', 'text-zinc-400' => $slotDone, 'text-zinc-900' => ! $slotDone])>{{ $time }}</time>
                            @else
                                <span class="text-sm font-semibold text-zinc-400">{{ __('Godzina do ustalenia') }}</span>
                            @endif
                        </div>
                        <div class="relative ml-[5px] border-l-2 border-zinc-200 pb-6 pl-4 sm:ml-0 sm:pb-8 sm:pl-6">
                            <span @class([
                                'absolute top-4 -left-[7px] hidden size-3 rounded-full sm:block',
                                'bg-red-500' => $slotLive,
                                'bg-club-navy' => ! $slotLive && $slotDone,
                                'bg-white ring-2 ring-sky-club ring-inset' => ! $slotLive && ! $slotDone,
                            ])>@if($slotLive)<span class="absolute inset-0 animate-ping rounded-full bg-red-500/60"></span>@endif</span>
                            <div @class([
                                'grid gap-3 sm:gap-4',
                                'md:grid-cols-2' => $courtColumns->count() !== 3,
                                'lg:grid-cols-3' => $courtColumns->count() === 3,
                                'xl:grid-cols-3' => $courtColumns->isEmpty(),
                            ])>
                                @foreach($slotRows as $row)
                                    @php
                                        $match = $row['match'];
                                        $winnerId = $match->winner()?->id;
                                        $column = $date && $match->court ? $courtColumns->get($match->court) : null;
                                        $destinations = $bracket->destinations($match);
                                        $tournament = $match->tournament();
                                        $ownMatch = collect($row['sides'])->contains(fn ($side) => $side['participant']?->isOwnTeam() && $side['state'] !== TournamentBracket::PENDING);
                                    @endphp
                                    <article
                                        x-show="shows(@js($row['teams']))"
                                        @if($column) style="--court: {{ $column }}" @endif
                                        @class([
                                            'group relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-zinc-900/10',
                                            'md:[grid-column-start:var(--court)]' => $column && $courtColumns->count() === 2,
                                            'lg:[grid-column-start:var(--court)]' => $column && $courtColumns->count() === 3,
                                            'ring-sky-club/50 hover:ring-sky-club' => $ownMatch,
                                            'ring-zinc-200 hover:ring-sky-club/50' => ! $ownMatch,
                                        ])
                                    >
                                        <header class="flex items-center gap-2 border-b border-zinc-100 px-3 py-2">
                                            <span class="rounded-md bg-(--tm-number-bg) px-1.5 text-sm font-bold text-(--tm-number-text) tabular-nums">#{{ $match->number }}</span>
                                            @if($tournament)
                                                <a href="{{ route('tournaments.matches.show', [$tournament, $match]) }}"
                                                    class="min-w-0 truncate text-xs font-semibold tracking-wide text-sky-club-dark uppercase after:absolute after:inset-0 after:content-[''] focus-visible:outline-none group-focus-within:underline"
                                                    aria-label="{{ __('Mecz #:number', ['number' => $match->number]) }}: {{ $row['sides']['home']['text'] }} – {{ $row['sides']['away']['text'] }}">{{ $match->title() }}</a>
                                            @else
                                                <span class="min-w-0 truncate text-xs font-semibold tracking-wide text-sky-club-dark uppercase">{{ $match->title() }}</span>
                                            @endif
                                            <span class="ml-auto flex shrink-0 items-center gap-2">
                                                @if($row['live'])
                                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-bold text-red-600 ring-1 ring-red-200">
                                                        <span class="relative flex size-2"><span class="absolute inset-0 animate-ping rounded-full bg-red-500/70"></span><span class="relative size-2 rounded-full bg-red-500"></span></span>
                                                        {{ __('Trwa') }}
                                                    </span>
                                                @endif
                                                @if($stream = $match->normalizedStreams()[0] ?? null)
                                                    <a href="{{ $stream['url'] }}" target="_blank" rel="noopener" class="relative z-10 inline-flex items-center gap-1 rounded-full bg-red-600 px-2 py-0.5 text-[11px] font-bold text-white hover:bg-red-700" title="{{ __('Transmisja') }}">
                                                        <svg class="size-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.3 2.84A1.5 1.5 0 0 0 4 4.11v11.78a1.5 1.5 0 0 0 2.3 1.27l9.34-5.89a1.5 1.5 0 0 0 0-2.54L6.3 2.84Z"/></svg>
                                                        {{ __('Transmisja') }}
                                                    </a>
                                                @endif
                                                @if($match->court)
                                                    <span @class(['inline-flex items-center gap-1 text-xs text-zinc-500', 'md:hidden' => $column && $courtColumns->count() === 2, 'lg:hidden' => $column && $courtColumns->count() === 3])>
                                                        <svg class="size-3.5 text-zinc-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="m9.69 18.933.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 1 0 3 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 0 0 2.273 1.765 11.842 11.842 0 0 0 .976.544l.062.029.018.008.006.003ZM10 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" clip-rule="evenodd"/></svg>
                                                        {{ $match->court }}
                                                    </span>
                                                @endif
                                            </span>
                                        </header>

                                        <div class="flex-1 py-1">
                                            @foreach($row['sides'] as $sideKey => $info)
                                                @php
                                                    $participant = $info['participant'];
                                                    $confirmed = $info['state'] === TournamentBracket::CONFIRMED && $participant;
                                                    $isWinner = $winnerId && $participant && $winnerId === $participant->id;
                                                    $sets = $match->{$sideKey.'_sets'};
                                                    $own = $confirmed && $participant->isOwnTeam();
                                                    $pid = $participant && $info['state'] !== TournamentBracket::PENDING ? (string) $participant->id : null;
                                                @endphp
                                                <div @class(['relative flex items-center gap-2.5 px-3 py-1.5 transition-colors', 'bg-sky-club/[0.07]' => $own])
                                                    @if($pid) :class="team === @js($pid) && 'bg-amber-100/70!'" @endif>
                                                    @if($own)<span class="absolute inset-y-1 left-0 w-1 rounded-r bg-sky-club" aria-hidden="true"></span>@endif
                                                    @if($confirmed)
                                                        <x-avatar logo :photo="$participant->logoPath()" :name="$participant->name()" class="h-7 w-7 shrink-0 rounded-full text-[10px] ring-1 ring-zinc-200" />
                                                    @else
                                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-dashed border-zinc-300 text-[10px] font-bold text-zinc-400">{{ $info['chip'] ?? '?' }}</span>
                                                    @endif
                                                    <span @class([
                                                        'min-w-0 flex-1 truncate text-[15px]',
                                                        'font-bold text-zinc-900' => $confirmed && $isWinner,
                                                        'font-semibold text-zinc-900' => $confirmed && ! $winnerId,
                                                        'text-zinc-500' => $confirmed && $winnerId && ! $isWinner,
                                                        'italic text-zinc-500' => $info['state'] === TournamentBracket::PROJECTED,
                                                        'italic text-zinc-400' => $info['state'] === TournamentBracket::PENDING,
                                                    ]) title="{{ $info['text'] }}">{{ $info['text'] }}</span>
                                                    @if($info['state'] === TournamentBracket::PROJECTED)
                                                        <span class="shrink-0 rounded-full border border-dashed border-zinc-300 px-1.5 text-[10px] text-zinc-400" title="{{ __('Grupa jeszcze trwa, kolejność może się zmienić') }}">{{ __('prognoza') }}</span>
                                                    @endif
                                                    @if($own)<span class="shrink-0 rounded bg-sky-club px-1 text-[9px] font-bold text-white">{{ mb_strtoupper($site['club_short_name']) }}</span>@endif
                                                    @if($sets !== null)
                                                        <span @class(['w-6 shrink-0 text-right font-display text-xl leading-none tabular-nums', 'font-bold text-zinc-900' => $isWinner, 'text-zinc-400' => ! $isWinner])>{{ $sets }}</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>

                                        @if($match->setsLabel() || (! $match->isFinished() && ($destinations['winner'] || $destinations['loser'])))
                                            <footer class="flex flex-wrap items-center gap-x-3 gap-y-0.5 border-t border-zinc-100 bg-zinc-50/70 px-3 py-1.5 text-[11px] text-zinc-500">
                                                @if($match->setsLabel())
                                                    <span class="tabular-nums"><span class="sr-only">{{ __('Sety') }}: </span>{{ str_replace(', ', ' · ', $match->setsLabel()) }}</span>
                                                @endif
                                                @if(! $match->isFinished() && ($destinations['winner'] || $destinations['loser']))
                                                    <span class="ml-auto flex gap-3 text-zinc-400">
                                                        @if($destinations['winner'])<span>{{ __('zwycięzca') }} → {{ $destinations['winner'] }}</span>@endif
                                                        @if($destinations['loser'])<span>{{ __('przegrany') }} → {{ $destinations['loser'] }}</span>@endif
                                                    </span>
                                                @endif
                                            </footer>
                                        @endif
                                        <div class="relative z-10 px-3 empty:hidden [&>p]:mt-0 [&>p]:pb-2 [&>p]:pt-1"><x-tournament-match-media :match="$match" :streams="false" /></div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @empty
        <div class="rounded-3xl bg-white p-12 text-center text-zinc-500 ring-1 ring-zinc-200">{{ __('Terminarz meczów pojawi się wkrótce.') }}</div>
    @endforelse
</div>

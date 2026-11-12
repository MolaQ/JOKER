<div @class(['inline-flex' => $state !== 'hidden', 'hidden' => $state === 'hidden'])
     @if($state !== 'hidden')
         id="kibicowskie-mvp" style="--mvp: {{ $color }}"
         x-data="{ open: false,
             show() { this.open = true; document.documentElement.classList.add('overflow-hidden'); this.$nextTick(() => this.$refs.close?.focus({ preventScroll: true })); },
             hide() { this.open = false; document.documentElement.classList.remove('overflow-hidden'); this.$refs.trigger?.focus({ preventScroll: true }); },
             init() { if (location.hash === '#kibicowskie-mvp') { this.show(); } } }"
         x-on:keydown.escape.window="open && hide()"
     @endif>
@if($state !== 'hidden')
    @php($winnerNames = $winners->map(fn ($row) => $row['player']->displayName())->implode(', '))
    <button type="button" x-ref="trigger" x-on:click="show()" aria-haspopup="dialog"
            @class(['fan-mvp inline-flex max-w-full items-center gap-1.5 rounded-full px-3 py-1 text-sm font-bold transition hover:brightness-110', 'fan-mvp-pulse bg-[var(--mvp)] text-ink' => $state === 'open', 'bg-[color-mix(in_srgb,var(--mvp)_18%,transparent)] text-[var(--mvp)] ring-1 ring-[color-mix(in_srgb,var(--mvp)_45%,transparent)]' => $state !== 'open'])>
        <span aria-hidden="true">{{ in_array($state, ['off', 'before'], true) ? '⚙' : '★' }}</span>
        <span class="min-w-0 truncate">{{ $title }}@if($state === 'open') · {{ $votedFor ? __('zmień głos') : __('zagłosuj') }}@elseif($state === 'closed' && $winnerNames): {{ $winnerNames }}@endif</span>
    </button>

    @teleport('body')
        <div x-cloak x-show="open" x-transition.opacity class="fan-mvp fixed inset-0 z-[80] flex items-end justify-center bg-black/70 backdrop-blur-sm sm:items-center sm:p-4" style="--mvp: {{ $color }}" x-on:click.self="hide()" role="dialog" aria-modal="true" aria-label="{{ $title }}">
            <div class="fan-mvp-sheet relative flex max-h-[92dvh] w-full max-w-2xl flex-col overflow-hidden rounded-t-3xl bg-ink text-white ring-1 ring-[color-mix(in_srgb,var(--mvp)_45%,transparent)] sm:rounded-3xl"
                 @if($state === 'open') x-data="{ picked: null, pickedName: '', left: '', ends: {{ $ends->getTimestampMs() }},
                     tick() { const s = Math.max(0, Math.floor((this.ends - Date.now()) / 1000)); this.left = [Math.floor(s / 3600), Math.floor(s % 3600 / 60), s % 60].map((n) => String(n).padStart(2, '0')).join(':'); if (s === 0) { clearInterval(this.timer); $wire.$refresh(); } },
                     init() { this.tick(); this.timer = setInterval(() => this.tick(), 1000); } }" @endif
                 wire:key="fan-mvp-sheet-{{ $state }}">
                <div class="fan-mvp-glow" aria-hidden="true"></div>

                <div class="relative flex items-start justify-between gap-3 px-5 pt-5 sm:px-6">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold tracking-[0.2em] text-white/60 uppercase">{{ $state === 'closed' ? __('Wybór kibiców') : __('Głosowanie kibiców') }}</p>
                        <h2 class="fan-mvp-title mt-0.5 font-display text-2xl font-bold break-words sm:text-3xl">★ {{ $title }}</h2>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        @if($state === 'open')
                            <p class="rounded-xl bg-white/5 px-3 py-1 text-right ring-1 ring-white/10"><span class="block text-[10px] font-semibold tracking-widest text-white/60 uppercase">{{ __('Do końca') }}</span><span class="font-display text-lg font-bold tabular-nums text-[var(--mvp)]" x-text="left" role="timer">{{ $ends->format('H:i') }}</span></p>
                        @endif
                        <button type="button" x-ref="close" x-on:click="hide()" class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-lg hover:bg-white/20" aria-label="{{ __('Zamknij') }}">✕</button>
                    </div>
                </div>

                <div class="relative min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 pt-3 pb-5 sm:px-6">
                    @if($state === 'open')
                        @if($intro)<p class="text-sm text-white/75">{{ $intro }}</p>@endif

                        @if($voteBlock)
                            <p class="mt-3 rounded-xl bg-white/10 px-3 py-2 text-sm">{{ $voteBlock }}</p>
                        @elseif($votedFor && ($mine = $candidates->firstWhere('id', $votedFor)))
                            <p class="mt-3 rounded-xl bg-[color-mix(in_srgb,var(--mvp)_18%,transparent)] px-3 py-2 text-sm ring-1 ring-[color-mix(in_srgb,var(--mvp)_45%,transparent)]" wire:key="mine-{{ $votedFor }}">
                                <strong>★ {{ __('Twój głos') }}: {{ $mine->displayName() }}.</strong>
                                {{ __('Możesz go zmienić do :time. Wynik poznamy po zakończeniu głosowania.', ['time' => $ends->format('H:i')]) }}
                            </p>
                        @endif

                        <ul class="mt-4 grid grid-cols-3 gap-2.5 sm:grid-cols-4 md:grid-cols-5">
                            @foreach($candidates as $player)
                                <li wire:key="candidate-{{ $player->id }}" class="relative">
                                    <button type="button" @disabled($voteBlock)
                                            x-on:click="picked = {{ $player->id }}; pickedName = @js($player->displayName())"
                                            :class="picked === {{ $player->id }} && 'is-picked'"
                                            :aria-pressed="picked === {{ $player->id }}"
                                            @class(['fan-mvp-card relative block h-full w-full overflow-hidden rounded-xl bg-white/5 text-center ring-1 ring-white/10 focus-visible:outline-2 focus-visible:outline-[var(--mvp)]', 'is-voted' => $votedFor === $player->id])>
                                        <x-avatar :photo="$player->publicPhotoPath()" :name="$player->displayName()" :label="$player->jersey_number" class="aspect-square w-full text-3xl" />
                                        <span class="block px-1.5 py-1.5 text-[11px] leading-tight font-semibold break-words hyphens-auto sm:text-xs">
                                            {{ $player->displayName() }}
                                        </span>
                                        @if($votedFor === $player->id)
                                            <span class="absolute top-1 right-1 rounded-full bg-[var(--mvp)] px-1.5 text-[10px] font-bold text-ink" title="{{ __('Twój głos') }}">★</span>
                                        @endif
                                    </button>
                                    @if($justVoted === $player->id)
                                        <span class="fan-mvp-burst" aria-hidden="true" wire:key="burst-{{ $player->id }}-{{ now()->timestamp }}">@for($i = 0; $i < 12; $i++)<span style="--a: {{ $i * 30 }}deg"></span>@endfor</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        @foreach($blockedNotes as $note)
                            <p class="mt-3 text-xs text-white/60">ⓘ {{ $note }}.</p>
                        @endforeach

                        @if($candidates->isEmpty())
                            <p class="mt-3 text-sm text-white/70">{{ __('Lista zawodników tego meczu jest pusta.') }}</p>
                        @endif

                        <div x-cloak x-show="picked && picked !== {{ $votedFor ?? 'null' }}" x-transition.opacity class="sticky bottom-0 z-10 mt-4 flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-white px-4 py-2.5 text-ink shadow-2xl">
                            <p class="min-w-0 text-sm"><span class="text-zinc-500">{{ $votedFor ? __('Zmieniam głos na') : __('Mój wybór') }}:</span> <strong class="break-words" x-text="pickedName"></strong></p>
                            <button type="button" class="rounded-full bg-[var(--mvp)] px-5 py-2 text-sm font-bold text-ink transition hover:brightness-110 active:scale-95" x-on:click="$wire.vote(picked).then(() => picked = null)" wire:loading.attr="disabled">★ {{ __('Oddaj głos') }}</button>
                        </div>
                    @elseif($state === 'closed')
                        @if($winners->isEmpty())
                            <p class="text-sm text-white/70">{{ __('W tym meczu kibice nie oddali głosów.') }}</p>
                        @else
                            <div class="mt-2 grid gap-5 sm:grid-cols-[auto_1fr] sm:items-center">
                                <div class="flex flex-wrap justify-center gap-3">
                                    @foreach($winners as $winner)
                                        <a href="{{ route('players.show', $winner['player']) }}" class="fan-mvp-winner fan-mvp-shine relative block w-36 overflow-hidden rounded-2xl ring-4 ring-[var(--mvp)] sm:w-40" wire:key="winner-{{ $winner['player']->id }}">
                                            <x-avatar :photo="$winner['player']->publicPhotoPath()" :name="$winner['player']->displayName()" :label="$winner['player']->jersey_number" class="aspect-[4/5] w-full text-5xl" />
                                            <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 via-black/60 to-transparent px-2 pt-10 pb-2 text-center">
                                                <span class="block text-sm leading-tight font-bold break-words hyphens-auto">{{ $winner['player']->displayName() }}</span>
                                                <span class="block font-display text-xl font-bold text-[var(--mvp)]">{{ $winner['percent'] }}%</span>
                                            </span>
                                            <span class="absolute top-2 left-1/2 -translate-x-1/2 rounded-full bg-[var(--mvp)] px-3 py-0.5 text-xs font-bold whitespace-nowrap text-ink">★ MVP</span>
                                        </a>
                                    @endforeach
                                </div>
                                <ol class="min-w-0 space-y-2.5">
                                    @foreach($results->where('votes', '>', 0)->take(6) as $row)
                                        <li wire:key="result-{{ $row['player']->id }}">
                                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                                <span class="min-w-0 truncate font-semibold">{{ $row['player']->displayName() }}</span>
                                                <span class="font-display font-bold tabular-nums {{ $row['winner'] ? 'text-[var(--mvp)]' : 'text-white/80' }}">{{ $row['percent'] }}%</span>
                                            </div>
                                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-white/10"><div class="fan-mvp-bar h-full rounded-full {{ $row['winner'] ? 'bg-[var(--mvp)]' : 'bg-white/50' }}" style="width: {{ $row['percent'] }}%"></div></div>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                    @endif

                    @if($canManage)
                        <details class="mt-5 rounded-2xl bg-zinc-50 text-sm text-zinc-800 ring-1 ring-zinc-200" @if(in_array($state, ['off', 'before'], true)) open @endif>
                            <summary class="cursor-pointer px-4 py-3 font-semibold">⚙ {{ __('Zarządzanie (widzą tylko prowadzący)') }}</summary>
                            <div class="space-y-4 border-t border-zinc-200 px-4 py-4">
                                <p>
                                    {{ __('Stan') }}: <strong>{{ ['off' => $disabled ? __('wyłączone w tym meczu') : __('brak głosowania (mecz bez godziny albo moduł wyłączony)'), 'before' => __('zacznie się o :time', ['time' => $starts?->format('d.m H:i')]), 'open' => __('trwa do :time', ['time' => $ends?->format('d.m H:i')]), 'closed' => __('zakończone')][$state] }}</strong>
                                    · {{ __('oddane głosy') }}: <strong>{{ $totalVotes }}</strong>
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="toggleDisabled" class="rounded-lg bg-white px-3 py-1.5 font-semibold ring-1 ring-zinc-300 hover:bg-zinc-100">{{ $disabled ? __('Włącz głosowanie w tym meczu') : __('Wyłącz głosowanie w tym meczu') }}</button>
                                    @if($state === 'open')
                                        <button type="button" wire:click="closeNow" wire:confirm="{{ __('Zakończyć głosowanie teraz? Wynik od razu pokaże się zalogowanym.') }}" class="rounded-lg bg-white px-3 py-1.5 font-semibold ring-1 ring-zinc-300 hover:bg-zinc-100">{{ __('Zakończ teraz') }}</button>
                                    @endif
                                </div>
                                <p class="text-xs text-zinc-500">{{ __('Zawodnik nie grał albo głosy były żartem? Zdejmij go z listy. W trakcie głosowania jego głosy wracają do kibiców, po zakończeniu tytuł przechodzi na następnego według głosów.') }}</p>
                                <ul class="divide-y divide-zinc-200">
                                    @foreach($roster as $player)
                                        @php($isExcluded = in_array($player->id, $excluded, true))
                                        <li class="flex flex-wrap items-center justify-between gap-2 py-2" wire:key="manage-{{ $player->id }}">
                                            <span @class(['min-w-0 break-words', 'text-zinc-400 line-through' => $isExcluded])>{{ $player->displayName() }} <span class="text-xs text-zinc-500 no-underline">· {{ $results->firstWhere('player.id', $player->id)['votes'] ?? 0 }} {{ __('gł.') }}</span></span>
                                            @if($isExcluded)
                                                <button type="button" wire:click="restore({{ $player->id }})" class="rounded-lg px-3 py-1 text-xs font-semibold text-sky-club-dark ring-1 ring-sky-club/40 hover:bg-sky-50">{{ __('Przywróć') }}</button>
                                            @else
                                                <button type="button" wire:click="exclude({{ $player->id }})" wire:confirm="{{ __('Zdjąć :name z głosowania w tym meczu?', ['name' => $player->displayName()]) }}" class="rounded-lg px-3 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-200 hover:bg-red-50">{{ __('Usuń z głosowania') }}</button>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="flex flex-wrap items-end gap-2 border-t border-zinc-200 pt-3">
                                    <label class="min-w-0 flex-1 text-xs font-semibold text-zinc-600">{{ __('Dopisz zawodnika spoza składu') }}
                                        <select wire:model="extraPlayerId" class="mt-1 block w-full rounded-lg border-zinc-300 text-sm">
                                            <option value="">{{ __('— wybierz zawodnika —') }}</option>
                                            @foreach($addable as $player)
                                                <option value="{{ $player->id }}">{{ $player->last_name }} {{ $player->first_name }} ({{ $player->team?->name }})</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <button type="button" wire:click="addPlayer" class="rounded-lg bg-ink px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800">{{ __('Dopisz') }}</button>
                                </div>
                            </div>
                        </details>
                    @endif
                </div>
            </div>
        </div>
    @endteleport
@endif
</div>

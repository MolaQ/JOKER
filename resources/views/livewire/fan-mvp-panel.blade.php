<div>
@if($state !== 'hidden')
    <section id="kibicowskie-mvp" class="fan-mvp scroll-mt-32" style="--mvp: {{ $color }}">
        @if($state === 'open')
            <div class="relative overflow-hidden rounded-3xl bg-ink p-5 text-white ring-1 ring-[color-mix(in_srgb,var(--mvp)_45%,transparent)] sm:p-8"
                 x-data="{ picked: null, pickedName: '', left: '', ends: {{ $ends->getTimestampMs() }},
                     tick() { const s = Math.max(0, Math.floor((this.ends - Date.now()) / 1000)); this.left = [Math.floor(s / 3600), Math.floor(s % 3600 / 60), s % 60].map((n) => String(n).padStart(2, '0')).join(':'); if (s === 0) { clearInterval(this.timer); $wire.$refresh(); } },
                     init() { this.tick(); this.timer = setInterval(() => this.tick(), 1000); } }">
                <div class="fan-mvp-glow" aria-hidden="true"></div>
                <div class="relative flex flex-wrap items-end justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold tracking-[0.2em] text-white/60 uppercase">{{ __('Głosowanie kibiców') }}</p>
                        <h2 class="fan-mvp-title mt-1 font-display text-3xl font-bold break-words sm:text-4xl">★ {{ $title }}</h2>
                        @if($intro)<p class="mt-2 max-w-xl text-sm text-white/75">{{ $intro }}</p>@endif
                    </div>
                    <div class="rounded-2xl bg-white/5 px-4 py-2 text-right ring-1 ring-white/10">
                        <p class="text-[11px] font-semibold tracking-widest text-white/60 uppercase">{{ __('Do końca') }}</p>
                        <p class="font-display text-2xl font-bold tabular-nums text-[var(--mvp)]" x-text="left" role="timer">{{ $ends->format('H:i') }}</p>
                    </div>
                </div>

                @if(! $active)
                    <p class="relative mt-5 rounded-xl bg-white/10 px-4 py-3 text-sm">{{ __('Głosować możesz po aktywacji konta przez klub.') }}</p>
                @elseif($votedFor && ($mine = $candidates->firstWhere('id', $votedFor)))
                    <p class="relative mt-5 rounded-xl bg-[color-mix(in_srgb,var(--mvp)_18%,transparent)] px-4 py-3 text-sm ring-1 ring-[color-mix(in_srgb,var(--mvp)_45%,transparent)]" wire:key="mine-{{ $votedFor }}">
                        <strong>★ {{ __('Twój głos') }}: {{ $mine->displayName() }}.</strong>
                        {{ __('Możesz go zmienić do :time. Wynik poznamy po zakończeniu głosowania.', ['time' => $ends->format('H:i')]) }}
                    </p>
                @endif

                <ul class="relative mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
                    @foreach($candidates as $player)
                        @php($reason = $blocked[$player->id] ?? null)
                        <li wire:key="candidate-{{ $player->id }}" class="relative">
                            <button type="button" @disabled($reason || ! $active)
                                    @click="picked = {{ $player->id }}; pickedName = @js($player->displayName())"
                                    :class="picked === {{ $player->id }} && 'is-picked'"
                                    :aria-pressed="picked === {{ $player->id }}"
                                    @class(['fan-mvp-card group relative block w-full overflow-hidden rounded-2xl bg-white/5 text-left ring-1 ring-white/10 focus-visible:outline-2 focus-visible:outline-[var(--mvp)]', 'is-voted' => $votedFor === $player->id, 'cursor-not-allowed opacity-45 grayscale' => $reason])>
                                <x-avatar :photo="$player->publicPhotoPath()" :name="$player->displayName()" :label="$player->jersey_number" class="aspect-[4/5] w-full text-5xl" />
                                <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 via-black/60 to-transparent px-3 pt-10 pb-3">
                                    @if($player->jersey_number !== null)<span class="block font-display text-sm font-bold text-[var(--mvp)]">#{{ $player->jersey_number }}</span>@endif
                                    <span class="block text-sm leading-tight font-bold break-words hyphens-auto sm:text-base">{{ $player->displayName() }}</span>
                                    @if($reason)<span class="mt-1 block text-[11px] leading-tight text-white/80">{{ $reason }}</span>@endif
                                </span>
                                @if($votedFor === $player->id)
                                    <span class="absolute top-2 right-2 rounded-full bg-[var(--mvp)] px-2 py-0.5 text-[11px] font-bold text-ink">★ {{ __('Twój głos') }}</span>
                                @endif
                            </button>
                            @if($justVoted === $player->id)
                                <span class="fan-mvp-burst" aria-hidden="true" wire:key="burst-{{ $player->id }}-{{ now()->timestamp }}">@for($i = 0; $i < 12; $i++)<span style="--a: {{ $i * 30 }}deg"></span>@endfor</span>
                            @endif
                        </li>
                    @endforeach
                </ul>

                @if($candidates->isEmpty())
                    <p class="relative mt-5 text-sm text-white/70">{{ __('Lista zawodników tego meczu jest pusta.') }}</p>
                @endif

                <div x-cloak x-show="picked && picked !== {{ $votedFor ?? 'null' }}" x-transition.opacity class="sticky bottom-20 z-10 mt-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white px-4 py-3 text-ink shadow-2xl lg:bottom-4">
                    <p class="min-w-0 text-sm"><span class="text-zinc-500">{{ $votedFor ? __('Zmieniam głos na') : __('Mój wybór') }}:</span> <strong class="break-words" x-text="pickedName"></strong></p>
                    <button type="button" class="rounded-full bg-[var(--mvp)] px-5 py-2.5 text-sm font-bold text-ink transition hover:brightness-110 active:scale-95" @click="$wire.vote(picked).then(() => picked = null)" wire:loading.attr="disabled">★ {{ __('Oddaj głos') }}</button>
                </div>
            </div>
        @elseif($state === 'closed')
            <div class="relative overflow-hidden rounded-3xl bg-ink p-5 text-white ring-1 ring-[color-mix(in_srgb,var(--mvp)_45%,transparent)] sm:p-8">
                <div class="fan-mvp-glow" aria-hidden="true"></div>
                <p class="relative text-xs font-semibold tracking-[0.2em] text-white/60 uppercase">{{ __('Wybór kibiców') }}</p>
                <h2 class="fan-mvp-title relative mt-1 font-display text-3xl font-bold break-words sm:text-4xl">★ {{ $title }}</h2>

                @if($winners->isEmpty())
                    <p class="relative mt-4 text-sm text-white/70">{{ __('W tym meczu kibice nie oddali głosów.') }}</p>
                @else
                    <div class="relative mt-6 grid gap-6 lg:grid-cols-[auto_1fr] lg:items-center">
                        <div class="flex flex-wrap justify-center gap-4">
                            @foreach($winners as $winner)
                                <a href="{{ route('players.show', $winner['player']) }}" class="fan-mvp-winner fan-mvp-shine relative block w-44 overflow-hidden rounded-3xl ring-4 ring-[var(--mvp)] sm:w-52" wire:key="winner-{{ $winner['player']->id }}">
                                    <x-avatar :photo="$winner['player']->publicPhotoPath()" :name="$winner['player']->displayName()" :label="$winner['player']->jersey_number" class="aspect-[4/5] w-full text-6xl" />
                                    <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 via-black/60 to-transparent px-3 pt-12 pb-3 text-center">
                                        <span class="block text-base leading-tight font-bold break-words hyphens-auto">{{ $winner['player']->displayName() }}</span>
                                        <span class="mt-1 block font-display text-2xl font-bold text-[var(--mvp)]">{{ $winner['percent'] }}%</span>
                                    </span>
                                    <span class="absolute top-2 left-1/2 -translate-x-1/2 rounded-full bg-[var(--mvp)] px-3 py-0.5 text-xs font-bold whitespace-nowrap text-ink">★ MVP</span>
                                </a>
                            @endforeach
                        </div>
                        <ol class="space-y-2.5">
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
            </div>
        @endif

        @if($canManage)
            <details class="mt-3 rounded-2xl bg-zinc-50 text-sm text-zinc-800 ring-1 ring-zinc-200">
                <summary class="cursor-pointer px-4 py-3 font-semibold">⚙ {{ $title }}: {{ __('zarządzanie (widzą tylko prowadzący)') }}</summary>
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
                </div>
            </details>
        @endif
    </section>
@endif
</div>

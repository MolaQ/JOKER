<!DOCTYPE html>
{{-- Ekran turnieju na telewizor: plansza 1920×1080 skalowana do każdego ekranu, sama przechodzi do następnej (?s=). --}}
<html lang="{{ app()->getLocale() }}" style="background:#071029">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>{{ $tournament->name }} · TV</title>
    @vite(['resources/css/app.css'])
    <style>
        html, body { margin: 0; height: 100%; overflow: hidden; background: #071029; }
        #tv-stage { position: absolute; left: 50%; top: 50%; width: 1920px; height: 1080px; transform-origin: center; }
        @keyframes tv-progress { from { transform: scaleX(1); } to { transform: scaleX(0); } }
        @keyframes tv-in { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: none; } }
        @keyframes tv-ticker { from { transform: translateX(0); } to { transform: translateX(-50%); } }
        .tv-in > * { animation: tv-in .6s ease-out both; }
        .tv-in > *:nth-child(2) { animation-delay: .08s; } .tv-in > *:nth-child(3) { animation-delay: .16s; } .tv-in > *:nth-child(4) { animation-delay: .24s; }
        .tv-in > *:nth-child(5) { animation-delay: .32s; } .tv-in > *:nth-child(6) { animation-delay: .4s; } .tv-in > *:nth-child(7) { animation-delay: .48s; } .tv-in > *:nth-child(8) { animation-delay: .56s; }
    </style>
</head>
<body class="font-sans text-white">
@php
    $category = $slide['category'] ?? null;
    $color = $category?->color() ?? '#38bdf8';
    $bracket = $category ? $brackets[$category->id] : null;
    $logoUrl = $site->image('logo', asset('img/logo-256.webp'));
    $side = fn ($match, $key) => $bracket ? $bracket->side($match, $key) : ['participant' => $match->{$key}, 'text' => $match->{$key}?->name() ?? '—'];
@endphp
<div id="tv-stage" class="flex flex-col overflow-hidden" style="background: radial-gradient(1200px 700px at 85% -10%, {{ $color }}33, transparent 60%), linear-gradient(160deg, #0d1d45 0%, #071029 60%)">
    {{-- Nagłówek: turniej, kategoria, tytuł planszy, zegar i odliczanie --}}
    <header class="flex items-center gap-8 px-16 pt-10 pb-6">
        <img src="{{ $logoUrl }}" alt="" class="h-24 w-24 object-contain">
        <div class="min-w-0 flex-1">
            <p class="font-display text-2xl font-semibold tracking-[0.3em] text-sky-300 uppercase">{{ $tournament->name }}</p>
            <h1 class="mt-1 flex items-center gap-5 font-display text-7xl leading-none font-bold uppercase">
                @if($category)
                    <span class="rounded-2xl px-5 py-1 text-white" style="background: {{ $color }}">{{ $category->name }}</span>
                @endif
                <span>{{ $slide['title'] ?? $tournament->name }}</span>
            </h1>
        </div>
        <div class="text-right">
            <p id="tv-clock" class="font-display text-6xl font-bold tabular-nums">{{ now()->format('H:i') }}</p>
            <p class="mt-1 text-2xl text-white/60">{{ __('plansza') }} {{ $slides->isEmpty() ? 0 : $index + 1 }}/{{ $slides->count() }}</p>
        </div>
        <div class="relative h-28 w-28 shrink-0" aria-label="{{ __('Następna plansza za') }}">
            <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90">
                <circle cx="50" cy="50" r="44" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="8" />
                <circle id="tv-ring" cx="50" cy="50" r="44" fill="none" stroke="{{ $color }}" stroke-width="8" stroke-linecap="round" stroke-dasharray="276.5" stroke-dashoffset="0" />
            </svg>
            <span id="tv-seconds" class="absolute inset-0 flex items-center justify-center font-display text-4xl font-bold tabular-nums">{{ $seconds }}</span>
        </div>
    </header>

    <main class="min-h-0 flex-1 px-16 pb-6">
        @if(! $slide)
            <div class="flex h-full items-center justify-center font-display text-6xl text-white/60 uppercase">{{ __('Plansze pojawią się, gdy turniej będzie miał kategorie i mecze') }}</div>

        @elseif($slide['type'] === 'groups')
            <div @class(['tv-in grid h-full gap-10', 'grid-cols-2' => $slide['groups']->count() > 1])>
                @foreach($slide['groups'] as $group)
                    @php($rows = $bracket->groupTable($group))
                    <section class="flex min-h-0 flex-col overflow-hidden rounded-3xl bg-white/[0.06] ring-1 ring-white/10">
                        <h2 class="flex items-center justify-between px-8 py-5 font-display text-5xl font-bold uppercase" style="background: linear-gradient(90deg, {{ $color }}cc, transparent)">
                            {{ $group->name }}
                            @if($group->court)<span class="text-2xl font-semibold text-white/80">{{ \App\Models\TrainingSession::courtLabel($group->court) }}</span>@endif
                        </h2>
                        <table class="w-full text-left">
                            <thead class="text-xl font-semibold tracking-wider text-white/50 uppercase">
                                <tr><th class="px-8 py-3">#</th><th class="py-3">{{ __('Drużyna') }}</th><th class="px-4 py-3 text-center">M</th><th class="px-4 py-3 text-center">{{ __('Sety') }}</th><th class="px-8 py-3 text-center">{{ __('Pkt') }}</th></tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                    <tr @class(['border-t border-white/10', 'bg-sky-400/15' => $row['participant']->isOwnTeam()])>
                                        <td class="px-8 py-3 font-display text-4xl font-bold" style="color: {{ $row['position'] <= 2 ? $color : 'inherit' }}">{{ $row['position'] }}</td>
                                        <td class="py-3">
                                            <span class="flex items-center gap-4">
                                                <x-avatar logo :photo="$row['participant']->logoPath()" :name="$row['participant']->name()" class="h-12 w-12 shrink-0 rounded-full text-base" />
                                                <span class="truncate text-3xl font-semibold">{{ $row['participant']->name() }}</span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center text-3xl tabular-nums">{{ $row['played'] }}</td>
                                        <td class="px-4 py-3 text-center text-3xl tabular-nums">{{ $row['sets_won'] }}:{{ $row['sets_lost'] }}</td>
                                        <td class="px-8 py-3 text-center font-display text-5xl font-bold tabular-nums">{{ $row['points'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>
                @endforeach
            </div>

        @elseif($slide['type'] === 'matches')
            <div class="tv-in grid h-full grid-cols-2 grid-rows-4 gap-6">
                @foreach($slide['matches'] as $match)
                    @php($winnerId = $match->winner()?->id)
                    <div @class(['flex min-h-0 items-center gap-6 rounded-3xl px-8 ring-1', 'bg-red-500/15 ring-red-400/60' => $match->is_live, 'bg-white/[0.06] ring-white/10' => ! $match->is_live])>
                        <div class="w-36 shrink-0 text-center">
                            @if($match->is_live)
                                <p class="inline-flex items-center gap-2 rounded-full bg-red-500 px-4 py-1 font-display text-2xl font-bold uppercase"><span class="h-3 w-3 animate-pulse rounded-full bg-white"></span>{{ __('Trwa') }}</p>
                            @else
                                <p class="font-display text-5xl font-bold tabular-nums">{{ $match->starts_at?->format('H:i') ?? '—' }}</p>
                                <p class="text-xl text-white/60">{{ $match->starts_at?->translatedFormat('D d.m') }}</p>
                            @endif
                            @if($match->court)<p class="mt-1 text-xl font-semibold text-sky-300">{{ \App\Models\TrainingSession::courtLabel($match->court) }}</p>@endif
                        </div>
                        <div class="min-w-0 flex-1 space-y-2">
                            @foreach(['home', 'away'] as $key)
                                @php($s = $side($match, $key))
                                <div @class(['flex items-center gap-4', 'text-white/50' => $winnerId && $s['participant']?->id !== $winnerId])>
                                    @if($s['participant'])
                                        <x-avatar logo :photo="$s['participant']->logoPath()" :name="$s['text']" class="h-11 w-11 shrink-0 rounded-full text-sm" />
                                    @else
                                        <span class="h-11 w-11 shrink-0 rounded-full bg-white/10"></span>
                                    @endif
                                    <span @class(['min-w-0 flex-1 truncate text-3xl', 'font-bold' => $winnerId && $s['participant']?->id === $winnerId, 'italic text-white/50' => ! $s['participant']])>{{ $s['text'] }}</span>
                                    <span class="w-10 text-right font-display text-5xl font-bold tabular-nums">{{ $match->{$key.'_sets'} ?? '' }}</span>
                                </div>
                            @endforeach
                            @if($match->setsLabel())<p class="text-right text-xl text-white/50 tabular-nums">({{ $match->setsLabel() }})</p>@endif
                        </div>
                        @if($match->number)<span class="self-start pt-5 font-display text-2xl text-white/40">#{{ $match->number }}</span>@endif
                    </div>
                @endforeach
            </div>

        @elseif($slide['type'] === 'bracket')
            <div class="tv-in flex h-full gap-6">
                @foreach($slide['columns'] as $column)
                    <section class="flex min-w-0 flex-1 flex-col gap-4">
                        <h2 class="font-display text-3xl font-bold tracking-wide uppercase" style="color: {{ $color }}">{{ $column['name'] }}</h2>
                        <div class="flex min-h-0 flex-1 flex-col justify-around gap-3">
                            @foreach($column['matches'] as $match)
                                @php($winnerId = $match->winner()?->id)
                                <div @class(['rounded-2xl px-5 py-3 ring-1', 'bg-red-500/15 ring-red-400/60' => $match->is_live, 'bg-white/[0.06] ring-white/10' => ! $match->is_live])>
                                    <p class="mb-1 flex gap-3 text-lg text-white/50">@if($match->number)<span>#{{ $match->number }}</span>@endif<span>{{ $match->label ?: ($match->placement ? __('o :place. miejsce', ['place' => $match->placement]) : '') }}</span><span class="ml-auto tabular-nums">{{ $match->starts_at?->format('H:i') }}</span></p>
                                    @foreach(['home', 'away'] as $key)
                                        @php($s = $side($match, $key))
                                        <div @class(['flex items-center gap-3', 'text-white/50' => $winnerId && $s['participant']?->id !== $winnerId])>
                                            <span @class(['min-w-0 flex-1 truncate text-2xl', 'font-bold' => $winnerId && $s['participant']?->id === $winnerId, 'italic text-white/50' => ! $s['participant']])>{{ $s['text'] }}</span>
                                            <span class="font-display text-3xl font-bold tabular-nums">{{ $match->{$key.'_sets'} ?? '' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

        @elseif($slide['type'] === 'sponsors')
            <div class="tv-in grid h-full grid-cols-4 content-center gap-10">
                @foreach($slide['sponsors'] as $sponsor)
                    <div class="flex h-56 items-center justify-center rounded-3xl bg-white p-8">
                        <img src="{{ \App\Support\Media::url($sponsor->logo_path) }}" alt="{{ $sponsor->name }}" class="max-h-full max-w-full object-contain">
                    </div>
                @endforeach
            </div>
        @endif
    </main>

    {{-- Pasek „Teraz gramy”: mecze w trakcie ze wszystkich kategorii --}}
    @if($live->isNotEmpty())
        <div class="flex h-20 items-center overflow-hidden bg-red-600">
            <span class="z-10 flex h-full shrink-0 items-center gap-3 bg-red-700 px-8 font-display text-3xl font-bold uppercase"><span class="h-4 w-4 animate-pulse rounded-full bg-white"></span>{{ __('Teraz gramy') }}</span>
            <div class="min-w-0 flex-1 overflow-hidden">
                <div class="flex w-max gap-16 pl-10 text-3xl whitespace-nowrap" @if($live->count() > 2) style="animation: tv-ticker {{ 12 * $live->count() }}s linear infinite" @endif>
                    @foreach($live->count() > 2 ? [1, 2] : [1] as $copy)
                        @foreach($live as $match)
                            @php($liveBracket = $brackets[$match->tournament_category_id])
                            <span><strong>{{ $match->category->name }}</strong> · {{ \App\Models\TrainingSession::courtLabel($match->court) }} · {{ $liveBracket->side($match, 'home')['text'] }} <strong class="font-display text-4xl">{{ $match->home_sets ?? 0 }}:{{ $match->away_sets ?? 0 }}</strong> {{ $liveBracket->side($match, 'away')['text'] }}</span>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Stopka: adres strony i pasek czasu do następnej planszy --}}
    <footer class="relative flex h-16 items-center justify-between px-16 text-2xl text-white/70">
        <span>{{ $footer }}</span>
        <span class="font-semibold tracking-wider text-white/50">{{ $site['club_name'] }}</span>
        <span class="absolute inset-x-0 bottom-0 h-2 origin-left" style="background: {{ $color }}; animation: tv-progress {{ $seconds }}s linear forwards"></span>
    </footer>
</div>
<script>
    (() => {
        const stage = document.getElementById('tv-stage');
        const fit = () => stage.style.transform = `translate(-50%, -50%) scale(${Math.min(innerWidth / 1920, innerHeight / 1080)})`;
        fit();
        addEventListener('resize', fit);

        const total = {{ $seconds }};
        const started = Date.now();
        const ring = document.getElementById('tv-ring');
        const label = document.getElementById('tv-seconds');
        const clock = document.getElementById('tv-clock');
        const next = new URL(location.href);
        next.searchParams.set('s', {{ $next }});

        setInterval(() => {
            const left = Math.max(0, total - (Date.now() - started) / 1000);
            label.textContent = Math.ceil(left);
            ring.style.strokeDashoffset = 276.5 * (1 - left / total);
            clock.textContent = new Date().toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' });
            if (left <= 0) location.replace(next);
        }, 250);
    })();
</script>
</body>
</html>

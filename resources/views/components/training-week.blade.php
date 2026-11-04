@props(['sessions', 'selected' => []])
{{-- Plan treningów na jednej tablicy: wiersz na dzień, w nim pasy sektorów (III, II, I jak w hali) i siłowni, czas w poziomie.
     Pigułki z drużynami działają jak przełączniki (można wybrać kilka): wybrane grupy są w kolorze, reszta przygasa,
     więc widać, kiedy trenują i jak przy tym obłożona jest hala. Pod tablicą lista godzin wybranych grup. --}}
@php
    use App\Models\TrainingSession;
    use App\Support\TrainingWeek;

    $week = TrainingWeek::build($sessions);
    $groups = $week['groups'];
    $axis = $week['axis'];
    $names = TrainingSession::venueNames();
    $roman = ['court3' => 'III', 'court2' => 'II', 'court1' => 'I'];
    $today = now()->isoWeekday();
    $hasConflict = $week['days']->contains(fn ($day) => collect($day['courts'])->merge($day['gym'])->contains('conflict', true));
    $style = function (array $group, float $strength = 24): string {
        $fill = 'color-mix(in srgb, '.$group['color'].' '.$strength.'%, var(--tint-base, white))';

        return $group['hatch']
            ? 'background: repeating-linear-gradient(135deg, color-mix(in srgb, '.$group['color'].' 45%, transparent) 0 3px, transparent 3px 8px), '.$fill.';'
            : 'background: '.$fill.';';
    };
    // Godziny na osi: początek każdej pełnej godziny w odcinku; koniec tylko na samym końcu osi.
    $ticks = collect($axis['segments'])->flatMap(fn ($segment, $i) => range($segment[0], $segment[1] - ($i === count($axis['segments']) - 1 ? 0 : 60), 60));
    $gaps = collect($axis['segments'])->slice(0, -1)->map(fn ($segment) => TrainingWeek::position($axis, $segment[1]));
    $hoursLabel = fn (float $hours) => str_replace('.', ',', rtrim(rtrim(number_format($hours, 1, '.', ''), '0'), '.')).' h';
@endphp
@if($week['days']->isNotEmpty())
<div {{ $attributes->merge(['class' => 'training-week']) }}
    x-data="{
        sel: @js(array_values(array_intersect($selected, $groups->keys()->all()))),
        slugs: @js($groups->map(fn ($group) => $group['slug'])),
        info: null,
        now: null,
        on(key) { return this.sel.length === 0 || this.sel.includes(key) },
        any(keys) { return keys.some(key => this.on(key)) },
        toggle(key) { this.sel = this.sel.includes(key) ? this.sel.filter(k => k !== key) : [...this.sel, key]; this.sync() },
        all() { this.sel = []; this.sync() },
        sync() {
            const url = new URL(window.location.href);
            this.sel.length ? url.searchParams.set('team', this.sel.map(k => this.slugs[k]).join(',')) : url.searchParams.delete('team');
            window.history.replaceState(null, '', url);
        },
        tick() {
            // Kreska „teraz” w dzisiejszym wierszu, gdy trwają godziny treningów.
            const d = new Date(), m = d.getHours() * 60 + d.getMinutes(), segs = @js($axis['segments']);
            let offset = 0; this.now = null;
            for (const [from, to] of segs) {
                if (m >= from && m <= to) { this.now = { day: d.getDay() || 7, x: (offset + m - from) / {{ $axis['total'] }} * 100 }; break; }
                offset += to - from + {{ TrainingWeek::GAP }};
            }
        },
    }"
    x-init="tick(); setInterval(() => tick(), 60000)">

    {{-- Wybór grup: pigułki są też legendą kolorów --}}
    <div class="flex flex-wrap items-center gap-2" role="group" aria-label="{{ __('Grupy treningowe') }}">
        <button type="button" class="chip" :class="sel.length === 0 ? 'bg-ink text-white' : 'hover:bg-zinc-200'" x-on:click="all()" :aria-pressed="sel.length === 0">{{ __('Wszystkie') }}</button>
        @foreach($groups as $group)
            <button type="button" class="chip ring-1 ring-inset transition" x-on:click="toggle(@js($group['key']))" :aria-pressed="sel.includes(@js($group['key']))"
                :class="sel.includes(@js($group['key'])) ? 'bg-white text-zinc-900 shadow-sm' : 'ring-transparent hover:bg-zinc-200'"
                style="--tw-ring-color: {{ $group['color'] }}">
                <span class="size-3 rounded-[3px] ring-1 ring-inset" style="{{ $style($group, 60) }} --tw-ring-color: {{ $group['color'] }}"></span>{{ $group['name'] }}
                <svg x-show="sel.includes(@js($group['key']))" x-cloak class="-mr-1 size-3.5 text-zinc-500" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m4 4 8 8M12 4l-8 8"/></svg>
            </button>
        @endforeach
        <p class="w-full text-xs text-zinc-500 sm:ml-1 sm:w-auto" x-show="sel.length === 0">{{ __('Kliknij kilka grup, żeby zobaczyć tylko ich treningi.') }}</p>
    </div>

    {{-- Ile godzin w tygodniu zajęte są sektory i siłownia --}}
    <dl class="mt-6 grid grid-cols-2 gap-2 sm:grid-cols-4">
        @foreach(TrainingWeek::LANES as $lane)
            <div class="flex items-center gap-3 rounded-xl bg-white px-3 py-2.5 ring-1 ring-zinc-200">
                <x-venue-icon :venue="$lane" small />
                <div class="min-w-0">
                    <dt class="truncate text-xs font-semibold text-zinc-500">{{ $names[$lane] }}</dt>
                    <dd class="font-display text-lg leading-tight font-bold">{{ $hoursLabel($week['hours'][$lane]) }} <span class="font-sans text-xs font-medium text-zinc-500">{{ __('w tygodniu') }}</span></dd>
                </div>
            </div>
        @endforeach
    </dl>

    {{-- Tablica tygodnia --}}
    <div class="mt-4 rounded-2xl bg-white p-3 ring-1 ring-zinc-200 sm:p-4">
        {{-- Oś godzin nad tablicą --}}
        <div class="tw-row">
            <div class="max-sm:hidden"></div>
            <div></div>
            <div class="relative h-5">
                @foreach($ticks as $minute)
                    @php $x = TrainingWeek::position($axis, $minute); @endphp
                    <span @class(['absolute top-0 text-[10px] text-zinc-400 tabular-nums sm:text-[11px]', '-translate-x-full' => $x >= 99, '-translate-x-1/2' => $x > 0 && $x < 99]) style="left: {{ $x }}%">{{ intdiv($minute, 60) }}<span class="max-sm:hidden">:00</span></span>
                @endforeach
            </div>
        </div>

        <div class="divide-y divide-zinc-100">
            @foreach($week['days'] as $weekday => $day)
                <section class="tw-row py-2.5" aria-label="{{ __(TrainingSession::WEEKDAYS[$weekday]) }}">
                    <h3 class="font-display text-sm font-bold uppercase max-sm:col-span-2 max-sm:mb-1 sm:self-center sm:text-base">
                        {{ __(TrainingSession::WEEKDAYS[$weekday]) }}
                        @if($weekday === $today)<span class="ml-1 rounded-full bg-sky-club/15 px-1.5 py-0.5 font-sans text-[10px] font-semibold tracking-normal text-sky-club-dark normal-case">{{ __('dziś') }}</span>@endif
                    </h3>

                    {{-- Nazwy pasów --}}
                    <div class="tw-lanes text-[10px] font-semibold text-zinc-500 sm:text-xs">
                        @foreach(TrainingWeek::LANES as $lane)
                            <div @class(['flex items-center', 'tw-gym' => $lane === 'gym']) style="height: var(--lane)">
                                @if($lane === 'gym')
                                    <x-venue-icon venue="gym" small class="h-3.5 w-[22px] sm:hidden" /><span class="truncate max-sm:hidden">{{ $names['gym'] }}</span>
                                @else
                                    <span class="sm:hidden">{{ $roman[$lane] }}</span><span class="truncate max-sm:hidden">{{ $names[$lane] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Pasy z zajęciami --}}
                    <div class="relative">
                        @foreach(['courts' => 3, 'gym' => 1] as $part => $count)
                            <div @class(['relative overflow-hidden rounded-md bg-zinc-50 ring-1 ring-zinc-200', 'tw-gym' => $part === 'gym']) style="height: calc(var(--lane) * {{ $count }})">
                                {{-- linie godzin, ściśnięte przerwy i granice sektorów --}}
                                @foreach($ticks as $minute)
                                    <span class="absolute inset-y-0 w-px bg-zinc-200/70" style="left: {{ TrainingWeek::position($axis, $minute) }}%"></span>
                                @endforeach
                                @foreach($gaps as $gap)
                                    <span class="tw-gap absolute inset-y-0" style="left: {{ $gap }}%; width: {{ round(TrainingWeek::GAP / $axis['total'] * 100, 3) }}%"></span>
                                @endforeach
                                @if($part === 'courts')
                                    <span class="absolute inset-x-0 h-px bg-zinc-300" style="top: 33.333%"></span>
                                    <span class="absolute inset-x-0 h-px bg-zinc-300" style="top: 66.666%"></span>
                                @endif
                                <span x-show="now && now.day === {{ $weekday }}" x-cloak class="absolute inset-y-0 z-10 w-0.5 bg-red-500" :style="now && `left: ${now.x}%`"></span>

                                @foreach($day[$part] as $block)
                                    @php
                                        $session = $block['session'];
                                        $group = $groups[$block['key']];
                                        $text = __(TrainingSession::WEEKDAYS[$weekday]).' · '.$session->timeRange().' · '.$session->label().' · '.$session->venueLabel().($session->note ? ' · '.$session->note : '');
                                        $tall = $block['h'] >= 99 / $count; // zajęcia na całą wysokość pasa mają miejsce na dwie linijki
                                    @endphp
                                    <button type="button" x-on:click="info = @js($text)" title="{{ $text }}"
                                        @class(['absolute flex min-w-0 flex-col justify-center overflow-hidden rounded-[5px] border-l-[3px] px-1 text-left leading-tight text-zinc-900 transition sm:px-1.5', 'ring-2 ring-red-500 ring-inset' => $block['conflict']])
                                        :class="on(@js($block['key'])) ? '' : 'opacity-20 grayscale'"
                                        style="left: calc({{ $block['x'] }}% + 1px); width: calc({{ $block['w'] }}% - 2px); top: calc({{ $block['y'] }}% + 2px); height: calc({{ $block['h'] }}% - 4px); border-left-color: {{ $group['color'] }}; {{ $style($group) }}">
                                        @if($tall)
                                            <span class="truncate text-[10px] font-semibold tabular-nums sm:text-[11px]"><span class="sm:hidden">{{ substr((string) $session->starts_at, 0, 5) }}</span><span class="max-sm:hidden">{{ $session->timeRange() }}</span></span>
                                        @endif
                                        <span class="truncate text-[10px] font-medium sm:text-[11px]">{{ $session->isMini() ? __('Minisiatkówka') : $session->label() }}@if($session->venue === 'main') · {{ $names['main'] }}@endif</span>
                                    </button>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-500">
            <p x-show="! info">{{ __('Kliknij zajęcia, żeby zobaczyć szczegóły.') }}@if($gaps->isNotEmpty()) {{ __('Zygzak na osi to godziny bez treningów.') }}@endif</p>
            <p x-show="info" x-cloak class="font-semibold text-zinc-800" x-text="info" aria-live="polite"></p>
            @if($hasConflict)<span class="rounded-full bg-white px-2 py-0.5 font-semibold text-red-700 ring-2 ring-red-500">{{ __('nakłada się') }}</span>@endif
        </div>
    </div>

    {{-- Lista godzin: wybrane grupy (albo wszystkie) --}}
    <div class="mt-10">
        <h2 class="font-display text-2xl font-bold uppercase">{{ __('Godziny treningów') }}</h2>
        <div class="mt-4 grid gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($week['days'] as $weekday => $day)
                <div x-show="any(@js($day['keys']))">
                    <h3 class="border-b border-zinc-200 pb-1.5 text-sm font-bold">{{ __(TrainingSession::WEEKDAYS[$weekday]) }}</h3>
                    <ul class="mt-2 space-y-1.5">
                        @foreach($sessions->where('weekday', $weekday)->sortBy('starts_at') as $session)
                            @php $group = $groups[TrainingWeek::key($session)]; @endphp
                            <li x-show="on(@js($group['key']))" class="flex items-center gap-2.5 text-sm">
                                <span class="w-[5.6rem] shrink-0 font-semibold whitespace-nowrap tabular-nums">{{ $session->timeRange() }}</span>
                                <span class="size-2.5 shrink-0 rounded-[3px]" style="{{ $style($group, 70) }}"></span>
                                <span class="min-w-0 flex-1 truncate">
                                    @if($session->url())<a href="{{ $session->url() }}" class="font-medium text-sky-club-dark hover:underline">{{ $session->label() }}</a>@else<span class="font-medium">{{ $session->label() }}</span>@endif
                                    <span class="text-zinc-500">· {{ $session->venueLabel() }}</span>
                                    @if($session->note)<span class="block truncate text-xs text-zinc-500">{{ $session->note }}</span>@endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

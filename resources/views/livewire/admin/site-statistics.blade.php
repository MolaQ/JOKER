@php
    $fmt = fn ($number) => number_format((float) $number, 0, ',', ' ');
    $duration = fn ($seconds) => \App\Livewire\Admin\SiteStatistics::formatDuration($seconds);
    $deviceTotal = max(1, $devices->sum());
    $pageName = fn ($row) => \Illuminate\Support\Str::before((string) $row->title, ' | ') ?: $row->path;
    $change = fn (float $now, float $before) => \App\Livewire\Admin\SiteStatistics::change($now, $before);
@endphp
<div class="space-y-6">
    <div class="flex flex-wrap gap-2">
        @foreach(\App\Livewire\Admin\SiteStatistics::PERIODS as $value => $label)
            <button type="button" wire:click="setPeriod({{ $value }})" @class(['rounded-full px-4 py-1.5 text-sm font-semibold', 'bg-sky-600 text-white' => $days === $value, 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50' => $days !== $value])>{{ $label }}</button>
        @endforeach
    </div>

    {{-- Najważniejsze liczby okresu ze zmianą względem poprzedniego okresu tej samej długości --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach([
            ['Wyświetlenia stron', $fmt($summary['views']), 'wszystkie otwarte strony', $change($summary['views'], $previous['views']), false],
            ['Wizyty', $fmt($summary['visits']), 'odwiedzający liczony raz dziennie', $change($summary['visits'], $previous['visits']), false],
            ['Stron na wizytę', number_format($summary['pages_per_visit'], 1, ',', ' '), 'ile stron ogląda jedna osoba', $change($summary['pages_per_visit'], $previous['pages_per_visit']), false],
            ['Odbicia', round($summary['bounce']).'%', 'wizyty zakończone na jednej stronie', $change($summary['bounce'], $previous['bounce']), true],
            ['Średni czas na stronie', $duration($summary['avg_duration']), 'aktywne oglądanie karty', $change($summary['avg_duration'], $previous['avg_duration']), false],
            ['Średnie przewinięcie', round($summary['avg_scroll']).'%', 'jak daleko czytają', $change($summary['avg_scroll'], $previous['avg_scroll']), false],
        ] as [$label, $value, $hint, $delta, $lowerIsBetter])
            <div class="rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200">
                <p class="text-sm text-gray-500">{{ $label }}</p>
                <p class="mt-1 flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-gray-900 tabular-nums">{{ $value }}</span>
                    @if($delta !== null && $delta !== 0)
                        @php $good = $lowerIsBetter ? $delta < 0 : $delta > 0; @endphp
                        <span @class(['rounded-full px-1.5 py-0.5 text-xs font-semibold tabular-nums', 'bg-emerald-50 text-emerald-700' => $good, 'bg-red-50 text-red-700' => ! $good]) title="Zmiana względem poprzednich {{ \App\Livewire\Admin\SiteStatistics::PERIODS[$days] }}">{{ $delta > 0 ? '▲' : '▼' }} {{ abs($delta) }}%</span>
                    @endif
                </p>
                <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Teraz na stronie --}}
        <div class="rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200">
            <h2 class="flex items-center gap-2 font-semibold text-gray-900"><span class="relative flex size-2.5"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-60"></span><span class="relative inline-flex size-2.5 rounded-full bg-emerald-500"></span></span>Teraz na stronie</h2>
            <p class="mt-2 text-4xl font-bold tabular-nums">{{ $fmt($now['people']) }} <span class="text-sm font-normal text-gray-500">{{ $now['people'] === 1 ? 'osoba' : 'osób' }} w ostatnich 5 minutach</span></p>
            <ul class="mt-3 space-y-1.5 text-sm">
                @forelse($now['pages'] as $row)
                    <li class="flex justify-between gap-3"><a href="{{ url($row->path) }}" target="_blank" class="min-w-0 truncate text-gray-700 hover:underline" title="{{ $row->path }}">{{ $pageName($row) }}</a><span class="shrink-0 font-semibold tabular-nums">{{ $row->people }}</span></li>
                @empty
                    <li class="text-gray-500">Nikogo w tej chwili.</li>
                @endforelse
            </ul>
        </div>

        {{-- Dziś godzina po godzinie (szare tło: wczoraj) --}}
        <div class="rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200 lg:col-span-2">
            <h2 class="font-semibold text-gray-900">Dziś godzina po godzinie <span class="text-xs font-normal text-gray-500">— niebieskie dziś, szare wczoraj</span></h2>
            <div class="mt-4 flex h-36 items-end gap-0.5" role="img" aria-label="Wyświetlenia dziś i wczoraj w kolejnych godzinach">
                @foreach($hours as $point)
                    <div class="group relative flex h-full flex-1 items-end">
                        <div class="absolute inset-x-0 bottom-0 rounded-t bg-gray-200" style="height: {{ round($point['yesterday'] / $maxHourly * 100) }}%"></div>
                        <div @class(['relative mx-auto w-2/3 rounded-t', 'bg-sky-600' => $point['hour'] <= now()->hour, 'bg-transparent' => $point['hour'] > now()->hour]) style="height: {{ $point['today'] ? max(2, round($point['today'] / $maxHourly * 100)) : 0 }}%"></div>
                        <span class="pointer-events-none absolute -top-9 left-1/2 z-10 hidden -translate-x-1/2 rounded bg-gray-900 px-1.5 py-0.5 text-center text-[11px] whitespace-nowrap text-white group-hover:block">{{ $point['hour'] }}:00<br>dziś {{ $point['today'] }} · wczoraj {{ $point['yesterday'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-1 flex justify-between text-[11px] text-gray-400"><span>0:00</span><span>6:00</span><span>12:00</span><span>18:00</span><span>23:00</span></div>
        </div>
    </div>

    <div class="rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200">
        <h2 class="font-semibold text-gray-900">Dzień po dniu <span class="text-xs font-normal text-gray-500">— jasne wyświetlenia, ciemne wizyty</span></h2>
        <div class="mt-4 flex h-40 items-end gap-px" role="img" aria-label="Wyświetlenia i wizyty dziennie">
            @foreach($series as $point)
                <div class="group relative flex h-full flex-1 items-end">
                    <div class="absolute inset-x-0 bottom-0 rounded-t bg-sky-200 group-hover:bg-sky-300" style="height: {{ max(1, round($point['views'] / $maxDaily * 100)) }}%"></div>
                    <div class="relative w-full rounded-t bg-sky-700" style="height: {{ round($point['visits'] / $maxDaily * 100) }}%"></div>
                    <span class="pointer-events-none absolute -top-9 left-1/2 z-10 hidden -translate-x-1/2 rounded bg-gray-900 px-1.5 py-0.5 text-center text-[11px] whitespace-nowrap text-white group-hover:block">{{ $point['date']->translatedFormat('D d.m') }}<br>{{ $point['views'] }} wyśw. · {{ $point['visits'] }} wizyt</span>
                </div>
            @endforeach
        </div>
        <div class="mt-1 flex justify-between text-[11px] text-gray-400"><span>{{ $series->first()['date']->format('d.m') }}</span><span>{{ $series->last()['date']->format('d.m') }}</span></div>
    </div>

    {{-- Kiedy odwiedzają: im ciemniejsze pole, tym więcej wyświetleń o tej porze --}}
    <div class="rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200">
        <h2 class="font-semibold text-gray-900">Kiedy odwiedzają <span class="text-xs font-normal text-gray-500">— dzień tygodnia i godzina, ciemniej = więcej osób</span></h2>
        <div class="mt-4 overflow-x-auto">
            <div class="grid min-w-[36rem] grid-cols-[2.25rem_repeat(24,minmax(0,1fr))] gap-0.5 text-[10px] text-gray-400">
                <span></span>
                @foreach(range(0, 23) as $hour)<span class="text-center">{{ $hour % 3 === 0 ? $hour : '' }}</span>@endforeach
                @foreach($heatmap as $row)
                    <span class="pr-1 font-medium leading-5 text-gray-600">{{ $row['day'] }}</span>
                    @foreach($row['hours'] as $hour => $views)
                        <span class="h-5 rounded-sm" style="background: {{ $views ? 'rgb(2 132 199 / '.round(0.12 + $views / $maxHeat * 0.88, 2).')' : 'rgb(244 244 245)' }}" title="{{ $row['day'] }} {{ $hour }}:00 · {{ $views }} wyśw."></span>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200">
            <h2 class="font-semibold text-gray-900">Skąd przychodzą <span class="text-xs font-normal text-gray-500">— wizyty</span></h2>
            <div class="mt-4 space-y-3 text-sm">
                @forelse($sources as $name => $visits)
                    @php $share = round($visits / max(1, $sources->sum()) * 100); @endphp
                    <div>
                        <div class="flex justify-between"><span>{{ $name }}</span><span class="tabular-nums"><b>{{ $fmt($visits) }}</b> <span class="text-gray-500">· {{ $share }}%</span></span></div>
                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-sky-600" style="width: {{ $share }}%"></div></div>
                    </div>
                @empty
                    <p class="text-gray-500">Brak danych.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200">
            <h2 class="font-semibold text-gray-900">Ile stron w jednej wizycie</h2>
            @php $depthTotal = max(1, $depth->sum()); @endphp
            <div class="mt-4 space-y-3 text-sm">
                @foreach($depth as $label => $visits)
                    @php $share = round($visits / $depthTotal * 100); @endphp
                    <div>
                        <div class="flex justify-between"><span>{{ $label }}</span><span class="tabular-nums"><b>{{ $fmt($visits) }}</b> <span class="text-gray-500">· {{ $share }}%</span></span></div>
                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-sky-600" style="width: {{ $share }}%"></div></div>
                    </div>
                @endforeach
            </div>
            <p class="mt-4 text-xs text-gray-500">Język strony: polski {{ round(($locales['pl'] ?? 0) / max(1, $locales->sum()) * 100) }}%, angielski {{ round(($locales['en'] ?? 0) / max(1, $locales->sum()) * 100) }}%.</p>
        </div>

        @foreach([['Strony wejścia', 'od czego zaczynają wizytę', $entries], ['Strony wyjścia', 'gdzie kończą (wizyty z więcej niż jedną stroną)', $exits]] as [$title, $hint, $rows])
            <div class="rounded-lg bg-white shadow ring-1 ring-zinc-200">
                <h2 class="border-b px-5 py-3 font-semibold text-gray-900">{{ $title }} <span class="text-xs font-normal text-gray-500">— {{ $hint }}</span></h2>
                <ul class="divide-y divide-gray-100 text-sm">
                    @forelse($rows as $row)
                        <li class="flex justify-between gap-3 px-5 py-2"><a href="{{ url($row->path) }}" target="_blank" class="min-w-0 truncate text-gray-900 hover:underline" title="{{ $row->path }}">{{ $pageName($row) }}</a><span class="shrink-0 font-semibold tabular-nums">{{ $fmt($row->visits) }}</span></li>
                    @empty
                        <li class="px-5 py-6 text-center text-gray-500">Brak danych.</li>
                    @endforelse
                </ul>
            </div>
        @endforeach
    </div>

    {{-- Najchętniej oglądane treści według rodzaju --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        @foreach([['Artykuły', $topArticles], ['Zawodnicy', $topPlayers], ['Drużyny', $topTeams]] as [$title, $rows])
            <div class="rounded-lg bg-white shadow ring-1 ring-zinc-200">
                <h2 class="border-b px-5 py-3 font-semibold text-gray-900">{{ $title }} <span class="text-xs font-normal text-gray-500">— najczęściej oglądane</span></h2>
                <ol class="divide-y divide-gray-100 text-sm">
                    @forelse($rows as $row)
                        <li class="flex items-center gap-3 px-5 py-2">
                            <span class="w-4 shrink-0 text-xs text-gray-400 tabular-nums">{{ $loop->iteration }}.</span>
                            <a href="{{ url($row->path) }}" target="_blank" class="min-w-0 flex-1 truncate text-gray-900 hover:underline" title="{{ $row->title }}">{{ $pageName($row) }}</a>
                            <span class="shrink-0 text-right tabular-nums"><b>{{ $fmt($row->views) }}</b><span class="block text-[11px] text-gray-500">{{ $duration($row->avg_duration) }}</span></span>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-gray-500">Brak danych.</li>
                    @endforelse
                </ol>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-lg bg-white shadow ring-1 ring-zinc-200">
            <h2 class="border-b px-5 py-3 font-semibold text-gray-900">Najczęściej oglądane <span class="text-xs font-normal text-gray-500">— co przyciąga</span></h2>
            <table data-sortable data-scroll-table class="w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr><th class="px-5 py-2 font-medium">Strona</th><th class="px-2 py-2 text-right font-medium">Wyśw.</th><th class="px-2 py-2 text-right font-medium">Osoby</th><th class="px-5 py-2 text-right font-medium">Czas</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($popular as $row)
                        <tr>
                            <td class="max-w-0 px-5 py-2"><a href="{{ url($row->path) }}" target="_blank" class="block truncate text-gray-900 hover:underline" title="{{ $row->title }}">{{ \Illuminate\Support\Str::before((string) $row->title, ' | ') ?: $row->path }}</a><span class="block truncate text-xs text-gray-400">{{ $row->path }}</span></td>
                            <td class="px-2 py-2 text-right font-semibold tabular-nums">{{ $fmt($row->views) }}</td>
                            <td class="px-2 py-2 text-right tabular-nums text-gray-600">{{ $fmt($row->visitors) }}</td>
                            <td class="px-5 py-2 text-right tabular-nums text-gray-600">{{ $duration($row->avg_duration) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-6 text-center text-gray-500">Brak danych w tym okresie.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-lg bg-white shadow ring-1 ring-zinc-200">
            <h2 class="border-b px-5 py-3 font-semibold text-gray-900">Najdłużej czytane <span class="text-xs font-normal text-gray-500">— co skupia uwagę (min. 3 wyświetlenia)</span></h2>
            <table data-sortable data-scroll-table class="w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr><th class="px-5 py-2 font-medium">Strona</th><th class="px-2 py-2 text-right font-medium">Czas</th><th class="px-2 py-2 text-right font-medium">Przewinięcie</th><th class="px-5 py-2 text-right font-medium">Wyśw.</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($engaging as $row)
                        <tr>
                            <td class="max-w-0 px-5 py-2"><a href="{{ url($row->path) }}" target="_blank" class="block truncate text-gray-900 hover:underline" title="{{ $row->title }}">{{ \Illuminate\Support\Str::before((string) $row->title, ' | ') ?: $row->path }}</a></td>
                            <td class="px-2 py-2 text-right font-semibold tabular-nums">{{ $duration($row->avg_duration) }}</td>
                            <td class="px-2 py-2 text-right tabular-nums text-gray-600">{{ round((float) $row->avg_scroll) }}%</td>
                            <td class="px-5 py-2 text-right tabular-nums text-gray-600">{{ $fmt($row->views) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-6 text-center text-gray-500">Za mało danych.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-lg bg-white shadow ring-1 ring-zinc-200">
            <h2 class="border-b px-5 py-3 font-semibold text-gray-900">Działy strony</h2>
            <ul class="divide-y divide-gray-100 text-sm">
                @forelse($sections as $section)
                    <li class="flex items-center gap-3 px-5 py-2">
                        <span class="w-32 shrink-0 text-gray-900">{{ $section['name'] }}</span>
                        <span class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100"><span class="block h-full rounded-full bg-blue-500" style="width: {{ round($section['views'] / max(1, $sections->max('views')) * 100) }}%"></span></span>
                        <span class="w-14 text-right font-semibold tabular-nums">{{ $fmt($section['views']) }}</span>
                        <span class="w-16 text-right text-xs text-gray-500 tabular-nums">{{ $duration($section['avg_duration']) }}</span>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-gray-500">Brak danych.</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-lg bg-white shadow ring-1 ring-zinc-200">
            <h2 class="border-b px-5 py-3 font-semibold text-gray-900">Otwierane zakładki i filtry</h2>
            <ul class="divide-y divide-gray-100 text-sm">
                @forelse($tabs as $tab)
                    <li class="flex items-center justify-between gap-3 px-5 py-2">
                        <span class="min-w-0 truncate"><span class="text-gray-900">{{ $tab->label }}</span> <span class="text-xs text-gray-400">{{ \Illuminate\Support\Str::before((string) $tab->title, ' | ') }}</span></span>
                        <span class="font-semibold tabular-nums">{{ $fmt($tab->clicks) }}</span>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-gray-500">Brak kliknięć w zakładki.</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-lg bg-white shadow ring-1 ring-zinc-200">
            <h2 class="border-b px-5 py-3 font-semibold text-gray-900">Adresy stron, z których przychodzą</h2>
            <ul class="divide-y divide-gray-100 text-sm">
                @forelse($referrers as $referrer)
                    <li class="flex justify-between px-5 py-2"><span class="text-gray-900">{{ $referrer->referrer_host }}</span><span class="font-semibold tabular-nums">{{ $fmt($referrer->views) }}</span></li>
                @empty
                    <li class="px-5 py-6 text-center text-gray-500">Wejścia bezpośrednie lub brak danych.</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200">
            <h2 class="font-semibold text-gray-900">Urządzenia</h2>
            <div class="mt-4 space-y-3 text-sm">
                @foreach(['mobile' => 'Telefon', 'desktop' => 'Komputer', 'tablet' => 'Tablet'] as $key => $label)
                    @php $share = round(($devices[$key] ?? 0) / $deviceTotal * 100); @endphp
                    <div>
                        <div class="flex justify-between"><span>{{ $label }}</span><span class="font-semibold tabular-nums">{{ $share }}%</span></div>
                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-blue-500" style="width: {{ $share }}%"></div></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <p class="text-xs text-gray-500">Statystyki bez ciasteczek: nie zapisujemy adresów IP, a odwiedzającego rozpoznajemy tylko w ramach jednego dnia. Wejścia administratorów i robotów nie są liczone. Dane starsze niż ok. 13 miesięcy są usuwane automatycznie.</p>
</div>

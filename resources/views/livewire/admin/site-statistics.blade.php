@php
    $fmt = fn ($number) => number_format((float) $number, 0, ',', ' ');
    $duration = fn ($seconds) => \App\Livewire\Admin\SiteStatistics::formatDuration($seconds);
    $deviceTotal = max(1, $devices->sum());
@endphp
<div class="space-y-6">
    <div class="flex flex-wrap gap-2">
        @foreach(\App\Livewire\Admin\SiteStatistics::PERIODS as $value => $label)
            <button type="button" wire:click="setPeriod({{ $value }})" @class(['rounded-full px-4 py-1.5 text-sm font-semibold', 'bg-sky-600 text-white' => $days === $value, 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50' => $days !== $value])>{{ $label }}</button>
        @endforeach
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([
            ['Wyświetlenia stron', $fmt($totals->views ?? 0), 'wszystkie otwarte strony'],
            ['Odwiedzający', $fmt($totals->visitors ?? 0), 'unikalne osoby (liczone dziennie)'],
            ['Średni czas na stronie', $duration($totals->avg_duration), 'aktywne oglądanie karty'],
            ['Średnie przewinięcie', round((float) ($totals->avg_scroll ?? 0)).'%', 'jak daleko czytają'],
        ] as [$label, $value, $hint])
            <div class="rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200">
                <p class="text-sm text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ $value }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    <div class="rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200">
        <h2 class="font-semibold text-gray-900">Wyświetlenia dzień po dniu</h2>
        <div class="mt-4 flex h-40 items-end gap-px" role="img" aria-label="Wykres wyświetleń dziennie">
            @foreach($series as $point)
                <div class="group relative flex h-full flex-1 items-end">
                    <div class="w-full rounded-t bg-blue-500/80 group-hover:bg-sky-600" style="height: {{ max(1, round($point['views'] / $maxDaily * 100)) }}%"></div>
                    <span class="pointer-events-none absolute -top-7 left-1/2 hidden -translate-x-1/2 rounded bg-gray-900 px-1.5 py-0.5 text-[11px] whitespace-nowrap text-white group-hover:block">{{ $point['date']->format('d.m') }}: {{ $point['views'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="mt-1 flex justify-between text-[11px] text-gray-400"><span>{{ $series->first()['date']->format('d.m') }}</span><span>{{ $series->last()['date']->format('d.m') }}</span></div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg bg-white shadow ring-1 ring-zinc-200">
            <h2 class="border-b px-5 py-3 font-semibold text-gray-900">Najczęściej oglądane <span class="text-xs font-normal text-gray-500">— co przyciąga</span></h2>
            <table data-scroll-table class="w-full text-sm">
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
            <table data-scroll-table class="w-full text-sm">
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
            <h2 class="border-b px-5 py-3 font-semibold text-gray-900">Skąd przychodzą</h2>
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

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Harmonogram · {{ $tournament->fullName() }}{{ $chosen ? ' · '.$sheets->first()['category']->name : '' }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4; margin: 12mm 12mm 14mm; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .sheet + .sheet { break-before: page; }
            tr, .keep { break-inside: avoid; }
        }
        [contenteditable]:empty::before { content: attr(data-placeholder); color: #a1a1aa; }
    </style>
</head>
<body class="bg-zinc-100 font-sans text-zinc-900 print:bg-white">
    {{-- Pasek narzędzi (nie drukuje się): wybór kategorii i drukowanie / zapis do PDF --}}
    <div data-no-print class="sticky top-0 z-10 border-b border-zinc-200 bg-white/95 px-4 py-3 shadow-sm backdrop-blur print:hidden">
        <div class="mx-auto flex max-w-[210mm] flex-wrap items-center gap-2 text-sm">
            <a href="{{ route('admin.tournaments.edit', $tournament) }}" class="mr-2 text-sky-700 hover:underline">← Panel turnieju</a>
            <a href="{{ route('admin.tournaments.schedule-sheet', $tournament) }}" @class(['rounded-full px-3 py-1 font-semibold ring-1', 'bg-sky-700 text-white ring-sky-700' => ! $chosen, 'ring-zinc-300 hover:bg-zinc-50' => $chosen])>Wszystkie kategorie</a>
            @foreach($categories as $category)
                <a href="{{ route('admin.tournaments.schedule-sheet', [$tournament, 'category' => $category->id]) }}" @class(['rounded-full px-3 py-1 font-semibold ring-1', 'bg-sky-700 text-white ring-sky-700' => $chosen === $category->id, 'ring-zinc-300 hover:bg-zinc-50' => $chosen !== $category->id])>{{ $category->name }}</a>
            @endforeach
            <button type="button" onclick="window.print()" class="ml-auto rounded-md bg-emerald-600 px-4 py-1.5 font-semibold text-white hover:bg-emerald-700">Drukuj / zapisz PDF</button>
        </div>
        <p class="mx-auto mt-1.5 max-w-[210mm] text-xs text-zinc-500">Pole „Uwagi” pod tabelą możesz uzupełnić przed wydrukiem (np. mecz o 9. miejsce do ustalenia). W oknie drukowania wybierz „Zapisz jako PDF” i wyślij plik gościom.</p>
    </div>

    <main>
    @foreach($sheets as $sheet)
        @php $category = $sheet['category']; @endphp
        <article class="sheet mx-auto my-6 max-w-[210mm] bg-white px-[12mm] py-[10mm] shadow-lg print:m-0 print:max-w-none print:p-0 print:shadow-none">
            {{-- Nagłówek: logo klubu, turniej, kategoria --}}
            <header class="keep flex items-center gap-5 border-b-4 pb-4" style="border-color: {{ $category->color() }}">
                <img src="{{ $logo }}" alt="{{ $clubName }}" class="h-20 w-20 shrink-0 object-contain">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold tracking-[0.2em] text-zinc-500 uppercase">Harmonogram turnieju · {{ $tournament->dateRangeLabel() }}.{{ $tournament->start_date->format('Y') }}</p>
                    <p class="font-display text-xl leading-tight font-bold text-zinc-800 uppercase">{{ $tournament->fullName() }}</p>
                    <h1 class="font-display text-4xl leading-none font-bold uppercase" style="color: {{ $category->color() }}">{{ $category->name }}</h1>
                </div>
                @if($tournamentLogo)<img src="{{ $tournamentLogo }}" alt="" class="h-20 w-20 shrink-0 object-contain">@endif
            </header>

            @if($sheet['summary'])
                <p class="mt-3 text-sm font-semibold text-zinc-700">{{ $sheet['summary'] }}</p>
            @endif

            {{-- Grupy z drużynami i boiskiem --}}
            @if($sheet['groups']->isNotEmpty())
                <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                    @foreach($sheet['groups'] as $group)
                        <div class="keep rounded-md bg-zinc-50 px-3 py-2 ring-1 ring-zinc-200">
                            <p class="font-semibold">{{ $group['name'] }}@if($group['court'])<span class="font-normal text-zinc-500"> · {{ $group['court'] }}</span>@endif</p>
                            <p class="mt-0.5 text-zinc-700">{{ implode(', ', $group['teams']) }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Mecze: dzień, godzina, boisko, spotkanie --}}
            @if($sheet['days']->isEmpty())
                <p class="mt-6 text-sm text-zinc-500">Ta kategoria nie ma jeszcze meczów.</p>
            @else
                <table class="mt-4 w-full border-collapse text-sm">
                    <thead>
                        <tr class="text-left text-xs tracking-wide text-white uppercase" style="background: {{ $category->color() }}">
                            <th class="w-24 px-2 py-1.5 font-semibold">Dzień</th>
                            <th class="w-16 px-2 py-1.5 font-semibold">Godzina</th>
                            <th class="w-48 px-2 py-1.5 font-semibold">Boisko</th>
                            <th class="px-2 py-1.5 font-semibold">Spotkanie</th>
                        </tr>
                    </thead>
                    @foreach($sheet['days'] as $day => $rows)
                        <tbody class="border-b-2 border-zinc-300">
                            @foreach($rows as $row)
                                <tr class="border-t border-zinc-200 align-top odd:bg-white even:bg-zinc-50">
                                    <td class="px-2 py-1.5 font-semibold">
                                        @if($loop->first)
                                            {{ $day ? \Illuminate\Support\Carbon::parse($day)->translatedFormat('l') : 'Bez terminu' }}
                                            @if($day)<span class="block text-xs font-normal text-zinc-500">{{ \Illuminate\Support\Carbon::parse($day)->format('d.m') }}</span>@endif
                                        @endif
                                    </td>
                                    <td class="px-2 py-1.5 font-semibold tabular-nums">{{ $row['time'] ?? '—' }}</td>
                                    <td class="px-2 py-1.5">
                                        @if($row['court'])
                                            <span class="inline-flex items-center gap-1.5">@if($row['icon'])<x-court-icon :icon="$row['icon']" class="!h-3 !w-5" />@endif{{ $row['court'] }}</span>
                                        @else
                                            <span class="text-zinc-400">do ustalenia</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-1.5">
                                        @if($row['title'])<span class="mr-1 text-xs font-semibold text-zinc-500 uppercase">{{ $row['title'] }}:</span>@elseif($row['group'] && $sheet['groups']->isNotEmpty())<span class="mr-1 text-xs font-semibold text-zinc-500">{{ $row['group'] }}:</span>@endif
                                        {{ $row['home'] }} – {{ $row['away'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    @endforeach
                </table>
            @endif

            {{-- Uwagi do uzupełnienia przed wydrukiem --}}
            <div contenteditable="true" data-placeholder="Uwagi (opcjonalnie) — kliknij, żeby dopisać przed wydrukiem" class="mt-3 rounded-md px-2 py-1 text-sm font-semibold outline-none outline-1 outline-dashed outline-zinc-300 focus:outline-sky-500 print:outline-0 print:empty:hidden"></div>

            {{-- Obiekty: adresy hal i boiska tej kategorii --}}
            @if($sheet['facilities'] !== [])
                <section class="keep mt-5">
                    <h2 class="font-display text-lg font-bold text-zinc-800 uppercase">Hale i adresy</h2>
                    <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                        @foreach($sheet['facilities'] as $facility)
                            <div class="rounded-md border-l-4 bg-zinc-50 px-3 py-2" style="border-color: {{ $facility['color'] ?? '#0ea5e9' }}">
                                <p class="font-semibold">{{ $facility['name'] }}</p>
                                @if($facility['address'])<p class="text-zinc-700">{{ $facility['address'] }}</p>@endif
                                <p class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-zinc-600">
                                    @foreach($facility['used'] as $court)
                                        <span class="inline-flex items-center gap-1"><x-court-icon :icon="$court['icon']" class="!h-3 !w-5" />@if($court['room'] !== '' && $court['label'] !== $court['room']){{ $court['room'] }} · @endif{{ $court['label'] }}</span>
                                    @endforeach
                                </p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <footer class="mt-6 flex items-center justify-between border-t border-zinc-200 pt-2 text-[11px] text-zinc-500">
                <span>{{ $clubName }} · {{ $tournament->fullName() }}</span>
                @if($tournament->has_page)<span>Aktualny harmonogram i wyniki na żywo: {{ route('tournaments.show', [$tournament, $category->slug()]) }}</span>@endif
            </footer>
        </article>
    @endforeach
    </main>
</body>
</html>

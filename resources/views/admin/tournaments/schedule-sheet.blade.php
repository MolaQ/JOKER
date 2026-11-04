<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Harmonogram · {{ $tournament->fullName() }}{{ $chosen ? ' · '.$sheets->first()['category']->name : '' }}</title>
    @vite(['resources/css/app.css', 'resources/js/qr.js'])
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

            @if($sheet['facts']->isNotEmpty() || $sheet['system'])
                <div class="keep mt-4 flex flex-wrap items-stretch gap-2">
                    @if($sheet['system'])
                        <div class="flex flex-col justify-center rounded-lg border-l-[6px] bg-zinc-900 px-4 py-2 text-white" style="border-color: {{ $category->color() }}">
                            <span class="text-[10px] font-semibold tracking-[0.2em] uppercase opacity-90">System</span>
                            <span class="font-display text-lg leading-tight font-bold uppercase">{{ $sheet['system'] }}</span>
                        </div>
                    @endif
                    @foreach($sheet['facts'] as [$number, $label])
                        <div class="flex items-baseline gap-1.5 rounded-lg border-b-4 bg-zinc-50 px-4 py-2 ring-1 ring-zinc-200" style="border-bottom-color: {{ $category->color() }}">
                            <span class="font-display text-3xl leading-none font-bold text-zinc-900">{{ $number }}</span>
                            <span class="text-sm font-semibold text-zinc-700">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
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
                            <th class="hidden w-24 px-2 py-1.5 font-semibold sm:table-cell print:table-cell">Dzień</th>
                            <th class="w-14 px-2 py-1.5 font-semibold sm:w-16"><span class="sm:hidden print:hidden">Godz.</span><span class="hidden sm:inline print:inline">Godzina</span></th>
                            <th class="px-2 py-1.5 font-semibold sm:w-48 print:w-48">Boisko</th>
                            <th class="px-2 py-1.5 font-semibold">Spotkanie</th>
                        </tr>
                    </thead>
                    @foreach($sheet['days'] as $day => $rows)
                        <tbody class="border-b-2 border-zinc-300">
                            {{-- Na telefonie dzień jest nagłówkiem nad meczami zamiast osobnej kolumny --}}
                            <tr class="bg-zinc-100 sm:hidden print:hidden">
                                <td colspan="3" class="px-2 py-1.5 text-xs font-bold tracking-wide text-zinc-700 uppercase">
                                    {{ $day ? \Illuminate\Support\Carbon::parse($day)->translatedFormat('l, d.m') : 'Bez terminu' }}
                                </td>
                            </tr>
                            @foreach($rows as $row)
                                <tr class="border-t border-zinc-200 align-top odd:bg-white even:bg-zinc-50">
                                    <td class="hidden px-2 py-1.5 font-semibold sm:table-cell print:table-cell">
                                        @if($loop->first)
                                            {{ $day ? \Illuminate\Support\Carbon::parse($day)->translatedFormat('l') : 'Bez terminu' }}
                                            @if($day)<span class="block text-xs font-normal text-zinc-500">{{ \Illuminate\Support\Carbon::parse($day)->format('d.m') }}</span>@endif
                                        @endif
                                    </td>
                                    <td class="px-2 py-1.5 font-semibold tabular-nums">{{ $row['time'] ?? '—' }}</td>
                                    <td class="px-2 py-1.5">
                                        @if($row['court'])
                                            {{-- Na telefonie sama nazwa boiska (np. „Sektor II”), pełna z halą od tabletu i na wydruku --}}
                                            <span class="inline-flex items-center gap-1.5 whitespace-nowrap sm:whitespace-normal">@if($row['icon'])<x-court-icon :icon="$row['icon']" class="!h-3 !w-5 shrink-0" />@endif<span class="sm:hidden print:hidden">{{ \Illuminate\Support\Str::afterLast($row['court'], ' · ') }}</span><span class="hidden sm:inline print:inline">{{ $row['court'] }}</span></span>
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
                                @php $title = \App\Support\TournamentHalls::legendTitle($facility); @endphp
                                <p class="font-semibold">{{ $title }}@if($title !== $facility['name'])<span class="font-normal text-zinc-500"> ({{ $facility['name'] }})</span>@endif</p>
                                @if($facility['address'])<p class="text-zinc-700">{{ $facility['address'] }}</p>@endif
                                <p class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-zinc-600">
                                    @foreach($facility['used'] as $court)
                                        <span class="inline-flex items-center gap-1"><x-court-icon :icon="$court['icon']" class="!h-3 !w-5" />@if($court['room'] !== '' && $court['room'] !== $title && $court['label'] !== $court['room']){{ $court['room'] }} · @endif@if($court['label'] !== $title){{ $court['label'] }}@endif</span>
                                    @endforeach
                                </p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Stopka: kod QR do strony kategorii z wynikami na żywo --}}
            @php $pageUrl = $tournament->has_page ? route('tournaments.show', [$tournament, $category->slug()]) : null; @endphp
            <footer class="keep mt-6 flex items-center gap-4 border-t-2 pt-3" style="border-color: {{ $category->color() }}">
                @if($pageUrl)
                    <div data-qr="{{ $pageUrl }}" class="size-[24mm] shrink-0 [&>svg]:h-full [&>svg]:w-full"></div>
                    <div class="min-w-0 flex-1">
                        <p class="font-display text-lg leading-tight font-bold text-zinc-900 uppercase">Wyniki na żywo i aktualny harmonogram</p>
                        <p class="mt-0.5 text-sm text-zinc-600">Zeskanuj kod aparatem telefonu albo wpisz adres:</p>
                        <p class="text-xs font-semibold break-all text-zinc-800">{{ preg_replace('#^https?://(www\.)?#', '', $pageUrl) }}</p>
                    </div>
                @else
                    <div class="flex-1"></div>
                @endif
                <div class="flex shrink-0 items-center gap-2 text-right text-[11px] leading-tight text-zinc-500">
                    <span>{{ $clubName }}<br>{{ $tournament->fullName() }}</span>
                    <img src="{{ $logo }}" alt="" class="size-9 object-contain">
                </div>
            </footer>
        </article>
    @endforeach
    </main>
</body>
</html>

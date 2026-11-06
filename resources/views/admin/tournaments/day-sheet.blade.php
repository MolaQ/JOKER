<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Mecze dnia · {{ $tournament->fullName() }}{{ $dayLabel ? ' · '.$dayLabel : '' }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4; margin: 12mm 12mm 14mm; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tr, .keep { break-inside: avoid; }
            thead { display: table-header-group; }
        }
    </style>
</head>
<body class="bg-zinc-100 font-sans text-zinc-900 print:bg-white">
    {{-- Pasek narzędzi (nie drukuje się): wybór dnia i drukowanie / zapis do PDF --}}
    <div class="sticky top-0 z-10 border-b border-zinc-200 bg-white/95 px-4 py-3 shadow-sm backdrop-blur print:hidden">
        <div class="mx-auto flex max-w-[210mm] flex-wrap items-center gap-2 text-sm">
            <a href="{{ route('admin.tournaments.edit', $tournament) }}" class="mr-2 text-sky-700 hover:underline">← Panel turnieju</a>
            @foreach($days as $value)
                <a href="{{ route('admin.tournaments.day-sheet', [$tournament, 'day' => $value]) }}" @class(['rounded-full px-3 py-1 font-semibold ring-1', 'bg-sky-700 text-white ring-sky-700' => $value === $day, 'ring-zinc-300 hover:bg-zinc-50' => $value !== $day])>{{ \Illuminate\Support\Carbon::parse($value)->translatedFormat('D d.m') }}</a>
            @endforeach
            <button type="button" onclick="window.print()" class="ml-auto rounded-md bg-emerald-600 px-4 py-1.5 font-semibold text-white hover:bg-emerald-700">Drukuj / zapisz PDF</button>
        </div>
    </div>

    <main class="mx-auto my-6 max-w-[210mm] bg-white px-2 py-6 shadow-lg sm:px-[12mm] sm:py-[10mm] print:m-0 print:max-w-none print:p-0 print:shadow-none">
        <header class="keep flex items-center gap-4 border-b-4 border-zinc-900 pb-3">
            <img src="{{ $logo }}" alt="{{ $clubName }}" class="h-14 w-14 shrink-0 object-contain">
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold tracking-[0.2em] text-zinc-500 uppercase">{{ $tournament->fullName() }}</p>
                <h1 class="font-display text-3xl leading-none font-bold uppercase">Mecze dnia</h1>
                @if($dayLabel)<p class="mt-1 font-semibold text-zinc-700">{{ $dayLabel }} · {{ $rows->count() }} {{ $rows->count() === 1 ? 'mecz' : ($rows->count() % 10 >= 2 && $rows->count() % 10 <= 4 && ($rows->count() % 100 < 10 || $rows->count() % 100 >= 20) ? 'mecze' : 'meczów') }}</p>@endif
            </div>
        </header>

        @if($categories->count() > 1)
            <p class="keep mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs font-semibold">
                @foreach($categories as $category)
                    <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded-sm" style="background: {{ $category->color() }}"></span>{{ $category->name }}</span>
                @endforeach
            </p>
        @endif

        @if($rows->isEmpty())
            <p class="mt-6 text-sm text-zinc-500">Turniej nie ma jeszcze meczów z godziną.</p>
        @else
            <table class="mt-3 w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-zinc-900 text-left text-xs tracking-wide text-white uppercase">
                        <th class="w-12 px-2 py-1.5 font-semibold">Godz.</th>
                        <th class="hidden px-2 py-1.5 font-semibold sm:table-cell print:table-cell">Boisko</th>
                        <th class="px-2 py-1.5 font-semibold">Kategoria</th>
                        <th class="px-2 py-1.5 font-semibold">Spotkanie</th>
                        <th class="px-2 py-1.5 text-right font-semibold">Wynik</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        {{-- Nowa godzina zaczyna się grubszą linią, żeby łatwo było znaleźć turę meczów. --}}
                        <tr @class(['align-top odd:bg-white even:bg-zinc-50', 'border-t-2 border-zinc-400' => ! $loop->first && $rows[$loop->index - 1]['time'] !== $row['time'], 'border-t border-zinc-200' => $loop->first || $rows[$loop->index - 1]['time'] === $row['time']])>
                            <td class="px-2 py-1.5 font-semibold tabular-nums">
                                {{ $row['time'] }}
                                {{-- Na telefonie boisko pod godziną zamiast osobnej kolumny --}}
                                @if($row['court'])<span class="block text-xs font-normal whitespace-nowrap text-zinc-500 sm:hidden print:hidden">{{ \Illuminate\Support\Str::afterLast($row['court'], ' · ') }}</span>@endif
                            </td>
                            <td class="hidden px-2 py-1.5 sm:table-cell print:table-cell">
                                @if($row['court'])
                                    <span class="inline-flex items-center gap-1.5">@if($row['icon'])<x-court-icon :icon="$row['icon']" class="!h-3 !w-5 shrink-0" />@endif<span>{{ \Illuminate\Support\Str::afterLast($row['court'], ' · ') }}</span></span>
                                @else
                                    <span class="text-zinc-400">do ustalenia</span>
                                @endif
                            </td>
                            <td class="px-2 py-1.5">
                                <span class="inline-flex items-center gap-1.5 font-semibold sm:whitespace-nowrap"><span class="size-2.5 shrink-0 rounded-sm" style="background: {{ $row['category']->color() }}"></span>{{ $row['category']->name }}</span>
                                @if($row['number'])<span class="block text-xs whitespace-nowrap text-zinc-500">mecz nr {{ $row['number'] }}</span>@endif
                            </td>
                            <td class="px-2 py-1.5">
                                @if($row['phase'])<span class="mr-1 text-xs font-semibold text-zinc-500">{{ $row['phase'] }}:</span>@endif
                                {{ $row['home'] }} – {{ $row['away'] }}
                            </td>
                            <td class="px-2 py-1.5 text-right whitespace-nowrap tabular-nums">
                                @if($row['result'])
                                    <span class="font-bold">{{ $row['result'] }}</span>
                                    @if($row['sets'])<span class="block text-[11px] whitespace-normal text-zinc-500 sm:whitespace-nowrap print:whitespace-nowrap">{{ $row['sets'] }}</span>@endif
                                @elseif($row['live'])
                                    <span class="text-xs font-bold text-red-600 uppercase">trwa</span>
                                @else
                                    <span class="inline-block h-5 w-14 border-b border-zinc-300"></span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </main>
</body>
</html>

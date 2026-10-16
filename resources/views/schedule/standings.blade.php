@extends('layouts.public')

@section('title', 'Tabela Ligowa - Joker Piła')

@section('content')
<section class="container-site pt-14 pb-6 lg:pt-20">
    <p class="eyebrow">Aktualna tabela {{ $currentSeason?->name ?? 'sezonu' }}</p>
    <h1 class="heading-xl mt-4">Tabela Ligowa</h1>
</section>

<!-- Navigation Tabs -->
<div class="bg-white border-b sticky top-16 z-40 lg:top-20 shadow-sm">
    <div class="container-site">
        <nav class="flex space-x-8">
            <a href="{{ route('schedule.index') }}" class="py-4 px-2 border-b-2 border-transparent hover:border-zinc-300 text-zinc-600 hover:text-zinc-900">
                Terminarz
            </a>
            <a href="{{ route('standings') }}" class="py-4 px-2 border-b-2 border-sky-club font-semibold text-sky-club-dark">
                Tabela
            </a>
        </nav>
    </div>
</div>

<!-- League Table -->
<section class="container-site py-12">
    @if(isset($competitions) && $competitions->isNotEmpty())
        <div class="mb-6 rounded-xl bg-white p-4 shadow">
            <form method="GET" action="{{ route('standings') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <label for="competition" class="text-sm font-semibold text-zinc-700">Rozgrywki:</label>
                <select id="competition" name="competition" class="rounded-md border-zinc-300 text-sm">
                    @foreach($competitions as $competition)
                        <option value="{{ $competition->id }}" @selected((int)$selectedCompetitionId === (int)$competition->id)>
                            {{ $competition->name }} ({{ $competition->level }})
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-md bg-ink px-4 py-2 text-sm font-semibold text-white">Pokaż tabelę</button>
            </form>
            @if(isset($selectedCompetition) && $selectedCompetition)
                <p class="mt-3 text-sm text-sky-club-dark font-medium">Wyświetlana tabela: {{ $selectedCompetition->name }}</p>
            @endif
        </div>
    @endif

    @if($standings->count() > 0)
        <!-- Desktop Table -->
        <div class="bg-white rounded-3xl ring-1 ring-zinc-200/70 overflow-hidden hidden md:block">
            <table class="w-full">
                <thead class="bg-zinc-50 border-b-2 border-zinc-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-zinc-700 uppercase tracking-wider">
                            Poz
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-zinc-700 uppercase tracking-wider">
                            Drużyna
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-zinc-700 uppercase tracking-wider">
                            M
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-zinc-700 uppercase tracking-wider">
                            W
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-zinc-700 uppercase tracking-wider">
                            P
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-zinc-700 uppercase tracking-wider">
                            Sety
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-zinc-700 uppercase tracking-wider">
                            Małe pkt
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-zinc-700 uppercase tracking-wider">
                            Punkty
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200">
                    @foreach($standings as $standing)
                        <tr class="hover:bg-zinc-50 transition {{ $standing->isOwnTeam() ? 'bg-sky-club/10' : '' }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-ink text-white rounded-full flex items-center justify-center font-bold text-sm mr-3">
                                        {{ $standing->position }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    @if($standing->competitorLogoPath())
                                        <img src="{{ Storage::url($standing->competitorLogoPath()) }}" class="h-6 w-6 object-cover rounded-full" alt="">
                                    @endif
                                    @if($standing->isOwnTeam() && $standing->competitor)
                                        <a href="{{ route('teams.show', $standing->competitor) }}" class="font-semibold text-zinc-900 hover:text-sky-club-dark transition">
                                            {{ $standing->competitorName() }}
                                        </a>
                                    @else
                                        <span class="font-semibold text-zinc-900">{{ $standing->competitorName() }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-zinc-600">
                                {{ $standing->played }}
                            </td>
                            <td class="px-6 py-4 text-center font-semibold text-green-600">
                                {{ $standing->won }}
                            </td>
                            <td class="px-6 py-4 text-center font-semibold text-red-600">
                                {{ $standing->lost }}
                            </td>
                            <td class="px-6 py-4 text-center text-zinc-600">
                                {{ $standing->sets_won }}:{{ $standing->sets_lost }}
                            </td>
                            <td class="px-6 py-4 text-center text-zinc-600">
                                {{ $standing->points_for }}:{{ $standing->points_against }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-xl font-bold text-sky-club-dark">
                                    {{ $standing->points }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="md:hidden space-y-4">
            @foreach($standings as $standing)
                <div class="bg-white rounded-3xl ring-1 ring-zinc-200/70 p-6 {{ $standing->isOwnTeam() ? 'ring-2 ring-sky-club' : '' }}">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-ink text-white rounded-full flex items-center justify-center font-bold">
                                {{ $standing->position }}
                            </div>
                            @if($standing->isOwnTeam() && $standing->competitor)
                                <a href="{{ route('teams.show', $standing->competitor) }}" class="font-bold text-lg text-zinc-900">
                                    {{ $standing->competitorName() }}
                                </a>
                            @else
                                <span class="font-bold text-lg text-zinc-900">{{ $standing->competitorName() }}</span>
                            @endif
                        </div>
                        <div class="text-2xl font-bold text-sky-club-dark">
                            {{ $standing->points }}
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 text-center text-sm">
                        <div>
                            <div class="text-zinc-500">Mecze</div>
                            <div class="font-semibold text-zinc-900">{{ $standing->played }}</div>
                        </div>
                        <div>
                            <div class="text-zinc-500">W/P</div>
                            <div class="font-semibold">
                                <span class="text-green-600">{{ $standing->won }}</span>/<span class="text-red-600">{{ $standing->lost }}</span>
                            </div>
                        </div>
                        <div>
                            <div class="text-zinc-500">Sety</div>
                            <div class="font-semibold text-zinc-900">{{ $standing->sets_won }}:{{ $standing->sets_lost }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Legend -->
        <div class="mt-8 bg-sky-club/10 rounded-lg p-6">
            <h3 class="font-bold text-zinc-900 mb-3">Legenda:</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-zinc-700">
                <div><strong>M</strong> - Mecze rozegrane</div>
                <div><strong>W</strong> - Wygrane</div>
                <div><strong>P</strong> - Przegrane</div>
                <div><strong>Sety</strong> - Bilans setów</div>
                <div><strong>Małe pkt</strong> - Bilans punktów</div>
                <div><strong>Punkty</strong> - Punkty ligowe</div>
            </div>
        </div>
    @else
        <div class="bg-zinc-100 rounded-lg p-12 text-center">
            <p class="text-zinc-600 text-lg">Brak danych w tabeli dla aktualnego sezonu.</p>
        </div>
    @endif
</section>
@endsection

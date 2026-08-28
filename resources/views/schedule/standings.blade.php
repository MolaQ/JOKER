@extends('layouts.public')

@section('title', 'Tabela Ligowa - Joker Piła')

@section('content')
<!-- Page Header -->
<div class="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white py-16">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">Tabela Ligowa</h1>
        <p class="text-xl text-blue-100">
            Aktualna tabela {{ $currentSeason?->name ?? 'sezonu' }}
        </p>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="bg-white border-b sticky top-0 z-10 shadow-sm">
    <div class="container mx-auto px-4">
        <nav class="flex space-x-8">
            <a href="{{ route('schedule.index') }}" class="py-4 px-2 border-b-2 border-transparent hover:border-gray-300 text-gray-600 hover:text-gray-900">
                Terminarz
            </a>
            <a href="{{ route('standings') }}" class="py-4 px-2 border-b-2 border-blue-900 font-semibold text-blue-900">
                Tabela
            </a>
        </nav>
    </div>
</div>

<!-- League Table -->
<section class="container mx-auto px-4 py-12">
    @if(isset($competitions) && $competitions->isNotEmpty())
        <div class="mb-6 rounded-xl bg-white p-4 shadow">
            <form method="GET" action="{{ route('standings') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <label for="competition" class="text-sm font-semibold text-gray-700">Rozgrywki:</label>
                <select id="competition" name="competition" class="rounded-md border-gray-300 text-sm">
                    @foreach($competitions as $competition)
                        <option value="{{ $competition->id }}" @selected((int)$selectedCompetitionId === (int)$competition->id)>
                            {{ $competition->name }} ({{ $competition->level }})
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-md bg-blue-900 px-4 py-2 text-sm font-semibold text-white">Pokaż tabelę</button>
            </form>
            @if(isset($selectedCompetition) && $selectedCompetition)
                <p class="mt-3 text-sm text-blue-700 font-medium">Wyświetlana tabela: {{ $selectedCompetition->name }}</p>
            @endif
        </div>
    @endif

    @if($standings->count() > 0)
        <!-- Desktop Table -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden hidden md:block">
            <table class="w-full">
                <thead class="bg-gray-50 border-b-2 border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                            Poz
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                            Drużyna
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                            M
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                            W
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                            P
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                            Sety
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                            Małe pkt
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                            Punkty
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($standings as $standing)
                        <tr class="hover:bg-gray-50 transition {{ $standing->isOwnTeam() ? 'bg-blue-50' : '' }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-blue-900 text-white rounded-full flex items-center justify-center font-bold text-sm mr-3">
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
                                        <a href="{{ route('teams.show', $standing->competitor) }}" class="font-semibold text-gray-900 hover:text-blue-900 transition">
                                            {{ $standing->competitorName() }}
                                        </a>
                                    @else
                                        <span class="font-semibold text-gray-900">{{ $standing->competitorName() }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-gray-600">
                                {{ $standing->played }}
                            </td>
                            <td class="px-6 py-4 text-center font-semibold text-green-600">
                                {{ $standing->won }}
                            </td>
                            <td class="px-6 py-4 text-center font-semibold text-red-600">
                                {{ $standing->lost }}
                            </td>
                            <td class="px-6 py-4 text-center text-gray-600">
                                {{ $standing->sets_won }}:{{ $standing->sets_lost }}
                            </td>
                            <td class="px-6 py-4 text-center text-gray-600">
                                {{ $standing->points_for }}:{{ $standing->points_against }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-xl font-bold text-blue-900">
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
                <div class="bg-white rounded-lg shadow-md p-6 {{ $standing->isOwnTeam() ? 'ring-2 ring-blue-900' : '' }}">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-900 text-white rounded-full flex items-center justify-center font-bold">
                                {{ $standing->position }}
                            </div>
                            @if($standing->isOwnTeam() && $standing->competitor)
                                <a href="{{ route('teams.show', $standing->competitor) }}" class="font-bold text-lg text-gray-900">
                                    {{ $standing->competitorName() }}
                                </a>
                            @else
                                <span class="font-bold text-lg text-gray-900">{{ $standing->competitorName() }}</span>
                            @endif
                        </div>
                        <div class="text-2xl font-bold text-blue-900">
                            {{ $standing->points }}
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 text-center text-sm">
                        <div>
                            <div class="text-gray-500">Mecze</div>
                            <div class="font-semibold text-gray-900">{{ $standing->played }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">W/P</div>
                            <div class="font-semibold">
                                <span class="text-green-600">{{ $standing->won }}</span>/<span class="text-red-600">{{ $standing->lost }}</span>
                            </div>
                        </div>
                        <div>
                            <div class="text-gray-500">Sety</div>
                            <div class="font-semibold text-gray-900">{{ $standing->sets_won }}:{{ $standing->sets_lost }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Legend -->
        <div class="mt-8 bg-blue-50 rounded-lg p-6">
            <h3 class="font-bold text-gray-900 mb-3">Legenda:</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-gray-700">
                <div><strong>M</strong> - Mecze rozegrane</div>
                <div><strong>W</strong> - Wygrane</div>
                <div><strong>P</strong> - Przegrane</div>
                <div><strong>Sety</strong> - Bilans setów</div>
                <div><strong>Małe pkt</strong> - Bilans punktów</div>
                <div><strong>Punkty</strong> - Punkty ligowe</div>
            </div>
        </div>
    @else
        <div class="bg-gray-100 rounded-lg p-12 text-center">
            <p class="text-gray-600 text-lg">Brak danych w tabeli dla aktualnego sezonu.</p>
        </div>
    @endif
</section>
@endsection

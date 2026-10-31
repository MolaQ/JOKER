<div class="space-y-4">
    <div class="rounded-lg bg-sky-50 p-4 text-sm text-sky-900">
        <p class="font-semibold">Kto wpisuje wyniki na hali (sędzia stolikowy, sekretarz)</p>
        <p class="mt-1">Wybrane osoby od <strong>{{ $from->format('d.m.Y') }}</strong> do <strong>{{ $until->format('d.m.Y') }}</strong> widzą na stronie turnieju przycisk „Wpisz wyniki”. Na prostym ekranie na telefonie wpisują godzinę rozpoczęcia meczu (domyślnie z harmonogramu) i sety. Nie zmienią drużyn, grup, meczów, godzin w harmonogramie ani zasad. Każdy wpis jest w Dzienniku zmian z przyciskiem „Cofnij”.</p>
        <p class="mt-2">Adres ekranu: <a href="{{ route('tournaments.score', $tournament) }}" target="_blank" class="font-semibold underline">{{ route('tournaments.score', $tournament) }}</a></p>
    </div>

    <div class="max-w-xl">
        <span class="block text-sm font-medium text-gray-700 mb-1">Dodaj osobę</span>
        <x-search-select model="userId" empty="— wybierz konto —" placeholder="Szukaj po imieniu albo e-mailu…" :options="$options" />
    </div>

    <ul class="divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white">
        @forelse($scorers as $scorer)
            <li wire:key="scorer-{{ $scorer->id }}" class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
                <span><span class="font-medium text-gray-900">{{ $scorer->name }}</span> <span class="text-gray-500">{{ $scorer->email }}</span></span>
                <button type="button" wire:click="remove({{ $scorer->id }})" wire:confirm="Odebrać {{ $scorer->name }} wpisywanie wyników?" class="text-xs font-semibold text-red-700 hover:text-red-900">Odbierz</button>
            </li>
        @empty
            <li class="px-4 py-6 text-center text-sm text-gray-500">Nikt jeszcze nie wpisuje wyników. Osoby z uprawnieniem „Turnieje” mogą to robić zawsze.</li>
        @endforelse
    </ul>
</div>

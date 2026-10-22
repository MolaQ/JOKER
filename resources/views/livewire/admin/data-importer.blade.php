{{-- Import CSV: rodzaj danych, wzór pliku, wgranie lub wklejenie, podgląd z błędami i zapis poprawnych wierszy --}}
<div class="space-y-6 bg-white p-4 sm:p-6">
    <div class="flex flex-wrap gap-2">
        @foreach(\App\Support\DataImport::TYPES as $key => $label)
            <button type="button" wire:click="$set('type', '{{ $key }}')" @class(['rounded-full px-4 py-1.5 text-sm font-semibold', 'bg-sky-600 text-white' => $type === $key, 'bg-gray-100 text-gray-700 hover:bg-gray-200' => $type !== $key])>{{ $loop->iteration }}. {{ $label }}</button>
        @endforeach
    </div>

    <p class="text-xs text-gray-500">Rodzaj danych rozpoznajemy po nagłówkach pliku, więc po wgraniu zakładka przełączy się sama.</p>

    <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-700">
        <p class="font-semibold text-gray-900">Kolumny pliku (pierwszy wiersz to nagłówki, kolejność dowolna)</p>
        <ul class="mt-2 flex flex-wrap gap-2">
            @foreach($columns as [$header, $required, $example])
                <li class="rounded-md bg-white px-2 py-1 ring-1 ring-gray-200"><span class="font-mono">{{ $header }}</span>@if($required)<span class="text-red-600">*</span>@endif <span class="text-gray-400">np. {{ $example }}</span></li>
            @endforeach
        </ul>
        <p class="mt-2 text-xs text-gray-500">
            @if($type === 'teams') Kategoria: seniorzy, juniorzy, juniorzy młodsi albo młodzicy.
            @elseif($type === 'staff') Rola: trener albo sztab. Funkcja „trener główny” ustawia trenera drużyny, inna (np. „asystent trenera”) dopisuje do sztabu. Kilka drużyn rozdziel przecinkiem. Bez e-maila osoba dostaje adres zastępczy.
            @else Wystarczą imię i nazwisko, resztę uzupełnisz później. Pozycja: rozgrywający, przyjmujący, atakujący, środkowy albo libero. Kapitan: tak albo nie. Data urodzenia: 2010-05-14 albo 14.05.2010.
                E-mail zawodnika zakłada mu konto (dostanie link do ustawienia hasła). Rodzice: kilka adresów po przecinku, np. „Anna Nowak &lt;anna@example.com&gt;, piotr@example.com”; brakujące konta rodziców też zostaną założone.
            @endif
            Gwiazdka oznacza pole wymagane.
        </p>
        <a href="{{ route('admin.import.template', $type) }}" class="mt-3 inline-block font-semibold text-sky-700 hover:text-sky-900">Pobierz wzór pliku ({{ \App\Support\DataImport::TYPES[$type] }}) ↓</a>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700">Plik CSV</label>
            <input type="file" wire:model="file" accept=".csv,.txt" class="mt-1 block text-sm">
            @error('file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">albo wklej tekst (np. skopiowany z Excela)</label>
            <textarea wire:model="text" rows="4" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs shadow-sm"></textarea>
            <button type="button" wire:click="preview" class="mt-2 rounded-md border border-gray-300 px-3 py-1.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">Pokaż podgląd</button>
        </div>
    </div>

    @if($rows)
        <div>
            <div class="mb-2 flex flex-wrap items-center gap-3">
                <p class="text-sm text-gray-700">Wierszy: {{ count($rows) }}, poprawnych: <strong>{{ $valid }}</strong>@if($valid < count($rows)), z błędami: <strong class="text-red-600">{{ count($rows) - $valid }}</strong> (zostaną pominięte)@endif</p>
                @if($valid)
                    <button type="button" wire:click="import" wire:confirm="Zaimportować {{ $valid }} {{ $valid === 1 ? 'wiersz' : 'wierszy' }}?" class="ml-auto rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Importuj {{ $valid }}</button>
                @endif
            </div>
            <div class="overflow-x-auto rounded-lg ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase">
                        <tr>
                            <th class="px-3 py-2">Wiersz</th>
                            <th class="px-3 py-2">Wynik</th>
                            @foreach($columns as [$header])<th class="px-3 py-2">{{ $header }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($rows as $row)
                            <tr @class(['bg-red-50' => $row['errors']]) wire:key="row-{{ $row['line'] }}">
                                <td class="px-3 py-2 text-gray-400">{{ $row['line'] }}</td>
                                <td class="min-w-56 px-3 py-2">
                                    @if($row['errors'])
                                        <span class="text-red-700">{{ implode('; ', $row['errors']) }}</span>
                                    @else
                                        <span @class(['rounded-full px-2 py-0.5 text-xs font-semibold', 'bg-green-100 text-green-800' => $row['action'] === 'nowy', 'bg-amber-100 text-amber-800' => $row['action'] !== 'nowy'])>{{ $row['action'] }}</span>
                                    @endif
                                </td>
                                @foreach(array_keys($columns) as $key)<td class="px-3 py-2 text-gray-800">{{ $row['data'][$key] }}</td>@endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

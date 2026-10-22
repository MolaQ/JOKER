{{-- Kryteria kolejności w tabeli: włączone w ustalonej kolejności (strzałki), pozostałe do dodania. --}}
@php $active = array_values($criteria ?? []); @endphp
<div>
    <p class="text-xs font-semibold text-gray-500 uppercase">Kolejność w tabeli przy równych punktach</p>
    <ol class="mt-1 space-y-1">
        @foreach($active as $index => $criterion)
            <li class="flex items-center gap-2 rounded-md bg-gray-50 px-2 py-1 text-sm text-gray-800" wire:key="crit-{{ $target }}-{{ $criterion }}">
                <span class="w-5 text-xs font-bold text-gray-400">{{ $index + 1 }}.</span>
                <span class="flex-1">{{ \App\Support\TournamentTable::CRITERIA[$criterion] }}</span>
                <button type="button" wire:click="moveCriterion('{{ $target }}', '{{ $criterion }}', -1)" @disabled($loop->first) class="px-1 text-gray-500 hover:text-gray-900 disabled:opacity-30" title="W górę">↑</button>
                <button type="button" wire:click="moveCriterion('{{ $target }}', '{{ $criterion }}', 1)" @disabled($loop->last) class="px-1 text-gray-500 hover:text-gray-900 disabled:opacity-30" title="W dół">↓</button>
                <button type="button" wire:click="toggleCriterion('{{ $target }}', '{{ $criterion }}')" class="px-1 text-red-500 hover:text-red-700" title="Wyłącz">✕</button>
            </li>
        @endforeach
    </ol>
    <div class="mt-1 flex flex-wrap gap-2">
        @foreach(array_diff(array_keys(\App\Support\TournamentTable::CRITERIA), $active) as $criterion)
            <button type="button" wire:click="toggleCriterion('{{ $target }}', '{{ $criterion }}')" class="rounded-full border border-dashed border-gray-300 px-2 py-0.5 text-xs text-gray-600 hover:border-blue-400 hover:text-blue-700">+ {{ \App\Support\TournamentTable::CRITERIA[$criterion] }}</button>
        @endforeach
    </div>
</div>

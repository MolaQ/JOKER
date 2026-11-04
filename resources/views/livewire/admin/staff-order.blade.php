<div class="space-y-4 p-4 sm:p-6">
    <p class="text-sm text-zinc-600">
        Kolejność ustawiasz strzałkami, tak samo pokażą się kafelki na stronie <a href="{{ route('trainers.index') }}" target="_blank" class="font-semibold text-sky-700 underline">Sztab ↗</a>.
        Na liście są konta z rolą Trener, Członek sztabu albo Trener mini oraz osoby przypisane do drużyny lub grupy mini. Opis, zdjęcie i media społecznościowe zmieniasz w „Użytkownicy”.
    </p>

    <ol class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 bg-white">
        @forelse ($entries as $entry)
            @php
                $user = $entry['user'];
            @endphp
            <li wire:key="sztab-{{ $user->id }}" @class(['grid gap-3 px-3 py-3 sm:grid-cols-[auto_minmax(0,1fr)_minmax(0,16rem)] sm:items-center', 'opacity-60' => $user->staff_hidden])>
                <div class="flex items-center gap-1">
                    <span class="w-6 text-right text-sm font-semibold tabular-nums text-zinc-400">{{ $loop->iteration }}.</span>
                    <button type="button" wire:click="move({{ $user->id }}, -1)" @disabled($loop->first) class="rounded-md px-2 py-1 text-zinc-600 hover:bg-zinc-100 disabled:opacity-30" aria-label="Wyżej: {{ $user->name }}">↑</button>
                    <button type="button" wire:click="move({{ $user->id }}, 1)" @disabled($loop->last) class="rounded-md px-2 py-1 text-zinc-600 hover:bg-zinc-100 disabled:opacity-30" aria-label="Niżej: {{ $user->name }}">↓</button>
                    <x-avatar :photo="$user->avatar_path" :name="$user->name" class="ml-1 aspect-[4/5] w-10 shrink-0 rounded-md text-sm" />
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-zinc-900">{{ $user->name }}
                        @foreach ($entry['groups'] as $group)
                            <span class="ml-1 inline-flex rounded-md bg-zinc-100 px-1.5 py-0.5 text-xs font-medium text-zinc-600">{{ $groupLabels[$group] }}</span>
                        @endforeach
                    </p>
                    <p class="truncate text-sm text-zinc-600">{{ $entry['teams']->map(fn ($team) => $team['role'].': '.$team['name'])->implode(' · ') ?: 'Brak przypisanych drużyn' }}</p>
                    @if ($miniGroups->isNotEmpty() && (in_array('mini', $entry['groups'], true) || $user->hasRole(\App\UserRole::MiniTrainer)))
                        <fieldset class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                            <legend class="sr-only">Grupy minisiatkówki: {{ $user->name }}</legend>
                            @foreach ($miniGroups as $miniGroup)
                                <label class="inline-flex items-center gap-1.5"><input type="checkbox" wire:model.live="miniGroupIds.{{ $user->id }}" value="{{ $miniGroup->id }}" class="rounded border-zinc-300"> {{ $miniGroup->name }}</label>
                            @endforeach
                        </fieldset>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <input type="text" wire:model.blur="functions.{{ $user->id }}" placeholder="{{ $entry['function'] }}" aria-label="Funkcja na stronie: {{ $user->name }}" maxlength="120" class="block w-full min-w-0 rounded-lg border border-zinc-300 text-sm">
                    <button type="button" wire:click="toggleHidden({{ $user->id }})" class="shrink-0 rounded-md px-2 py-1 text-xs font-semibold {{ $user->staff_hidden ? 'bg-amber-50 text-amber-800' : 'text-zinc-600 hover:bg-zinc-100' }}">{{ $user->staff_hidden ? 'Ukryty' : 'Ukryj' }}</button>
                </div>
            </li>
        @empty
            <li class="px-4 py-6 text-center text-sm text-zinc-500">Nikt nie ma jeszcze roli trenera ani członka sztabu.</li>
        @endforelse
    </ol>
    <p class="text-xs text-zinc-500">Pole funkcji jest opcjonalne. Puste pokazuje funkcję z drużyny (szary podpowiedziany tekst), np. „Trener”, „Fizjoterapeuta”.</p>
</div>

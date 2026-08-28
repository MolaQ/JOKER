<div>
    @if($flashMessage)
        <div class="p-4 {{ $flashType === 'error' ? 'bg-red-50 text-red-800' : 'bg-green-50 text-green-800' }} text-sm font-medium">
            {{ $flashMessage }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 bg-white border-b border-gray-200">
        <div class="w-full sm:w-80">
            <select wire:model.live="competitionFilter" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="">Wszystkie rozgrywki</option>
                @foreach($competitions as $competition)
                    <option value="{{ $competition->id }}">{{ $competition->name }} ({{ $competition->season?->name }})</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="button" wire:click="recalculate" wire:confirm="Przeliczyć tabelę na podstawie zakończonych meczów? Wpisy z ręczną korektą nie zostaną zmienione." class="inline-flex items-center justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                <span wire:loading.remove wire:target="recalculate">↻ Przelicz tabelę</span>
                <span wire:loading wire:target="recalculate">Przeliczanie...</span>
            </button>
            <button type="button" wire:click="openCreateModal" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                + Dodaj wpis do tabeli
            </button>
        </div>
    </div>

    <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
            <tr>
                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">#</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Drużyna</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Rozgrywki</th>
                <th class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">M</th>
                <th class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">W-P</th>
                <th class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Sety</th>
                <th class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Pkt</th>
                <th class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Korekta</th>
                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Akcje</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
            @forelse($standings as $standing)
                <tr wire:key="standing-{{ $standing->id }}">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-bold text-gray-900 sm:pl-6">{{ $standing->position }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                        <div class="flex items-center gap-2">
                            @if($standing->competitorLogoPath())
                                <img src="{{ Storage::url($standing->competitorLogoPath()) }}" class="h-6 w-6 object-cover rounded" alt="">
                            @endif
                            <span>{{ $standing->competitorName() }}</span>
                            @if(! $standing->isOwnTeam())
                                <span class="inline-flex items-center rounded-md bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-500">rywal</span>
                            @endif
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $standing->competition?->name }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-center">{{ $standing->played }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-center">{{ $standing->won }}-{{ $standing->lost }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-center">{{ $standing->setsRatio() }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-gray-900 text-center">{{ $standing->points }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-center">
                        @if($standing->is_manual_override)
                            <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">ręczna</span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <button type="button" wire:click="openEditModal({{ $standing->id }})" class="text-blue-600 hover:text-blue-900 mr-4">Edytuj</button>
                        <button type="button" wire:click="delete({{ $standing->id }})" wire:confirm="Czy na pewno chcesz usunąć ten wpis?" class="text-red-600 hover:text-red-900">Usuń</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-3 py-4 text-sm text-gray-500 text-center">Brak wpisów w tabeli</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeModal"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg" @click.stop>
                    <form wire:submit.prevent="save">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $editingId ? 'Edytuj wpis w tabeli' : 'Dodaj wpis do tabeli' }}
                            </h3>
                        </div>

                        <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Rozgrywki</label>
                                    <select wire:model="competition_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Wybierz</option>
                                        @foreach($competitions as $competition)
                                            <option value="{{ $competition->id }}">{{ $competition->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('competition_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Typ drużyny</label>
                                    <select wire:model="competitor_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="team">Nasza drużyna</option>
                                        <option value="rival_team">Drużyna rywala</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                @if($competitor_type === 'rival_team')
                                    <label class="block text-sm font-medium text-gray-700">Drużyna rywala</label>
                                    <select wire:model="competitor_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Wybierz</option>
                                        @foreach($rivalTeams as $rivalTeam)
                                            <option value="{{ $rivalTeam->id }}">{{ $rivalTeam->full_name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <label class="block text-sm font-medium text-gray-700">Drużyna</label>
                                    <select wire:model="competitor_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Wybierz</option>
                                        @foreach($teams as $team)
                                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                @error('competitor_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="flex items-center text-sm font-medium text-gray-700">
                                    <input type="checkbox" wire:model="is_manual_override" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
                                    Ręczna korekta (kara, walkower) — nie będzie nadpisywana automatycznym przeliczeniem
                                </label>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Pozycja</label>
                                    <input type="number" wire:model="position" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Mecze</label>
                                    <input type="number" wire:model="played" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Punkty</label>
                                    <input type="number" wire:model="points" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Wygrane</label>
                                    <input type="number" wire:model="won" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Przegrane</label>
                                    <input type="number" wire:model="lost" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Sety wygrane</label>
                                    <input type="number" wire:model="sets_won" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Sety przegrane</label>
                                    <input type="number" wire:model="sets_lost" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Małe punkty zdobyte</label>
                                    <input type="number" wire:model="points_for" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Małe punkty stracone</label>
                                    <input type="number" wire:model="points_against" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
                            <button type="button" wire:click="closeModal" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Anuluj
                            </button>
                            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                                <span wire:loading.remove wire:target="save">Zapisz</span>
                                <span wire:loading wire:target="save">Zapisywanie...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

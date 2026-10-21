<div>
    <div class="flex items-center justify-between gap-4 border-b border-gray-200 bg-white p-4">
        <p class="text-sm text-gray-600">Turnieje pokazują się w kalendarzu meczów. Mecze rozegrane na turnieju dodasz w „Mecze i wyniki” (rodzaj: turniejowy).</p>
        <button type="button" wire:click="openCreateModal" class="inline-flex shrink-0 items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">+ Dodaj turniej</button>
    </div>

    <table class="min-w-full divide-y divide-gray-300 bg-white">
        <thead class="bg-gray-50">
            <tr>
                <th class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-6">Termin</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Turniej</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Nasze drużyny</th>
                <th class="relative py-3.5 pr-4 pl-3 sm:pr-6"><span class="sr-only">Akcje</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($tournaments as $tournament)
                <tr wire:key="tournament-{{ $tournament->id }}">
                    <td class="py-4 pr-3 pl-4 text-sm whitespace-nowrap text-gray-700 sm:pl-6">{{ $tournament->dateRangeLabel() }}.{{ $tournament->start_date->format('Y') }}</td>
                    <td class="px-3 py-4 text-sm">
                        <p class="font-semibold text-gray-900">🏆 {{ $tournament->fullName() }}</p>
                        <p class="text-gray-500">{{ collect([$tournament->venue, $tournament->city])->filter()->implode(', ') }}@if($tournament->is_organizer) · <span class="font-semibold text-blue-700">organizuje Joker</span>@endif @if($tournament->games_count) · {{ $tournament->games_count }} mecz(e)@endif</p>
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-700">
                        @foreach($tournament->teams as $team)
                            <span class="mr-2 inline-block">{{ $team->name }}@if($team->pivot->placement) <strong>– {{ $team->pivot->placement }}</strong>@endif</span>
                        @endforeach
                    </td>
                    <td class="py-4 pr-4 pl-3 text-right text-sm font-medium whitespace-nowrap sm:pr-6">
                        <a href="{{ route('admin.tournaments.edit', $tournament) }}" class="mr-4 text-blue-600 hover:text-blue-900">Strona turnieju</a>
                        <button type="button" wire:click="openEditModal({{ $tournament->id }})" class="text-blue-600 hover:text-blue-900">Edytuj</button>
                        <button type="button" wire:click="delete({{ $tournament->id }})" wire:confirm="Usunąć turniej? Mecze turniejowe zostaną, ale bez przypisania." class="ml-4 text-red-600 hover:text-red-900">Usuń</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-8 text-center text-sm text-gray-500">Brak turniejów. Dodaj np. Turniej im. Włodzimierza Winklera.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="px-4 py-4">{{ $tournaments->links() }}</div>

    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeModal"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl rounded-lg bg-white shadow-xl" @click.stop>
                    <form wire:submit.prevent="save">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edytuj turniej' : 'Dodaj turniej' }}</h3>
                        </div>

                        <div class="space-y-4 px-6 py-4">
                            <div class="grid gap-4 sm:grid-cols-[1fr_10rem]">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nazwa</label>
                                    <input type="text" wire:model="name" placeholder="np. Turniej im. Włodzimierza Winklera" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Edycja</label>
                                    <input type="text" wire:model="edition" placeholder="np. XII" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Od</label>
                                    <input type="date" wire:model="start_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('start_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Do (gdy kilka dni)</label>
                                    <input type="date" wire:model="end_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('end_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Miejscowość</label>
                                    <input type="text" wire:model="city" placeholder="np. Sulęcin" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Hala</label>
                                    <input type="text" wire:model="venue" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>

                            <label class="flex items-center text-sm text-gray-700">
                                <input type="checkbox" wire:model="is_organizer" class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                Organizuje Joker Piła (w kalendarzu jak mecz u siebie)
                            </label>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Nasze drużyny i zajęte miejsca</label>
                                <div class="space-y-1 rounded-md border border-gray-200 p-2">
                                    @foreach($teams as $team)
                                        <div class="flex items-center gap-3" wire:key="tournament-team-{{ $team->id }}">
                                            <label class="flex w-40 items-center text-sm text-gray-700">
                                                <input type="checkbox" wire:model.live="team_ids" value="{{ $team->id }}" class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                {{ $team->name }}
                                            </label>
                                            @if(in_array($team->id, array_map('intval', $team_ids), true))
                                                <input type="text" wire:model="placements.{{ $team->id }}" placeholder="Miejsce, np. 2. miejsce (po turnieju)" class="min-w-0 flex-1 rounded-md border-gray-300 text-sm">
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                @error('team_ids') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Opis</label>
                                <textarea wire:model="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 rounded-b-lg bg-gray-50 px-6 py-4">
                            <button type="button" wire:click="closeModal" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50">Anuluj</button>
                            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Zapisz</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

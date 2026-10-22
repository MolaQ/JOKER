@php use App\Models\TrainingSession; @endphp
<div class="grid gap-6 lg:grid-cols-[22rem_1fr]">
    {{-- Formularz --}}
    <form wire:submit="save" class="h-fit space-y-4 rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200 lg:sticky lg:top-6">
        <h2 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edytuj trening' : 'Dodaj trening' }}</h2>

        @if($this->teams->isEmpty())
            <p class="text-sm text-gray-600">Nie prowadzisz żadnej drużyny. Poproś szefa lub administratora o przypisanie do drużyny.</p>
        @else
            <div>
                <label for="training-team" class="block text-sm font-medium text-gray-700">Drużyna</label>
                <select id="training-team" wire:model="team_id" class="mt-1 block w-full">
                    @foreach($this->teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </select>
                @error('team_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <fieldset>
                <legend class="text-sm font-medium text-gray-700">{{ $editingId ? 'Dzień' : 'Dni (możesz zaznaczyć kilka)' }}</legend>
                <div class="mt-2 grid grid-cols-4 gap-1.5">
                    @foreach(TrainingSession::WEEKDAYS as $number => $label)
                        <label class="flex cursor-pointer items-center justify-center rounded-md px-2 py-2 text-sm font-semibold ring-1 ring-gray-200 has-[:checked]:bg-sky-600 has-[:checked]:text-white has-[:checked]:ring-sky-600">
                            <input @if($editingId) type="radio" wire:model.live="weekdays.0" @else type="checkbox" wire:model.live="weekdays" @endif value="{{ $number }}" class="sr-only">
                            {{ mb_substr($label, 0, 3) }}
                        </label>
                    @endforeach
                </div>
                @error('weekdays') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </fieldset>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="training-start" class="block text-sm font-medium text-gray-700">Od</label>
                    <input type="time" id="training-start" wire:model.live.debounce.400ms="starts_at" step="300" class="mt-1 block w-full">
                    @error('starts_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="training-end" class="block text-sm font-medium text-gray-700">Do</label>
                    <input type="time" id="training-end" wire:model.live.debounce.400ms="ends_at" step="300" class="mt-1 block w-full">
                    @error('ends_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <fieldset>
                <legend class="text-sm font-medium text-gray-700">Miejsce</legend>
                <div class="mt-2 grid grid-cols-2 gap-1.5">
                    @foreach(TrainingSession::VENUES as $key => $label)
                        <label @class(['flex cursor-pointer items-center justify-center rounded-md px-2 py-2 text-sm font-semibold ring-1 ring-gray-200 has-[:checked]:bg-sky-600 has-[:checked]:text-white has-[:checked]:ring-sky-600', 'col-span-2' => $key === 'main'])>
                            <input type="radio" wire:model.live="venue" value="{{ $key }}" class="sr-only"> {{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div>
                <label for="training-note" class="block text-sm font-medium text-gray-700">Uwagi (opcjonalnie)</label>
                <input type="text" id="training-note" wire:model="note" maxlength="120" placeholder="np. tylko rozgrywający" class="mt-1 block w-full">
            </div>

            @if($this->conflicts->isNotEmpty())
                <div class="rounded-md bg-amber-50 p-3 text-sm text-amber-900 ring-1 ring-amber-200">
                    <p class="font-semibold">To miejsce jest już zajęte:</p>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach($this->conflicts as $conflict)
                            <li>{{ $conflict->weekdayLabel() }} {{ $conflict->timeRange() }}: {{ $conflict->team->name }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-1 text-xs">Możesz zapisać mimo to, np. gdy drużyny dzielą boisko.</p>
                </div>
            @endif

            <div class="flex items-center justify-between gap-3 pt-1">
                @if($editingId)
                    <button type="button" wire:click="resetForm" class="text-sm font-semibold text-gray-500 hover:text-gray-800">Anuluj</button>
                @else
                    <span></span>
                @endif
                <button type="submit" class="rounded-md bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">{{ $editingId ? 'Zapisz' : 'Dodaj do planu' }}</button>
            </div>
        @endif
    </form>

    {{-- Plan tygodnia --}}
    <div class="min-w-0">
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <label for="training-filter" class="text-sm text-gray-600">Pokaż:</label>
            <select id="training-filter" wire:model.live="filterTeam" class="w-auto">
                <option value="">Wszystkie drużyny</option>
                @foreach($allTeams as $team)
                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                @endforeach
            </select>
            <a href="{{ route('trainings.index') }}" target="_blank" class="ml-auto text-sm font-semibold text-sky-700 hover:text-sky-900">Zobacz na stronie ↗</a>
        </div>

        @if($days->isEmpty())
            <p class="rounded-lg bg-white p-6 text-sm text-gray-500 shadow ring-1 ring-zinc-200">Plan jest pusty. Dodaj pierwszy trening w formularzu.</p>
        @else
            <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3">
                @foreach($days as $weekday => $sessions)
                    <div class="rounded-lg bg-white p-4 shadow ring-1 ring-zinc-200" wire:key="day-{{ $weekday }}">
                        <h3 class="font-semibold text-gray-900">{{ TrainingSession::WEEKDAYS[$weekday] }}</h3>
                        <ul class="mt-3 space-y-2">
                            @foreach($sessions as $session)
                                <li wire:key="session-{{ $session->id }}" @class(['rounded-md p-2.5 text-sm ring-1', 'bg-sky-50 ring-sky-300' => $editingId === $session->id, 'bg-gray-50 ring-gray-200' => $editingId !== $session->id])>
                                    <p class="font-semibold tabular-nums">{{ $session->timeRange() }} <span class="font-normal text-gray-500">· {{ $session->venueLabel() }}</span></p>
                                    <p class="text-gray-700">{{ $session->team->name }}@if($session->note) <span class="text-gray-500">· {{ $session->note }}</span>@endif</p>
                                    @if($managedIds->contains($session->team_id))
                                        <div class="mt-1.5 flex gap-3 text-xs font-semibold">
                                            <button type="button" wire:click="edit({{ $session->id }})" class="text-sky-700 hover:text-sky-900">Edytuj</button>
                                            <button type="button" wire:click="delete({{ $session->id }})" wire:confirm="Usunąć ten trening z planu?" class="text-red-600 hover:text-red-800">Usuń</button>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

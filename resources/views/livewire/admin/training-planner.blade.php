@php use App\Models\TrainingSession; @endphp
<div class="space-y-6">
    {{-- Formularz: pola w dwóch rzędach nad planem tygodnia --}}
    <form id="training-form" wire:submit="save" class="scroll-mt-6 rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200">
        <h2 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edytuj trening' : 'Dodaj trening' }}</h2>

        @if($this->teams->isEmpty())
            <p class="text-sm text-gray-600">Nie prowadzisz żadnej drużyny. Poproś szefa lub administratora o przypisanie do drużyny.</p>
        @else
          <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-[minmax(10rem,14rem)_minmax(18rem,1fr)_minmax(14rem,16rem)]">
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
                <div class="mt-1 grid grid-cols-7 gap-1">
                    @foreach(TrainingSession::WEEKDAYS as $number => $label)
                        <label class="flex cursor-pointer items-center justify-center rounded-md px-1 py-2 text-sm font-semibold ring-1 ring-gray-200 has-[:checked]:bg-sky-600 has-[:checked]:text-white has-[:checked]:ring-sky-600">
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

            <fieldset class="md:col-span-2 xl:col-span-2">
                <legend class="text-sm font-medium text-gray-700">Miejsce</legend>
                <div class="mt-1 grid grid-cols-3 gap-1 sm:grid-cols-5">
                    @foreach(TrainingSession::VENUES as $key => $label)
                        <label class="flex cursor-pointer items-center justify-center rounded-md px-2 py-2 text-center text-sm font-semibold ring-1 ring-gray-200 has-[:checked]:bg-sky-600 has-[:checked]:text-white has-[:checked]:ring-sky-600">
                            <input type="radio" wire:model.live="venue" value="{{ $key }}" class="sr-only"> {{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div>
                <label for="training-note" class="block text-sm font-medium text-gray-700">Uwagi (opcjonalnie)</label>
                <input type="text" id="training-note" wire:model="note" maxlength="120" placeholder="np. tylko rozgrywający" class="mt-1 block w-full">
            </div>

          </div>

            @if($this->conflicts->isNotEmpty())
                <div class="mt-4 rounded-md bg-amber-50 p-3 text-sm text-amber-900 ring-1 ring-amber-200">
                    <p class="font-semibold">To miejsce jest już zajęte:</p>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach($this->conflicts as $conflict)
                            <li>{{ $conflict->weekdayLabel() }} {{ $conflict->timeRange() }}: {{ $conflict->team->name }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-1 text-xs">Możesz zapisać mimo to, np. gdy drużyny dzielą boisko.</p>
                </div>
            @endif

            <div class="mt-4 flex items-center justify-end gap-3">
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
            {{-- Tydzień jak w kalendarzu: dni obok siebie (na telefonie przewijane w bok) --}}
            <div class="-mx-1 grid snap-x auto-cols-[minmax(11rem,1fr)] grid-flow-col gap-2 overflow-x-auto px-1 pb-2 lg:grid-flow-row lg:grid-cols-7 lg:overflow-visible">
                @foreach(TrainingSession::WEEKDAYS as $weekday => $dayLabel)
                    @php $sessions = $days->get($weekday, collect()); @endphp
                    <div @class(['min-h-40 snap-start rounded-lg p-2 shadow-sm ring-1', 'bg-white ring-zinc-200' => $sessions->isNotEmpty(), 'bg-zinc-50 ring-zinc-200/70' => $sessions->isEmpty()]) wire:key="day-{{ $weekday }}">
                        <h3 class="flex items-baseline justify-between px-1 text-sm font-semibold text-gray-900">{{ $dayLabel }} <span class="text-xs font-normal text-gray-400">{{ $sessions->count() ?: '' }}</span></h3>
                        <ul class="mt-2 space-y-1.5">
                            @foreach($sessions as $session)
                                <li wire:key="session-{{ $session->id }}" @class(['rounded-md p-2 text-xs ring-1', 'bg-sky-50 ring-sky-300' => $editingId === $session->id, 'bg-gray-50 ring-gray-200' => $editingId !== $session->id])>
                                    <p class="font-semibold tabular-nums text-gray-900">{{ $session->timeRange() }}</p>
                                    <p class="text-gray-500">{{ $session->venueLabel() }}</p>
                                    <p class="mt-0.5 font-medium text-gray-800">{{ $session->team->name }}@if($session->note) <span class="text-gray-500">· {{ $session->note }}</span>@endif</p>
                                    @if($managedIds->contains($session->team_id))
                                        <div class="mt-1.5 flex flex-wrap gap-1">
                                            <button type="button" wire:click="edit({{ $session->id }})" onclick="document.getElementById('training-form').scrollIntoView({ behavior: 'smooth' })" class="admin-action !px-2 !py-0.5">Edytuj</button>
                                            <button type="button" wire:click="delete({{ $session->id }})" wire:confirm="Usunąć ten trening z planu?" class="admin-action admin-action-danger !px-2 !py-0.5">Usuń</button>
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

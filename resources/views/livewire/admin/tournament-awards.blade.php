@php
    $input = 'w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm';
    $label = 'block text-sm font-medium text-gray-700 mb-1';
@endphp
<div class="space-y-4">
    <p class="text-sm text-gray-600">Wyróżnienia indywidualne w kategorii {{ $category->name }}. Widać je na stronie turnieju w zakładce „Klasyfikacja” i na grafice do udostępnienia. Wyróżnienie dla zawodnika klubu trafia też do „Osiągnięć” w jego profilu. Listę nazw zmienisz w Ustawieniach (Turnieje: drabinka i karty drużyn).</p>

    <form wire:submit="save" class="grid gap-3 rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label class="{{ $label }}" for="award-name">Wyróżnienie</label>
            <select id="award-name" wire:model.live="award.name" class="{{ $input }}">
                <option value="">wybierz</option>
                @foreach($names as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                <option value="__custom">inne (wpiszę)</option>
            </select>
            @if($award['name'] === '__custom')
                <input type="text" wire:model="award.custom" placeholder="np. Najlepsza debiutantka" class="{{ $input }} mt-2">
            @endif
            @error('award.name') <p class="mt-1 text-xs text-red-600">Wybierz wyróżnienie.</p> @enderror
            @error('award.custom') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="{{ $label }}" for="award-player">Zawodnik klubu</label>
            <select id="award-player" wire:model.live="award.player" class="{{ $input }}">
                <option value="">— spoza klubu —</option>
                @foreach($players as $player)
                    <option value="{{ $player->id }}">{{ $player->full_name }}{{ $player->team ? ' ('.$player->team->name.')' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $label }}" for="award-person">Albo imię i nazwisko</label>
            <input id="award-person" type="text" wire:model="award.person" @disabled($award['player']) placeholder="zawodniczka rywali" class="{{ $input }} disabled:bg-gray-100">
            @error('award.person') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="{{ $label }}" for="award-team">Drużyna (opcjonalnie)</label>
            <select id="award-team" wire:model="award.team" class="{{ $input }}">
                <option value="">—</option>
                @foreach($category->participants->sortBy(fn ($participant) => $participant->name()) as $participant)
                    <option value="{{ $participant->id }}">{{ $participant->name() }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2 lg:col-span-4">
            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">Dodaj wyróżnienie</button>
        </div>
    </form>

    <ul class="divide-y divide-gray-200 overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
        @forelse($category->awards as $item)
            <li wire:key="award-{{ $item->id }}" class="flex flex-wrap items-center gap-3 px-4 py-3">
                <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600" aria-hidden="true">★</span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">{{ $item->name }}</p>
                    <p class="font-semibold text-gray-900">{{ $item->personLabel() }}@if($item->teamLabel()) <span class="font-normal text-gray-500">· {{ $item->teamLabel() }}</span>@endif</p>
                    @if($item->player_id)<p class="text-xs text-emerald-700">w profilu zawodnika</p>@endif
                </div>
                <button type="button" wire:click="move({{ $item->id }}, -1)" class="admin-action" title="Wyżej">↑</button>
                <button type="button" wire:click="move({{ $item->id }}, 1)" class="admin-action" title="Niżej">↓</button>
                <button type="button" wire:click="delete({{ $item->id }})" wire:confirm="Usunąć to wyróżnienie? Zniknie też z profilu zawodnika." class="text-sm text-red-600 hover:underline">Usuń</button>
            </li>
        @empty
            <li class="px-4 py-6 text-sm text-gray-500">Nie ma jeszcze wyróżnień w tej kategorii.</li>
        @endforelse
    </ul>
</div>

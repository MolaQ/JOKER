{{-- Edycja meczu turnieju w panelu: faza i pozycja w drabince, etykieta, miejsce, drużyny --}}
<form wire:submit="saveMatchEdit" class="grid gap-3 rounded-md bg-blue-50 p-3 sm:grid-cols-2 lg:col-span-5 lg:grid-cols-4">
    <div>
        <label class="{{ $label }}">Etykieta</label>
        <input type="text" wire:model="editMatch.label" class="{{ $input }}" placeholder="np. Półfinał 1">
    </div>
    @if($tournamentMatch->stage === 'playoff')
        <div>
            <label class="{{ $label }}">Faza = kolumna</label>
            <input type="number" min="1" wire:model="editMatch.bracket_round" class="{{ $input }}">
        </div>
        <div>
            <label class="{{ $label }}">Nazwa fazy</label>
            <input type="text" wire:model="editMatch.phase_name" class="{{ $input }}" placeholder="np. Finały">
        </div>
        <div>
            <label class="{{ $label }}">Pozycja w kolumnie</label>
            <input type="number" min="1" wire:model="editMatch.bracket_slot" class="{{ $input }}">
        </div>
        <div>
            <label class="{{ $label }}">Drabinka</label>
            <select wire:model="editMatch.bracket_from" class="{{ $input }}">
                <option value="">główna (o 1. miejsce)</option>
                @foreach([3, 5, 7, 9, 11, 13] as $from)
                    <option value="{{ $from }}">o miejsca od {{ $from }}.</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $label }}">Decyduje o miejscu</label>
            <select wire:model="editMatch.decides_place" class="{{ $input }}">
                <option value="">— nie —</option>
                <option value="1">1. miejsce (finał)</option>
                @foreach([3, 5, 7, 9, 11, 13, 15] as $place)
                    <option value="{{ $place }}">o {{ $place }}. miejsce</option>
                @endforeach
            </select>
        </div>
    @elseif($tournamentMatch->stage === 'placement')
        <div>
            <label class="{{ $label }}">Mecz o miejsce</label>
            <input type="number" min="1" wire:model="editMatch.placement" class="{{ $input }}">
        </div>
    @else
        <div>
            <label class="{{ $label }}">Grupa</label>
            <select wire:model="editMatch.group" class="{{ $input }}">
                @foreach($category->groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    @foreach(['home' => 'Gospodarz', 'away' => 'Gość'] as $side => $sideName)
        <div>
            <label class="{{ $label }}">{{ $sideName }}</label>
            <select wire:model="editMatch.{{ $side }}" class="{{ $input }}">
                <option value="">— jeszcze nieznany —</option>
                @foreach($category->participants as $participant)
                    <option value="{{ $participant->id }}">{{ $participant->name() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $label }}">…albo opis</label>
            <input type="text" wire:model="editMatch.{{ $side }}_placeholder" class="{{ $input }}" placeholder="np. Zwycięzca półfinału 1">
        </div>
    @endforeach
    <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
        <button type="submit" class="{{ $primary }}">Zapisz mecz</button>
        <button type="button" wire:click="$set('editingMatchId', null)" class="{{ $secondary }}">Anuluj</button>
    </div>
</form>

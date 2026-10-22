{{-- Edycja meczu turnieju w panelu: numer, faza i pozycja w drabince, etykieta, miejsce, skąd drużyny --}}
<div class="grid gap-3 rounded-md bg-blue-50 p-3 sm:grid-cols-2 lg:col-span-5 lg:grid-cols-4">
    <div>
        <label class="{{ $label }}">Numer meczu</label>
        <input type="number" min="1" wire:model.blur.live="editMatch.number" class="{{ $input }}">
        @error('editMatch.number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="{{ $label }}">Etap</label>
        <select wire:model.live="editMatch.stage" class="{{ $input }}">
            @foreach(\App\Models\TournamentMatch::STAGES as $value => $name)
                <option value="{{ $value }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="{{ $label }}">Etykieta</label>
        <input type="text" wire:model.blur.live="editMatch.label" class="{{ $input }}" placeholder="np. Półfinał 1">
    </div>
    <div>
        <label class="{{ $label }}">Termin</label>
        <input type="datetime-local" wire:model.blur.live="editMatch.starts_at" class="{{ $input }}">
    </div>
    <div>
        <label class="{{ $label }}">Boisko / hala</label>
        <input type="text" wire:model.blur.live="editMatch.court" class="{{ $input }}">
    </div>
    @if($editMatch['stage'] === 'playoff')
        <div>
            <label class="{{ $label }}">Faza = kolumna</label>
            <input type="number" min="1" wire:model.blur.live="editMatch.bracket_round" class="{{ $input }}">
        </div>
        <div>
            <label class="{{ $label }}">Nazwa fazy</label>
            <input type="text" wire:model.blur.live="editMatch.phase_name" class="{{ $input }}" placeholder="np. Finały">
        </div>
        <div>
            <label class="{{ $label }}">Pozycja w kolumnie</label>
            <input type="number" min="1" wire:model.blur.live="editMatch.bracket_slot" class="{{ $input }}">
        </div>
        <div>
            <label class="{{ $label }}">Drabinka</label>
            <select wire:model.live="editMatch.bracket_from" class="{{ $input }}">
                <option value="">główna (o 1. miejsce)</option>
                @foreach([3, 5, 7, 9, 11, 13] as $from)
                    <option value="{{ $from }}">o miejsca od {{ $from }}.</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $label }}">Decyduje o miejscu</label>
            <select wire:model.live="editMatch.decides_place" class="{{ $input }}">
                <option value="">— nie —</option>
                <option value="1">1. miejsce (finał)</option>
                @foreach([3, 5, 7, 9, 11, 13, 15] as $place)
                    <option value="{{ $place }}">o {{ $place }}. miejsce</option>
                @endforeach
            </select>
        </div>
    @elseif($editMatch['stage'] === 'placement')
        <div>
            <label class="{{ $label }}">Mecz o miejsce</label>
            <input type="number" min="1" wire:model.blur.live="editMatch.placement" class="{{ $input }}">
        </div>
    @else
        <div>
            <label class="{{ $label }}">Grupa</label>
            <select wire:model.live="editMatch.group" class="{{ $input }}">
                <option value="">—</option>
                @foreach($category->groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    @include('livewire.admin.tournament-match-sides', ['prefix' => 'editMatch', 'data' => $editMatch, 'sourceOptions' => \App\Support\TournamentBracket::sourceOptions($category, $editingMatchId)])
    <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
        <button type="button" wire:click="finishMatchEdit" class="{{ $primary }}">Gotowe</button>
        <span class="text-xs text-gray-500">Zmiany zapisują się same.</span>
        <a href="{{ route('admin.media.index', ['mecz' => 'tournament_match:'.$editingMatchId]) }}" class="ml-auto text-sm text-sky-700 hover:text-blue-800">Transmisja i zdjęcia →</a>
    </div>
</div>

{{-- Skąd biorą się drużyny meczu: reguła (miejsce w grupie, zwycięzca/przegrany meczu) albo ręczny wybór. --}}
@foreach(['home' => 'Gospodarz', 'away' => 'Gość'] as $side => $sideName)
    <div>
        <label class="{{ $label }}">{{ $sideName }}: skąd drużyna</label>
        <select wire:model.live="{{ $prefix }}.{{ $side }}_source" class="{{ $input }}">
            <option value="">wybiorę ręcznie</option>
            @foreach(collect($sourceOptions)->groupBy(fn ($name, $key) => str_starts_with($key, 'group:') ? 'Miejsce w grupie' : 'Wynik innego meczu', true) as $optgroup => $options)
                <optgroup label="{{ $optgroup }}">
                    @foreach($options as $value => $name)
                        <option value="{{ $value }}">{{ $name }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @error("{$prefix}.{$side}_source") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
    @if(($data[$side.'_source'] ?? '') === '')
        <div>
            <label class="{{ $label }}">{{ $sideName }}</label>
            <select wire:model="{{ $prefix }}.{{ $side }}" class="{{ $input }}">
                <option value="">— jeszcze nieznany —</option>
                @foreach($category->participants as $participant)
                    <option value="{{ $participant->id }}">{{ $participant->name() }}</option>
                @endforeach
            </select>
            <input type="text" wire:model="{{ $prefix }}.{{ $side }}_placeholder" class="{{ $input }} mt-1" placeholder="…albo opis, np. Gospodarz turnieju">
        </div>
    @else
        <p class="self-end pb-2 text-xs text-gray-500">Drużyna wpisze się sama, gdy {{ str_starts_with($data[$side.'_source'], 'group:') ? 'grupa zostanie rozegrana; wcześniej strona pokaże prognozę' : 'mecz źródłowy będzie miał wynik' }}.</p>
    @endif
@endforeach

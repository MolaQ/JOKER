{{-- Wybór kolejności listy według odbioru (właściwość Livewire $sort: '', 'wyswietlenia', 'polubienia'). --}}
<select wire:model.live="sort" aria-label="Kolejność" {{ $attributes->merge(['class' => 'rounded-md border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500']) }}>
    <option value="">Kolejność: domyślna</option>
    <option value="wyswietlenia">Najczęściej oglądane</option>
    <option value="polubienia">Najwięcej polubień</option>
</select>

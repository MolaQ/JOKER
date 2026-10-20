@props(['model', 'current' => null, 'pending' => null])
{{-- Wybór zdjęcia i przycięcie do pionowego kadru 4:5 z podglądem na żywo (Cropper.js) --}}
<div x-data="photoCropper('{{ $model }}')" {{ $attributes->merge(['class' => 'space-y-3']) }}>
    <div class="flex items-start gap-4">
        <div class="aspect-[4/5] w-28 shrink-0 overflow-hidden rounded-xl bg-zinc-100 ring-1 ring-zinc-200">
            <div x-show="source" x-ref="preview" class="h-full w-full overflow-hidden"></div>
            <template x-if="! source">
                <div class="h-full w-full">
                    @if($pending)
                        <img src="{{ $pending }}" alt="Nowe zdjęcie" class="h-full w-full object-cover">
                    @elseif($current)
                        <img src="{{ $current }}" alt="Obecne zdjęcie" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full items-center justify-center text-xs text-zinc-400">brak zdjęcia</div>
                    @endif
                </div>
            </template>
        </div>
        <div class="min-w-0 flex-1 space-y-2 text-sm">
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-md bg-white px-3 py-2 font-semibold text-zinc-800 ring-1 ring-zinc-300 hover:bg-zinc-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M12 4v12m0-12-4 4m4-4 4 4"/></svg>
                Wybierz zdjęcie
                <input type="file" accept="image/*" class="sr-only" x-ref="input" @change="choose($event)">
            </label>
            <p class="text-xs text-zinc-500">Pionowy kadr 4:5 (np. 800×1000). Po wybraniu zdjęcia przesuń i powiększ kadr - podgląd obok pokazuje efekt.</p>
            <p x-show="error" x-text="error" class="text-xs text-red-600"></p>
            <p wire:loading wire:target="{{ $model }}" class="text-xs text-zinc-500">Wysyłanie…</p>
        </div>
    </div>

    <div x-show="source" x-cloak class="space-y-2">
        <div class="max-h-[60vh] overflow-hidden rounded-xl bg-zinc-900">
            <img x-ref="image" :src="source" alt="Kadrowanie" class="block max-w-full">
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <button type="button" @click="zoom(0.1)" class="rounded-md bg-white px-3 py-1.5 ring-1 ring-zinc-300 hover:bg-zinc-50" aria-label="Powiększ">＋</button>
            <button type="button" @click="zoom(-0.1)" class="rounded-md bg-white px-3 py-1.5 ring-1 ring-zinc-300 hover:bg-zinc-50" aria-label="Pomniejsz">－</button>
            <button type="button" @click="rotate(90)" class="rounded-md bg-white px-3 py-1.5 ring-1 ring-zinc-300 hover:bg-zinc-50">Obróć ↻</button>
            <button type="button" @click="destroy()" class="rounded-md bg-white px-3 py-1.5 ring-1 ring-zinc-300 hover:bg-zinc-50">Anuluj</button>
            <button type="button" @click="confirm()" :disabled="! ready || uploading" class="ml-auto rounded-md bg-blue-600 px-4 py-1.5 font-semibold text-white hover:bg-blue-700 disabled:opacity-50">
                <span x-show="! uploading">Użyj tego kadru</span><span x-show="uploading">Wysyłanie…</span>
            </button>
        </div>
    </div>
</div>

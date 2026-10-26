@props([
    'model',
    'current' => null,
    'pending' => null,
    // Proporcje kadru, np. '4/5', '16/9', '1/1' albo 'free' (dowolny kadr, np. logo).
    'aspect' => '4/5',
    // Szerokość wysyłanego pliku w pikselach (przy dowolnym kadrze: najdłuższy bok).
    'width' => 800,
    // jpeg dla zdjęć, png dla logo z przezroczystym tłem.
    'format' => 'jpeg',
    // false = bez miniatury obok przycisku (gdy widok sam pokazuje obecny obrazek).
    'preview' => true,
    'hint' => null,
    'label' => null,
])
@php
    [$ratioW, $ratioH] = $aspect === 'free' ? [0, 0] : array_map('floatval', explode('/', $aspect));
    $ratio = $ratioH > 0 ? $ratioW / $ratioH : 0;
    $hint ??= $aspect === 'free'
        ? __('Po wybraniu pliku zaznacz fragment, który ma zostać (np. obetnij puste brzegi logo).')
        : __('Kadr :ratio. Po wybraniu zdjęcia przesuń i powiększ kadr, podgląd pokazuje efekt.', ['ratio' => str_replace('/', ':', $aspect)]);
    $boxStyle = 'aspect-ratio: '.($ratio > 0 ? $ratioW.' / '.$ratioH : '1 / 1');
    $boxWidth = $ratio >= 1.2 ? 'w-44' : 'w-28';
@endphp
{{-- Wybór obrazka i przycięcie z podglądem na żywo (Cropper.js); przycięty plik trafia do pola Livewire --}}
<div x-data="photoCropper(@js($model), @js(['aspect' => $ratio, 'width' => (int) $width, 'format' => $format]))" {{ $attributes->merge(['class' => 'space-y-3']) }}>
    <div class="flex items-start gap-4">
        @if($preview)
            <div class="{{ $boxWidth }} shrink-0 overflow-hidden rounded-xl bg-zinc-100 ring-1 ring-zinc-200" style="{{ $boxStyle }}">
                <div x-show="source" x-ref="preview" class="h-full w-full overflow-hidden"></div>
                <template x-if="! source">
                    <div class="h-full w-full">
                        @if($pending)
                            <img src="{{ $pending }}" alt="{{ __('Nowe zdjęcie') }}" class="h-full w-full {{ $aspect === 'free' ? 'object-contain p-1' : 'object-cover' }}">
                        @elseif($current)
                            <img src="{{ $current }}" alt="{{ __('Obecne zdjęcie') }}" class="h-full w-full {{ $aspect === 'free' ? 'object-contain p-1' : 'object-cover' }}">
                        @else
                            <div class="flex h-full items-center justify-center text-xs text-zinc-400">{{ __('brak zdjęcia') }}</div>
                        @endif
                    </div>
                </template>
            </div>
        @endif
        <div class="min-w-0 flex-1 space-y-2 text-sm">
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-md bg-white px-3 py-2 font-semibold text-zinc-800 ring-1 ring-zinc-300 hover:bg-zinc-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M12 4v12m0-12-4 4m4-4 4 4"/></svg>
                {{ $label ?? __('Wybierz zdjęcie') }}
                <input type="file" accept="image/*" class="sr-only" x-ref="input" @change="choose($event)">
            </label>
            <p class="text-xs text-zinc-500">{{ $hint }}</p>
            <p x-show="error" x-text="error" class="text-xs text-red-600"></p>
            <p wire:loading wire:target="{{ $model }}" class="text-xs text-zinc-500">{{ __('Wysyłanie…') }}</p>
        </div>
    </div>

    <div x-show="source" x-cloak class="space-y-2">
        <div class="max-h-[60vh] overflow-hidden rounded-xl {{ $format === 'png' ? 'bg-[repeating-conic-gradient(#e4e4e7_0_25%,#fff_0_50%)] bg-[length:16px_16px]' : 'bg-zinc-900' }}">
            <img x-ref="image" :src="source" alt="{{ __('Kadrowanie') }}" class="block max-w-full">
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <button type="button" @click="zoom(0.1)" class="rounded-md bg-white px-3 py-1.5 ring-1 ring-zinc-300 hover:bg-zinc-50" aria-label="{{ __('Powiększ') }}">＋</button>
            <button type="button" @click="zoom(-0.1)" class="rounded-md bg-white px-3 py-1.5 ring-1 ring-zinc-300 hover:bg-zinc-50" aria-label="{{ __('Pomniejsz') }}">－</button>
            <button type="button" @click="rotate(90)" class="rounded-md bg-white px-3 py-1.5 ring-1 ring-zinc-300 hover:bg-zinc-50">{{ __('Obróć') }} ↻</button>
            <button type="button" @click="destroy()" class="rounded-md bg-white px-3 py-1.5 ring-1 ring-zinc-300 hover:bg-zinc-50">{{ __('Anuluj') }}</button>
            @if($format === 'png')
                <span class="flex flex-wrap items-center gap-2 rounded-md bg-zinc-50 px-2 py-1 ring-1 ring-zinc-200">
                    <button type="button" @click="removeBackground()" :disabled="! ready" class="rounded-md bg-white px-3 py-1 font-semibold text-zinc-800 ring-1 ring-zinc-300 hover:bg-zinc-100 disabled:opacity-50" title="{{ __('Usuwa jednolite tło (np. białe) od brzegów obrazka; napisy w środku logo zostają.') }}">{{ __('Usuń tło') }}</button>
                    <label class="flex items-center gap-1.5 text-xs text-zinc-600">{{ __('czułość') }}
                        <input type="range" min="5" max="120" step="5" x-model="tolerance" @change="backgroundRemoved && removeBackground()" class="w-24 accent-blue-600">
                    </label>
                    <button type="button" x-show="backgroundRemoved" @click="restoreBackground()" class="text-xs font-semibold text-zinc-500 underline hover:text-zinc-800">{{ __('przywróć') }}</button>
                </span>
            @endif
            <button type="button" @click="confirm()" :disabled="! ready || uploading" class="ml-auto rounded-md bg-blue-600 px-4 py-1.5 font-semibold text-white hover:bg-blue-700 disabled:opacity-50">
                <span x-show="! uploading">{{ __('Użyj tego kadru') }}</span><span x-show="uploading">{{ __('Wysyłanie…') }}</span>
            </button>
        </div>
    </div>
</div>

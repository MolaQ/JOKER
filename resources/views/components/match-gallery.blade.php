@props(['gallery'])
{{-- Galeria zdjęć z meczu: siatka miniatur i podgląd na pełnym ekranie (strzałki, Esc) --}}
@php
    $photos = $gallery->photos->map(fn ($photo) => ['src' => $photo->url(), 'caption' => (string) $photo->caption])->values();
@endphp
@if($photos->isNotEmpty())
    <div
        {{ $attributes->merge(['class' => 'scroll-mt-32']) }}
        x-data="{
            open: false,
            index: 0,
            photos: @js($photos),
            show(i) { this.index = i; this.open = true; },
            next() { this.index = (this.index + 1) % this.photos.length; },
            prev() { this.index = (this.index - 1 + this.photos.length) % this.photos.length; },
        }"
        x-effect="document.body.classList.toggle('overflow-hidden', open)"
        x-on:keydown.escape.window="open = false"
        x-on:keydown.arrow-right.window="open && next()"
        x-on:keydown.arrow-left.window="open && prev()"
    >
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h3 class="heading-md">📷 {{ $gallery->title }}</h3>
                <p class="mt-1 text-sm text-zinc-500">{{ trans_choice(':count zdjęcie|:count zdjęcia|:count zdjęć', $photos->count()) }}</p>
                @if($gallery->description)
                    <p class="mt-2 max-w-2xl text-zinc-600">{{ $gallery->description }}</p>
                @endif
            </div>
            @livewire('like-button', ['type' => 'gallery', 'id' => $gallery->id], key('gallery-like-'.$gallery->id))
        </div>

        <div class="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
            @foreach($photos as $i => $photo)
                <button type="button" x-on:click="show({{ $i }})" class="group relative aspect-[4/3] overflow-hidden rounded-2xl bg-zinc-100" aria-label="{{ __('Powiększ zdjęcie :number', ['number' => $i + 1]) }}">
                    <img src="{{ $photo['src'] }}" alt="{{ $photo['caption'] ?: $gallery->title }}" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105">
                </button>
            @endforeach
        </div>

        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-[70] flex flex-col bg-ink/95 text-white" role="dialog" aria-modal="true" aria-label="{{ $gallery->title }}">
            <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <p class="min-w-0 truncate text-sm font-semibold">{{ $gallery->title }} · <span class="tabular-nums" x-text="(index + 1) + ' / ' + photos.length"></span></p>
                <div class="flex items-center gap-2">
                    @livewire('like-button', ['type' => 'gallery', 'id' => $gallery->id, 'dark' => true], key('gallery-lightbox-like-'.$gallery->id))
                    <button type="button" x-on:click="open = false" class="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-2xl hover:bg-white/20" aria-label="{{ __('Zamknij') }}">×</button>
                </div>
            </div>
            <div class="relative flex min-h-0 flex-1 items-center justify-center px-4 pb-4 sm:px-16" x-on:click.self="open = false">
                <img :src="photos[index]?.src" :alt="photos[index]?.caption || @js($gallery->title)" class="max-h-full max-w-full rounded-lg object-contain shadow-2xl">
                <button type="button" x-show="photos.length > 1" x-on:click="prev()" class="absolute top-1/2 left-2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-2xl hover:bg-white/20 sm:left-4" aria-label="{{ __('Poprzednie zdjęcie') }}">‹</button>
                <button type="button" x-show="photos.length > 1" x-on:click="next()" class="absolute top-1/2 right-2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-2xl hover:bg-white/20 sm:right-4" aria-label="{{ __('Następne zdjęcie') }}">›</button>
            </div>
            <p class="min-h-12 px-4 pb-5 text-center text-sm text-zinc-300" x-text="photos[index]?.caption"></p>
        </div>
    </div>
@endif

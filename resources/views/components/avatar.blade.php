@props(['photo' => null, 'name' => '', 'label' => null])

{{-- Zdjęcie osoby albo (gdy brak) inicjały / numer na klubowym tle. --}}
<div {{ $attributes->merge(['class' => 'relative flex items-center justify-center overflow-hidden bg-gradient-to-br from-graphite to-ink text-white']) }}>
    @if($photo)
        <img src="{{ \App\Support\Media::url($photo) }}" alt="{{ $name }}" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
    @endif
    <span class="font-display font-bold text-sky-club" aria-hidden="true">{{ $label ?? collect(explode(' ', $name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}</span>
</div>

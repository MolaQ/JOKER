@props(['photo' => null, 'name' => '', 'label' => null, 'logo' => false])
{{-- Zdjęcie osoby albo (gdy brak) inicjały / numer na klubowym tle.
     logo: herb drużyny bez tła i bez przycinania (logo ma już wycięte tło); tło z inicjałami tylko gdy herbu brak. --}}
@php
    $background = 'overflow-hidden bg-gradient-to-br from-graphite to-ink text-white';
    $bare = $logo && $photo;
@endphp
<div {{ $attributes->merge(['class' => 'relative flex items-center justify-center '.($bare ? '' : $background)]) }}>
    @if($photo)
        <img src="{{ \App\Support\Media::url($photo) }}" alt="{{ $name }}" loading="lazy" decoding="async" @class(['absolute inset-0 h-full w-full', 'object-contain' => $logo, 'object-cover' => ! $logo]) @if($bare) onerror="this.parentElement.classList.add('overflow-hidden', 'bg-gradient-to-br', 'from-graphite', 'to-ink', 'text-white'); this.nextElementSibling.hidden = false; this.remove()" @else onerror="this.remove()" @endif>
    @endif
    <span class="font-display font-bold text-sky-club" aria-hidden="true" @if($bare) hidden @endif>{{ $label ?? collect(explode(' ', $name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}</span>
</div>

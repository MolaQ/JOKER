@props(['country'])
{{-- Flaga jako SVG (emoji flag nie działają w Windows). country: pl albo gb --}}
@php($clipId = 'flag-'.\Illuminate\Support\Str::random(8))
@if($country === 'gb')
    <svg {{ $attributes->merge(['class' => 'inline-block shrink-0']) }} viewBox="0 0 60 30" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <clipPath id="{{ $clipId }}-s"><path d="M0,0 v30 h60 v-30 z"/></clipPath>
        <clipPath id="{{ $clipId }}-t"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/></clipPath>
        <g clip-path="url(#{{ $clipId }}-s)">
            <path d="M0,0 v30 h60 v-30 z" fill="#012169"/>
            <path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/>
            <path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#{{ $clipId }}-t)" stroke="#C8102E" stroke-width="4"/>
            <path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10"/>
            <path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6"/>
        </g>
    </svg>
@else
    <svg {{ $attributes->merge(['class' => 'inline-block shrink-0']) }} viewBox="0 0 16 10" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <rect width="16" height="5" fill="#fff"/>
        <rect y="5" width="16" height="5" fill="#dc143c"/>
    </svg>
@endif

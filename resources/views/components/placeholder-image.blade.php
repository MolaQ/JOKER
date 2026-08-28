@props(['label' => 'Joker Piła', 'height' => 'h-48'])

<div {{ $attributes->merge(['class' => "w-full {$height} rounded-lg bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 flex flex-col items-center justify-center text-white"]) }}>
    <div class="text-4xl mb-2">🏐</div>
    <div class="text-sm font-semibold opacity-90">{{ $label }}</div>
</div>

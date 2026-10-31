@props(['name'])
{{-- Plakietka z nazwą naszej drużyny, np. KADECI II; kolor bierze z tekstu obok, więc pasuje na jasnym i ciemnym tle --}}
<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center rounded-full bg-current/10 px-2 py-0.5 align-middle font-sans text-[0.7rem] leading-none font-bold tracking-wide whitespace-nowrap uppercase ring-1 ring-current/20']) }}>{{ $name }}</span>

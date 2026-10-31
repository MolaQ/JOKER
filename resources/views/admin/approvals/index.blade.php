@extends('admin.layout')

@section('title', $sections[$current]['label'])

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Do zatwierdzenia</h1>
            <p class="mt-1 text-sm text-gray-600">Konta, propozycje, komentarze i zmiany, które czekają na Twoją decyzję.</p>
        </div>
        @livewire('admin.approval-email-toggle')
    </div>

    <nav class="mt-5 flex flex-wrap gap-2" aria-label="Sekcje">
        @foreach($sections as $key => $section)
            <a href="{{ route($section['route']) }}" @class([
                'inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold ring-1 transition',
                'bg-sky-600 text-white ring-sky-600' => $key === $current,
                'bg-white text-gray-700 ring-gray-200 hover:ring-sky-400' => $key !== $current,
            ]) @if($key === $current) aria-current="page" @endif>
                <x-admin-icon :name="$section['icon']" class="size-4 opacity-70" /> {{ $section['label'] }}
                @if($section['count'] > 0)
                    <span @class(['rounded-full px-2 py-0.5 text-xs font-bold', 'bg-white text-sky-700' => $key === $current, 'bg-amber-400 text-gray-900' => $key !== $current])>{{ $section['count'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <p class="mt-5 mb-4 max-w-3xl text-sm text-gray-700">{{ $sections[$current]['intro'] }}</p>

    <div @class(['overflow-hidden rounded-lg shadow ring-1 ring-zinc-200' => in_array($current, ['propozycje', 'zawodnicy', 'profile'], true)])>
        @livewire($sections[$current]['component'], $current === 'oznaczenia' ? ['showEmpty' => true] : [])
    </div>
</div>
@endsection

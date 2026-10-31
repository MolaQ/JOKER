@extends('layouts.public')

@section('title', __('Logotypy klubów'))
@section('robots', 'noindex, nofollow')

@section('content')
<section class="relative isolate -mt-16 bg-club-navy pt-16 text-white lg:-mt-20 lg:pt-20">
    <div class="container-site pt-16 pb-10 lg:pt-20">
        <h1 class="heading-lg">{{ __('Logotypy klubów') }}</h1>
        <p class="mt-3 max-w-2xl text-white/75">{{ __('Do grafik transmisji i relacji. Kliknij logo, żeby pobrać plik w oryginalnej jakości, albo pobierz wszystkie naraz.') }}</p>
        @if($logos->isNotEmpty())
            <a href="{{ route('logos.zip') }}" class="btn-primary mt-6">⬇ {{ __('Pobierz wszystkie (:count, ZIP)', ['count' => $logos->count()]) }}</a>
        @endif
    </div>
</section>

<section class="container-site py-8" x-data="{ q: '', bg: 'checker', sort: (new URLSearchParams(location.search)).get('sort') || 'popular', order(popular, name) { return this.sort === 'popular' ? popular : (this.sort === 'name_desc' ? 100000 - name : name); }, match(name) { const n = (s) => s.toLocaleLowerCase('pl').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/ł/g, 'l'); return n(name).includes(n(this.q)); } }">
    <div class="flex flex-wrap items-center gap-3">
        <input type="search" x-model="q" placeholder="{{ __('Szukaj klubu…') }}" class="w-full max-w-sm rounded-full border border-zinc-300 px-4 py-2 text-sm">
        <label class="sr-only" for="logos-sort">{{ __('Sortuj') }}</label>
        <select id="logos-sort" x-model="sort" x-on:change="history.replaceState(null, '', '?sort=' + sort)" class="rounded-full border border-zinc-300 py-2 pr-9 pl-4 text-sm">
            <option value="popular">{{ __('Najpopularniejsze') }}</option>
            <option value="name">{{ __('Nazwa klubu A–Z') }}</option>
            <option value="name_desc">{{ __('Nazwa klubu Z–A') }}</option>
        </select>
        <div class="flex gap-1 rounded-full bg-zinc-100 p-1 text-xs font-semibold" role="group" aria-label="{{ __('Tło podglądu') }}">
            @foreach(['checker' => __('Przezroczyste'), 'light' => __('Jasne'), 'dark' => __('Ciemne')] as $value => $label)
                <button type="button" x-on:click="bg = '{{ $value }}'" :class="bg === '{{ $value }}' ? 'bg-white shadow text-zinc-900' : 'text-zinc-500'" class="rounded-full px-3 py-1">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    @foreach($logos->groupBy('group') as $group => $items)
        <h2 class="mt-8 font-display text-2xl font-semibold text-zinc-900 uppercase">{{ $group }}</h2>
        <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            @foreach($items as $logo)
                <li x-show="match(@js($logo['name']))" :style="'order: ' + order({{ $logo['popular_rank'] }}, {{ $logo['name_rank'] }})" title="{{ trans_choice('{0}|{1} :count pobranie|[2,4] :count pobrania|[5,*] :count pobrań', $logo['downloads'], ['count' => $logo['downloads']]) }}">
                    <a href="{{ route('logos.download', $logo['key']) }}" download class="group block overflow-hidden rounded-2xl ring-1 ring-zinc-200 transition hover:ring-sky-club">
                        <span class="flex aspect-square items-center justify-center p-4"
                            :class="{ 'bg-zinc-900': bg === 'dark', 'bg-white': bg === 'light' }"
                            :style="bg === 'checker' ? 'background: repeating-conic-gradient(#e4e4e7 0 25%, #fff 0 50%) 0 0 / 16px 16px' : ''">
                            <img src="{{ $logo['url'] }}" alt="{{ $logo['name'] }}" loading="lazy" class="max-h-full max-w-full object-contain">
                        </span>
                        <span class="flex items-center justify-between gap-2 border-t border-zinc-100 bg-white px-3 py-2">
                            <span class="min-w-0 line-clamp-2 text-sm leading-tight font-semibold text-zinc-900" title="{{ $logo['name'] }}">{{ $logo['name'] }}</span>
                            <span class="shrink-0 text-xs font-semibold text-sky-club-dark uppercase group-hover:underline">{{ $logo['extension'] }} ⬇</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endforeach

    @if($logos->isEmpty())
        <p class="mt-8 text-sm text-zinc-500">{{ __('Nie ma jeszcze żadnych logotypów. Dodaje się je w panelu: Rywale (klub albo drużyna) i Drużyny.') }}</p>
    @endif
</section>
@endsection

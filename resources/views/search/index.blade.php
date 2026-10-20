@extends('layouts.public')

@section('title', ($query ? $query.' - ' : '').__('Szukaj'))

@section('content')
<section class="container-site max-w-3xl pt-14 pb-6 lg:pt-20">
    <p class="eyebrow">{{ __('Wyszukiwarka') }}</p>
    <h1 class="heading-lg mt-4">{{ __('Szukaj na stronie') }}</h1>

    <form action="{{ route('search') }}" method="GET" role="search" class="mt-8 flex gap-2">
        <input type="search" name="q" value="{{ $query }}" inputmode="search" enterkeyhint="search" placeholder="{{ __('Zawodnik, drużyna, mecz, wpis…') }}" aria-label="{{ __('Szukana fraza') }}" @if(! $query) autofocus @endif
            class="h-14 min-w-0 flex-1 rounded-full border border-zinc-200 bg-white px-6 text-base outline-none focus:border-sky-club focus:ring-2 focus:ring-sky-club/30">
        <button type="submit" class="btn-primary h-14">{{ __('Szukaj') }}</button>
    </form>

    @if($query)
        <p class="mt-6 text-sm text-zinc-500">{{ $total ? __('Znaleziono').': '.$total : __('Brak wyników dla „:query”. Spróbuj innego słowa.', ['query' => $query]) }}</p>
    @endif
</section>

<section class="container-site max-w-3xl">
    @foreach($results->keys() as $type)
        @if($results->has($type))
            <h2 class="mt-8 text-xs font-semibold tracking-widest text-zinc-400 uppercase">{{ $type }}</h2>
            <ul class="card mt-3 divide-y divide-zinc-100 overflow-hidden">
                @foreach($results[$type] as $result)
                    <li>
                        <a href="{{ $result['url'] }}" class="flex flex-col px-5 py-4 transition hover:bg-zinc-50">
                            <span class="font-semibold">{{ $result['title'] }}</span>
                            @if($result['subtitle'])<span class="text-sm text-zinc-500">{{ $result['subtitle'] }}</span>@endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    @endforeach
</section>
@endsection

@extends('layouts.public')

@section('title', __('Mecze i tabele'))
@section('description', __('Terminarz, wyniki i tabele ligowe wszystkich drużyn :club: ligi seniorskie i rozgrywki młodzieżowe.', ['club' => $site['club_name']]))

@section('content')
{{-- Mecze i tabele na jednej stronie: zakładka w adresie (?tab=tables), żeby link do tabel dalej działał --}}
<div x-data="{
        tab: @js($startTab),
        go(tab) {
            this.tab = tab;
            const url = new URL(location.href);
            tab === 'tables' ? url.searchParams.set('tab', 'tables') : url.searchParams.delete('tab');
            history.replaceState(history.state, '', url);
        },
    }">
    <section class="container-site pt-14 pb-6 lg:pt-20">
        <p class="eyebrow">{{ __('Wszystkie drużyny') }} · {{ __('sezon') }} {{ $currentSeason?->name ?? date('Y') }}</p>
        <h1 class="heading-xl mt-4">{{ __('Mecze') }}</h1>
        @if($hasStandings)
            <div class="mt-6 inline-flex rounded-full bg-zinc-100 p-1 text-sm font-semibold sm:text-base" role="tablist" aria-label="{{ __('Mecze i tabele') }}">
                @foreach(['schedule' => __('Terminarz i wyniki'), 'tables' => __('Tabele')] as $tabKey => $tabLabel)
                    <button type="button" role="tab" x-on:click="go('{{ $tabKey }}')" :aria-selected="tab === '{{ $tabKey }}'"
                        :class="tab === '{{ $tabKey }}' ? 'bg-ink text-white shadow' : 'text-zinc-600 hover:text-zinc-900'"
                        class="rounded-full px-5 py-2 transition">{{ $tabLabel }}</button>
                @endforeach
            </div>
        @endif
    </section>

    <div x-show="tab === 'schedule'" @if($startTab !== 'schedule') x-cloak @endif>
        @livewire('games-browser')
    </div>
    @if($hasStandings)
        <div x-show="tab === 'tables'" @if($startTab !== 'tables') x-cloak @endif>
            @livewire('standings-browser')
        </div>
    @endif
</div>
@endsection

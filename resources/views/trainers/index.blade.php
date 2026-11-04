@extends('layouts.public')

@section('title', __('Sztab'))
@section('description', __('Trenerzy, trenerzy minisiatkówki i cały sztab :club: kto prowadzi którą drużynę.', ['club' => $site['club_name']]))

@push('structured-data')
    {!! \App\Support\Seo::jsonLd(\App\Support\Seo::staffList($entries)) !!}
    {!! \App\Support\Seo::jsonLd(\App\Support\Seo::breadcrumbs([[__('Strona główna'), route('home')], [__('Sztab'), route('trainers.index')]])) !!}
@endpush

@section('content')
<section class="container-site pt-14 pb-10 lg:pt-20">
    <p class="eyebrow">{{ __('Ludzie klubu') }}</p>
    <h1 class="heading-xl mt-4">{{ __('Sztab') }}</h1>
    <p class="mt-6 max-w-2xl text-lg text-zinc-600">{{ __('Wszyscy trenerzy i osoby, które pracują z naszymi drużynami: od minisiatkówki po seniorów, razem z fizjoterapeutami, statystykami i kierownikami.') }}</p>
</section>

<section class="container-site pb-20"
    x-data="{
        group: '',
        team: '',
        q: '',
        norm(text) { return String(text).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/ł/g, 'l'); },
        shows(el) {
            const groups = el.dataset.groups.split(' ');
            const teams = el.dataset.teams.split(' ');
            return (this.group === '' || groups.includes(this.group))
                && (this.team === '' || teams.includes(this.team))
                && (this.q.trim() === '' || this.norm(el.dataset.search).includes(this.norm(this.q.trim())));
        },
        get count() { return [...this.$refs.list.children].filter((el) => this.shows(el)).length; },
    }">
    @if($entries->isNotEmpty())
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('Pokaż') }}">
                <button type="button" x-on:click="group = ''" :aria-pressed="group === ''" :class="group === '' ? 'bg-ink text-white' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200'" class="rounded-full px-4 py-2 text-sm font-semibold transition">{{ __('Wszyscy') }}</button>
                @foreach($groups as $key => $label)
                    <button type="button" x-on:click="group = @js($key)" :aria-pressed="group === @js($key)" :class="group === @js($key) ? 'bg-ink text-white' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200'" class="rounded-full px-4 py-2 text-sm font-semibold transition">{{ __($label) }}</button>
                @endforeach
            </div>
            <div class="grid gap-2 sm:grid-cols-2 lg:w-[32rem]">
                @if($teams->count() > 1)
                    <select x-model="team" aria-label="{{ __('Drużyna') }}" class="block w-full rounded-full border border-zinc-300 bg-white px-4 py-2 text-sm">
                        <option value="">{{ __('Wszystkie drużyny') }}</option>
                        @foreach($teams as $team)
                            <option value="{{ $team['key'] }}">{{ $team['name'] }}</option>
                        @endforeach
                    </select>
                @endif
                <input type="search" x-model="q" placeholder="{{ __('Szukaj osoby') }}" aria-label="{{ __('Szukaj osoby') }}" class="block w-full rounded-full border border-zinc-300 bg-white px-4 py-2 text-sm @if($teams->count() <= 1) sm:col-span-2 @endif">
            </div>
        </div>

        <div x-ref="list" class="mt-8 grid grid-cols-2 gap-3 sm:gap-6 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($entries as $entry)
                <a href="{{ route('trainers.show', $entry['user']) }}" x-show="shows($el)"
                    data-groups="{{ implode(' ', $entry['groups']) }}"
                    data-teams="{{ $entry['teams']->pluck('key')->implode(' ') }}"
                    data-search="{{ $entry['user']->name }} {{ $entry['function'] }} {{ $entry['teams']->pluck('name')->implode(' ') }}"
                    class="card card-hover group overflow-hidden">
                    <x-avatar :photo="$entry['user']->avatar_path" :name="$entry['user']->name" class="aspect-[4/5] w-full text-4xl sm:text-6xl" />
                    <div class="p-3 sm:p-5">
                        <p class="text-[0.65rem] font-semibold tracking-widest text-sky-club-dark uppercase sm:text-xs">{{ $entry['function'] }}</p>
                        <h2 class="mt-1 font-display text-base leading-tight font-bold uppercase sm:text-xl group-hover:text-sky-club-dark">{{ $entry['user']->name }}</h2>
                        @if($entry['teams']->isNotEmpty())
                            <ul class="mt-2 flex flex-wrap gap-1 sm:mt-3 sm:gap-1.5">
                                @foreach($entry['teams'] as $team)
                                    <li class="chip bg-zinc-100 text-xs text-zinc-700 ring-1 ring-zinc-200">{{ $team['name'] }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <p x-show="count === 0" x-cloak class="mt-8 rounded-3xl bg-zinc-100 p-8 text-center text-zinc-600">{{ __('Nikt nie pasuje do wybranych filtrów.') }}</p>
    @else
        <p class="rounded-3xl bg-zinc-100 p-8 text-zinc-600">{{ __('Sztab zostanie wkrótce uzupełniony.') }}</p>
    @endif
</section>
@endsection

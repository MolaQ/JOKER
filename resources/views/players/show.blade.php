@extends('layouts.public')

@section('title', $player->full_name . ' - Joker Piła')
@section('description', $player->full_name.' - '.($player->position?->label() ?? 'zawodnik').' drużyny '.$player->team?->name.' (Joker Piła).')

@section('content')
<section class="relative isolate -mt-16 overflow-hidden bg-ink pt-16 text-white lg:-mt-20 lg:pt-20">
    <div class="net-pattern absolute inset-0 -z-10" aria-hidden="true"></div>
    <span class="pointer-events-none absolute -right-8 bottom-0 -z-10 font-display text-[16rem] leading-none font-bold text-white/5 select-none sm:text-[24rem]" aria-hidden="true">{{ $player->jersey_number }}</span>

    <div class="container-site grid items-end gap-10 pt-16 pb-14 lg:grid-cols-[auto_1fr] lg:pt-24">
        <x-avatar :photo="$player->photo_path" :name="$player->full_name" :label="$player->jersey_number" class="aspect-[4/5] w-48 rounded-3xl text-7xl ring-4 ring-sky-club/40 lg:w-64" />
        <div>
            <a href="{{ route('teams.show', $player->team) }}" class="text-sm text-zinc-400 transition hover:text-white">← {{ $player->team?->name }}</a>
            <p class="eyebrow mt-5">{{ $player->position?->label() }} @if($player->jersey_number) · #{{ $player->jersey_number }} @endif</p>
            <h1 class="heading-xl mt-3">{{ $player->first_name }}<br>{{ $player->last_name }}</h1>
            <div class="mt-6">
                @livewire('like-button', ['type' => 'player', 'id' => $player->id], key('player-like-'.$player->id))
            </div>
        </div>
    </div>
</section>

<section class="container-site grid gap-8 py-14 lg:grid-cols-3">
    <div class="card p-6 lg:sticky lg:top-28 lg:self-start">
        <h2 class="heading-md">Dane</h2>
        <dl class="mt-5 divide-y divide-zinc-100 text-sm">
            @foreach([
                'Pozycja' => $player->position?->label(),
                'Wiek' => $player->age() ? $player->age().' lat' : null,
                'Wzrost' => $player->height ? $player->height.' cm' : null,
                'Waga' => $player->weight ? $player->weight.' kg' : null,
                'Zasięg' => $player->reach ? $player->reach.' cm' : null,
                'Zasięg w ataku' => $player->spike_reach ? $player->spike_reach.' cm' : null,
            ] as $label => $value)
                @if($value)
                    <div class="flex items-center justify-between py-3">
                        <dt class="text-zinc-500">{{ $label }}</dt>
                        <dd class="font-semibold">{{ $value }}</dd>
                    </div>
                @endif
            @endforeach
        </dl>

        <h3 class="mt-6 text-xs font-semibold tracking-widest text-zinc-500 uppercase">Drużyny w tym sezonie</h3>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach($seasonTeams->filter() as $seasonTeam)
                <a href="{{ route('teams.show', $seasonTeam) }}" class="chip hover:bg-sky-club hover:text-white">{{ $seasonTeam->name }}</a>
            @endforeach
        </div>
    </div>

    <div class="space-y-8 lg:col-span-2">
        @if($player->bio)
            <div class="card p-8">
                <h2 class="heading-md">O zawodniku</h2>
                <div class="prose-club mt-5">{!! strip_tags($player->bio, '<p><br><strong><em><b><i><ul><ol><li>') !!}</div>
            </div>
        @endif

        @if($player->position)
            <div class="relative overflow-hidden rounded-3xl bg-sky-club p-8 text-white">
                <p class="font-display text-sm font-semibold tracking-[0.25em] uppercase opacity-80">Pozycja</p>
                <h2 class="mt-2 font-display text-3xl font-bold uppercase">{{ $player->position->label() }}</h2>
                <p class="mt-3 max-w-xl text-white/90">{{ $player->position->description() }}</p>
            </div>
        @endif
    </div>
</section>
@endsection

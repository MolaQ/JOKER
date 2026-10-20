@extends('layouts.public')

@section('title', $trainer->name . ' - sztab')

@section('content')
<section class="relative isolate -mt-16 overflow-hidden bg-ink pt-16 text-white lg:-mt-20 lg:pt-20">
    <div class="net-pattern absolute inset-0 -z-10" aria-hidden="true"></div>

    <div class="container-site grid items-end gap-10 pt-16 pb-14 lg:grid-cols-[auto_1fr] lg:pt-24">
        <x-avatar :photo="$trainer->avatar_path" :name="$trainer->name" class="aspect-square w-40 rounded-3xl text-6xl ring-4 ring-sky-club/40 lg:w-56" />
        <div>
            <a href="{{ route('teams.index') }}" class="text-sm text-zinc-400 transition hover:text-white">← Drużyny</a>
            <p class="eyebrow mt-5">{{ $roles->pluck('role')->unique()->implode(' · ') ?: 'Sztab klubu' }}</p>
            <h1 class="heading-xl mt-3">{{ $trainer->name }}</h1>
            <x-view-count :model="$trainer" class="mt-3 text-white/70" />
        </div>
    </div>
</section>

<section class="container-site grid gap-8 py-14 lg:grid-cols-3">
    <div class="card p-6 lg:self-start">
        <h2 class="heading-md">Funkcje w klubie</h2>
        <ul class="mt-5 space-y-3">
            @forelse($roles as $entry)
                <li>
                    <a href="{{ route('teams.show', $entry['team']) }}" class="group flex items-center justify-between gap-4 rounded-2xl bg-zinc-50 p-4 transition hover:bg-sky-club/10">
                        <span>
                            <span class="block text-xs font-semibold tracking-widest text-sky-club-dark uppercase">{{ $entry['role'] }}</span>
                            <span class="block font-semibold">{{ $entry['team']->name }}</span>
                        </span>
                        <span class="transition group-hover:translate-x-1" aria-hidden="true">→</span>
                    </a>
                </li>
            @empty
                <li class="text-sm text-zinc-500">Brak przypisanych drużyn.</li>
            @endforelse
        </ul>

        @if($trainer->instagram)
            <a href="{{ $trainer->instagram }}" target="_blank" rel="noopener" class="btn-dark mt-6 w-full">Instagram</a>
        @endif
    </div>

    <div class="lg:col-span-2">
        <div class="card p-8">
            <h2 class="heading-md">O mnie</h2>
            <div class="prose-club mt-5">
                @if($trainer->additional_info)
                    {!! strip_tags($trainer->additional_info, '<p><br><strong><em><b><i><ul><ol><li>') !!}
                @else
                    <p class="text-zinc-500">Opis zostanie wkrótce uzupełniony.</p>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

@extends('layouts.public')

@section('title', __('Dokumenty'))
@section('description', __('Dokumenty klubu Joker Piła do pobrania: regulaminy, zgody, formularze i deklaracje.'))

@section('content')
<section class="container-site max-w-4xl pt-14 pb-6 lg:pt-20">
    <p class="eyebrow">{{ __('Do pobrania') }}</p>
    <h1 class="heading-xl mt-4">{{ __('Dokumenty') }}</h1>
    <p class="mt-6 max-w-2xl text-lg text-zinc-600">{{ __('Regulaminy, zgody i formularze dla zawodników i rodziców.') }}</p>
</section>

<section class="container-site max-w-4xl">
    @forelse($documents as $category => $group)
        <h2 class="mt-10 text-xs font-semibold tracking-widest text-zinc-400 uppercase">{{ $group->first()->categoryLabel() }}</h2>
        <ul class="mt-3 space-y-3">
            @foreach($group as $document)
                <li id="dokument-{{ $document->id }}" class="scroll-mt-28">
                    <a href="{{ \App\Support\Media::url($document->file_path) }}" download class="card card-hover group flex items-center gap-4 p-4 target:ring-2 target:ring-sky-club">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-club/10 text-xs font-bold text-sky-club-dark uppercase">{{ $document->file_type }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold group-hover:text-sky-club-dark">{{ $document->title }}</span>
                            @if($document->description)<span class="block truncate text-sm text-zinc-500">{{ $document->description }}</span>@endif
                        </span>
                        <span class="hidden text-sm text-zinc-400 sm:block">{{ $document->fileSizeFormatted() }}</span>
                        <svg class="h-5 w-5 shrink-0 text-zinc-400 group-hover:text-sky-club" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0-5-5m5 5 5-5M5 20h14"/></svg>
                    </a>
                </li>
            @endforeach
        </ul>
    @empty
        <p class="rounded-3xl bg-zinc-100 p-8 text-zinc-600">{{ __('Brak dokumentów do pobrania.') }}</p>
    @endforelse
</section>
@endsection

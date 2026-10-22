@extends('admin.layout')

@section('title', 'Galerie')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Galerie</h1>
        <p class="mt-2 max-w-3xl text-sm text-gray-700">Galerie z meczów i wydarzeń. Na zdjęciach oznaczasz drużyny, zawodników i trenerów, a oznaczenia prowadzą do ich profili. Oznaczenia zawodników bez zgody na wizerunek są ukryte na stronie.</p>
    </div>

    @if(($pendingTags = \App\Livewire\Admin\PhotoTagReview::pendingFor(auth()->user())->count()) > 0)
        <a href="{{ route('admin.photo-tags.index') }}" class="mb-6 block rounded-lg bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900 ring-1 ring-amber-300 hover:bg-amber-100"><x-admin-icon name="tag" class="mr-1 inline size-4 align-[-3px]" /> Oznaczenia na zdjęciach czekają na zatwierdzenie: {{ $pendingTags }} →</a>
    @endif
    @livewire('admin.gallery-manager')
</div>
@endsection

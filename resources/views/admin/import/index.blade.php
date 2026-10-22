@extends('admin.layout')

@section('title', 'Import danych')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Import danych</h1>
        <p class="mt-2 text-sm text-gray-700">Drużyny, trenerzy i zawodnicy z pliku CSV (np. zapisanego w Excelu jako „CSV rozdzielany średnikami”). Importuj w kolejności: drużyny, trenerzy i sztab, zawodnicy. Ponowny import tego samego pliku aktualizuje dane zamiast je dublować.</p>
    </div>

    <div class="overflow-hidden shadow ring-1 ring-zinc-200 sm:rounded-lg">
        @livewire('admin.data-importer')
    </div>
</div>
@endsection

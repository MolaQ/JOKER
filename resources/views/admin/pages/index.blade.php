@extends('admin.layout')

@section('title', 'Podstrony')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Podstrony</h1>
        <p class="mt-2 text-sm text-gray-700">Strony z tekstem, np. „Dla rodziców”, „FAQ”, „O klubie”. Link do podstrony wstawisz w menu lub górnym pasku w „Wygląd i ustawienia”.</p>
    </div>

    <div class="overflow-hidden shadow ring-1 ring-black/5 sm:rounded-lg">
        @livewire('admin.page-manager')
    </div>
</div>
@endsection

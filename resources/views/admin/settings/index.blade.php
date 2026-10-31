@extends('admin.layout')

@section('title', 'System')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">System</h1>
        <p class="mt-2 text-sm text-gray-700">Ustawienia całej strony: wygląd i kolory, menu, strona główna, wiadomości i kto do kogo może pisać, powiadomienia, kontakt i stopka. Zmiany widać od razu po zapisaniu. Widzi to tylko administrator i szef.</p>
    </div>

    <div class="overflow-clip shadow ring-1 ring-zinc-200 sm:rounded-lg">
        @livewire('admin.site-settings-manager')
    </div>
</div>
@endsection

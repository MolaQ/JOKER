@extends('admin.layout')

@section('title', 'Aktualizacja strony')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Aktualizacja strony</h1>
        <p class="mt-2 max-w-3xl text-sm text-gray-700">Wgraj tu paczkę <code>joker-kod.zip</code> zbudowaną skryptem <code>paczka-na-serwer.bat</code>. Panel ją sprawdzi, a po zatwierdzeniu hasłem zainstaluje ją zadanie cron na serwerze (co 5 minut). Plik <code>.env</code>, zdjęcia i kopie zapasowe zostają nietknięte.</p>
    </div>

    @livewire('admin.site-updater')
</div>
@endsection

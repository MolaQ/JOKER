@extends('admin.layout')

@section('title', 'Głosy MVP kibiców')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Głosy MVP kibiców</h1>
        <p class="mt-2 max-w-3xl text-sm text-gray-700">Kto na kogo głosował, mecz po meczu (najnowsze głosy u góry). Widzi to tylko administrator. „Nie liczy się” to głos zawodnika, gdy zawodnicy nie głosują, albo na kogoś zdjętego z głosowania.</p>
    </div>

    @livewire('admin.fan-mvp-votes')
</div>
@endsection

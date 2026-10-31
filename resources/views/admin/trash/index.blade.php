@extends('admin.layout')

@section('title', 'Kosz')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Kosz</h1>
        <p class="mt-2 text-sm text-gray-700">Usunięte w panelu konta, zawodnicy, aktualności, mecze, turnieje, drużyny, galerie i podstrony. Każdy wraca razem ze wszystkim, co od niego zależało (role, wiadomości, kategorie turnieju, zdjęcia…). Po {{ \App\Models\TrashItem::days() }} dniach znika na stałe; czas zmienisz w Ustawieniach systemu.</p>
    </div>

    @livewire('admin.trash-bin')
</div>
@endsection

@extends('admin.layout')

@section('title', 'Pozycjonowanie (SEO)')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Pozycjonowanie (SEO)</h1>
        <p class="mt-2 text-sm text-gray-700">Mapa strony dla Google, robots.txt i lista rzeczy, które poprawią widoczność klubu w wyszukiwarkach.</p>
    </div>

    @livewire('admin.seo-manager')
</div>
@endsection

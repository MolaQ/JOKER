@extends('admin.layout')

@section('title', 'Komentarze')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Komentarze</h1>
        <p class="mt-2 max-w-3xl text-sm text-gray-700">Komentarze kibiców pojawiają się na stronie dopiero po akceptacji. Komentarze rodziców, zawodników i osób z klubu publikują się od razu, ale tu też możesz je ukryć albo usunąć.</p>
    </div>

    @livewire('admin.comment-moderation')
</div>
@endsection

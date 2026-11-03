@props(['tournament'])
{{-- Turniej ćwiczebny: pasek na stronie turnieju i ekranie wpisywania, żeby nikt nie pomylił go z prawdziwym. --}}
@if($tournament->isPractice())
    <div class="bg-amber-300 text-amber-950" role="note">
        <p class="container-site py-2 text-sm font-semibold">🧪 {{ __('Turniej ćwiczebny. Widzą go tylko organizatorzy i wpisujący, a wyniki nie trafiają do kibiców.') }}</p>
    </div>
@endif

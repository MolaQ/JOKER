<div>
    {{-- Kategorie wiekowe i zakładki --}}
    <div class="sticky top-16 z-30 border-b border-zinc-200 bg-white/90 backdrop-blur-xl lg:top-20">
        <div class="container-site space-y-3 py-3">
            @if($categories->count() > 1)
                <div class="flex flex-wrap gap-2" aria-label="{{ __('Kategoria wiekowa') }}">
                    @foreach($categories as $categoryOption)
                        <button type="button" data-track="{{ __('Kategoria') }}: {{ $categoryOption->name }}" wire:click="selectCategory({{ $categoryOption->id }})" @class(['chip transition', 'bg-club-navy text-white' => $current?->id === $categoryOption->id, 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200' => $current?->id !== $categoryOption->id])>{{ $categoryOption->name }}</button>
                    @endforeach
                </div>
            @endif
            <div class="scrollbar-none -mx-4 flex gap-1 overflow-x-auto px-4 sm:mx-0 sm:px-0" role="tablist">
                @foreach($tabs as $key => $label)
                    <button type="button" role="tab" aria-selected="{{ $currentTab === $key ? 'true' : 'false' }}" wire:click="selectTab('{{ $key }}')" @class([
                        'shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition',
                        'bg-sky-club text-white shadow' => $currentTab === $key,
                        'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' => $currentTab !== $key,
                    ])>{{ $label }}</button>
                @endforeach
            </div>
        </div>
    </div>

    <section class="container-site py-10 transition-opacity" wire:loading.class="opacity-50">
        @if($tabs->isEmpty())
            <div class="card p-12 text-center text-zinc-500">{{ __('Szczegóły turnieju pojawią się wkrótce.') }}</div>
        @endif

        {{-- DRUŻYNY --}}
        @if($currentTab === 'druzyny' && $current)
            <h2 class="heading-md">{{ __('Drużyny') }} <span class="text-zinc-400">· {{ $current->name }}</span></h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($current->participants->sortBy(fn ($participant) => ($participant->group?->name ?? 'zz').$participant->name()) as $participant)
                    <div wire:key="participant-{{ $participant->id }}" @class(['card p-5', 'ring-2 ring-sky-club' => $participant->isOwnTeam()])>
                        <div class="flex items-center gap-4">
                            <x-avatar :photo="$participant->logoPath()" :name="$participant->name()" class="h-16 w-16 shrink-0 rounded-2xl text-xl" />
                            <div class="min-w-0">
                                <p class="font-display text-lg leading-tight font-semibold text-zinc-900 uppercase">{{ $participant->name() }}</p>
                                @if($participant->group)<p class="mt-1 text-sm text-zinc-500">{{ $participant->group->name }}</p>@endif
                            </div>
                        </div>
                        @if($participant->rosterLines())
                            <details class="group mt-4">
                                <summary class="cursor-pointer list-none text-sm font-semibold text-sky-club-dark" data-track="{{ __('Skład') }}: {{ $participant->name() }}">{{ __('Skład') }} ({{ count($participant->rosterLines()) }}) <span class="inline-block transition group-open:rotate-180">▾</span></summary>
                                <ul class="mt-2 columns-2 gap-4 text-sm text-zinc-700">
                                    @foreach($participant->rosterLines() as $line)
                                        <li>{{ $line }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- GRUPY --}}
        @if($currentTab === 'grupy' && $current)
            <div class="grid gap-8 lg:grid-cols-2">
                @foreach($current->groups as $group)
                    <div wire:key="group-{{ $group->id }}" class="card overflow-hidden">
                        <h2 class="bg-gradient-to-r from-club-navy to-sky-club-dark px-5 py-3 font-display text-xl font-semibold text-white uppercase">{{ $group->name }}</h2>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-zinc-50 text-xs font-semibold text-zinc-500 uppercase">
                                    <tr>
                                        <th class="px-4 py-2 text-left">#</th>
                                        <th class="px-2 py-2 text-left">{{ __('Drużyna') }}</th>
                                        <th class="px-2 py-2 text-center" title="{{ __('Mecze') }}">M</th>
                                        <th class="px-2 py-2 text-center" title="{{ __('Wygrane') }}">W</th>
                                        <th class="px-2 py-2 text-center" title="{{ __('Porażki') }}">P</th>
                                        <th class="px-2 py-2 text-center">{{ __('Sety') }}</th>
                                        <th class="px-2 py-2 text-center">{{ __('Małe pkt') }}</th>
                                        <th class="px-4 py-2 text-center">{{ __('Pkt') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100">
                                    @foreach($group->table() as $row)
                                        <tr @class(['bg-sky-club/10 font-semibold' => $row['participant']->isOwnTeam()])>
                                            <td class="px-4 py-2.5 font-bold">{{ $row['position'] }}</td>
                                            <td class="px-2 py-2.5">
                                                <span class="flex items-center gap-2">
                                                    <x-avatar :photo="$row['participant']->logoPath()" :name="$row['participant']->name()" class="h-6 w-6 shrink-0 rounded-full text-[9px]" />
                                                    <span class="truncate">{{ $row['participant']->name() }}</span>
                                                </span>
                                            </td>
                                            <td class="px-2 py-2.5 text-center tabular-nums">{{ $row['played'] }}</td>
                                            <td class="px-2 py-2.5 text-center text-emerald-700 tabular-nums">{{ $row['won'] }}</td>
                                            <td class="px-2 py-2.5 text-center text-red-600 tabular-nums">{{ $row['lost'] }}</td>
                                            <td class="px-2 py-2.5 text-center tabular-nums">{{ $row['sets_won'] }}:{{ $row['sets_lost'] }}</td>
                                            <td class="px-2 py-2.5 text-center text-zinc-500 tabular-nums">{{ $row['small_won'] }}:{{ $row['small_lost'] }}</td>
                                            <td class="px-4 py-2.5 text-center font-display text-lg font-bold tabular-nums">{{ $row['points'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($group->matches->isNotEmpty())
                            <details class="border-t border-zinc-100 px-5 py-3">
                                <summary class="cursor-pointer text-sm font-semibold text-sky-club-dark" data-track="{{ __('Mecze grupy') }}: {{ $group->name }}">{{ __('Mecze grupy') }} ({{ $group->matches->count() }})</summary>
                                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                    @foreach($group->matches as $match)
                                        <x-tournament-match :match="$match" :show-title="false" />
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </div>
                @endforeach
            </div>
            <p class="mt-4 text-xs text-zinc-500">{{ __('Punktacja: wygrana 3 pkt, wygrana w tie-breaku 2 pkt, porażka w tie-breaku 1 pkt. Kolejność: punkty, zwycięstwa, stosunek setów, stosunek małych punktów.') }}</p>
        @endif

        {{-- MECZE --}}
        @if($currentTab === 'mecze' && $current)
            @foreach($matchesByDay as $day => $dayMatches)
                <h2 class="heading-md mt-8 first:mt-0">{{ $day ? \Illuminate\Support\Carbon::parse($day)->translatedFormat('l, j F') : __('Termin do ustalenia') }}</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($dayMatches as $match)
                        <x-tournament-match :match="$match" :show-date="false" wire:key="match-{{ $match->id }}" />
                    @endforeach
                </div>
            @endforeach
        @endif

        {{-- DRABINKA I MECZE O MIEJSCA --}}
        @if($currentTab === 'drabinka' && $current)
            @foreach($playoff as $bracket)
                <h2 @class(['heading-md', 'mt-10' => ! $loop->first])>{{ $bracket['from'] === 1 ? __('Drabinka') : __('Drabinka o miejsca :from–:to', ['from' => $bracket['from'], 'to' => $bracket['to']]) }}</h2>
                @php
                    // Gdy organizator ustawił pozycje w kolumnach, mecz stoi dokładnie w swoim wierszu; inaczej drabinka się centruje.
                    $explicitSlots = $bracket['rounds']->flatten()->contains(fn ($match) => $match->bracket_slot !== null);
                    $columnNames = $bracket['rounds']->map(function ($roundMatches, $round) use ($bracket) {
                        $isLast = $round === $bracket['rounds']->keys()->last();

                        return match (true) {
                            filled($roundMatches->pluck('phase_name')->filter()->first()) => $roundMatches->pluck('phase_name')->filter()->first(),
                            $roundMatches->contains(fn ($match) => $match->decides_place !== null) && $isLast => __('Finały'),
                            $roundMatches->count() === 1 && $isLast => $bracket['from'] === 1 ? __('Finał') : __('Mecz o :place. miejsce', ['place' => $bracket['from']]),
                            $roundMatches->count() === 2 => __('Półfinały'),
                            $roundMatches->count() === 4 => __('Ćwierćfinały'),
                            $roundMatches->count() === 8 => __('1/8 finału'),
                            default => __('Runda :round', ['round' => $round]),
                        };
                    });
                    $highlight = fn ($match, $roundMatches, $isLast) => $match->decides_place === 1 || ($roundMatches->count() === 1 && $isLast && $bracket['from'] === 1 && $match->decides_place === null);
                @endphp
                <div class="mt-5 overflow-x-auto pb-2">
                    @if($explicitSlots)
                        <div class="grid min-w-max gap-x-6 gap-y-4" style="grid-template-columns: repeat({{ $bracket['rounds']->count() }}, 22rem)">
                            @foreach($bracket['rounds'] as $round => $roundMatches)
                                @php $column = $loop->iteration; $isLast = $loop->last; $nextSlot = 1; @endphp
                                <p class="text-xs font-semibold tracking-widest text-zinc-500 uppercase" style="grid-column: {{ $column }}; grid-row: 1">{{ $columnNames[$round] }}</p>
                                @foreach($roundMatches as $match)
                                    @php $slot = $match->bracket_slot ?? $nextSlot; $nextSlot = $slot + 1; @endphp
                                    <x-tournament-match :match="$match" :show-title="filled($match->label) || $match->decides_place !== null" @class(['ring-2 ring-amber-300' => $highlight($match, $roundMatches, $isLast)]) style="grid-column: {{ $column }}; grid-row: {{ $slot + 1 }}" wire:key="playoff-{{ $match->id }}" />
                                @endforeach
                            @endforeach
                        </div>
                    @else
                        <div class="flex min-w-max gap-6">
                            @foreach($bracket['rounds'] as $round => $roundMatches)
                                @php $isLast = $loop->last; @endphp
                                <div class="flex w-[22rem] flex-col">
                                    <p class="mb-3 text-xs font-semibold tracking-widest text-zinc-500 uppercase">{{ $columnNames[$round] }}</p>
                                    <div class="flex flex-1 flex-col justify-around gap-4">
                                        @foreach($roundMatches as $match)
                                            <x-tournament-match :match="$match" :show-title="filled($match->label) || $match->decides_place !== null" @class(['ring-2 ring-amber-300' => $highlight($match, $roundMatches, $isLast)]) wire:key="playoff-{{ $match->id }}" />
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            @if($placement->isNotEmpty())
                <h2 class="heading-md {{ $playoff->isNotEmpty() ? 'mt-10' : '' }}">{{ __('Mecze o miejsca') }}</h2>
                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($placement as $match)
                        <x-tournament-match :match="$match" wire:key="placement-{{ $match->id }}" />
                    @endforeach
                </div>
            @endif
        @endif

        {{-- KLASYFIKACJA KOŃCOWA --}}
        @if($currentTab === 'klasyfikacja' && $current)
            <h2 class="heading-md">{{ __('Klasyfikacja końcowa') }} <span class="text-zinc-400">· {{ $current->name }}</span></h2>
            <ol class="mt-6 max-w-2xl space-y-2">
                @foreach($finalStandings as $row)
                    <li @class(['flex items-center gap-4 rounded-2xl px-4 py-3', 'bg-gradient-to-r from-amber-200 to-amber-50' => $row['position'] === 1, 'bg-gradient-to-r from-zinc-200 to-zinc-50' => $row['position'] === 2, 'bg-gradient-to-r from-orange-200 to-orange-50' => $row['position'] === 3, 'bg-white ring-1 ring-zinc-200' => $row['position'] > 3, 'ring-2 ring-sky-club' => $row['participant']->isOwnTeam()])>
                        <span class="w-10 font-display text-3xl font-bold tabular-nums">{{ $row['position'] }}.</span>
                        <x-avatar :photo="$row['participant']->logoPath()" :name="$row['participant']->name()" class="h-10 w-10 shrink-0 rounded-full text-xs" />
                        <span class="font-semibold text-zinc-900">{{ $row['participant']->name() }}</span>
                        @if($row['position'] <= 3)<span class="ml-auto text-2xl" aria-hidden="true">{{ ['🥇', '🥈', '🥉'][$row['position'] - 1] }}</span>@endif
                    </li>
                @endforeach
            </ol>
        @endif

        {{-- ZASADY I INFORMATOR --}}
        @foreach(['zasady' => $tournament->rules, 'informator' => $tournament->guest_info] as $key => $content)
            @if($currentTab === $key)
                <div class="card mx-auto max-w-3xl p-8">
                    <h2 class="heading-md">{{ $tabs[$key] }}</h2>
                    <div class="prose-club mt-5">{!! strip_tags($content, '<p><br><strong><em><b><i><ul><ol><li><h2><h3><h4><a><table><thead><tbody><tr><th><td>') !!}</div>
                </div>
            @endif
        @endforeach
    </section>
</div>

<div style="--tm-bar-bg: {{ $site->color('tm_bar_bg') }}; --tm-bar-text: {{ $site->color('tm_bar_text') }}; --tm-number-bg: {{ $site->color('tm_number_bg') }}; --tm-number-text: {{ $site->color('tm_number_text') }};">
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
            @php
                $cardDark = $site->isDark('tm_card_from', 'tm_card_to');
                $cardStyle = 'background-image: linear-gradient(150deg, '.$site->color('tm_card_from').', '.$site->color('tm_card_to').')';
            @endphp
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($current->participants->sortBy(fn ($participant) => ($participant->group?->name ?? 'zz').$participant->name()) as $participant)
                    <div wire:key="participant-{{ $participant->id }}" style="{{ $cardStyle }}" @class(['card border-0 p-5 shadow-lg', 'ring-2 ring-sky-club' => $participant->isOwnTeam(), 'ring-1 ring-white/10' => $cardDark && ! $participant->isOwnTeam()])>
                        <div class="flex items-center gap-4">
                            <x-avatar logo :photo="$participant->logoPath()" :name="$participant->name()" @class(['h-16 w-16 shrink-0 rounded-2xl text-xl', 'bg-white/5 ring-1 ring-white/15' => $cardDark]) />
                            <div class="min-w-0">
                                <p @class(['font-display text-lg leading-tight font-semibold uppercase', 'text-white' => $cardDark, 'text-zinc-900' => ! $cardDark])>{{ $participant->name() }}</p>
                                @if($participant->group)<p @class(['mt-1 text-sm', 'text-white/60' => $cardDark, 'text-zinc-500' => ! $cardDark])>{{ $participant->group->name }}</p>@endif
                            </div>
                        </div>
                        @if($participant->rosterLines())
                            <details class="group mt-4">
                                <summary @class(['cursor-pointer list-none text-sm font-semibold', 'text-sky-club-light' => $cardDark, 'text-sky-club-dark' => ! $cardDark]) data-track="{{ __('Skład') }}: {{ $participant->name() }}">{{ __('Skład') }} ({{ count($participant->rosterLines()) }}) <span class="inline-block transition group-open:rotate-180">▾</span></summary>
                                <ul @class(['mt-2 columns-2 gap-4 text-sm', 'text-white/80' => $cardDark, 'text-zinc-700' => ! $cardDark])>
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
                        <h2 class="flex items-center justify-between gap-2 bg-gradient-to-r from-club-navy to-sky-club-dark px-5 py-3 font-display text-xl font-semibold text-white uppercase">{{ $group->name }} <x-tournament-group-status :progress="$bracket->groupProgress($group)" dark /></h2>
                        <div class="overflow-x-auto">
                            <table data-sortable class="w-full text-sm">
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
                                    @foreach($bracket->groupTable($group) as $row)
                                        <tr @class(['bg-sky-club/10 font-semibold' => $row['participant']->isOwnTeam()])>
                                            <td class="px-4 py-2.5 font-bold">{{ $row['position'] }}</td>
                                            <td class="px-2 py-2.5">
                                                <span class="flex items-center gap-2">
                                                    <x-avatar logo :photo="$row['participant']->logoPath()" :name="$row['participant']->name()" class="h-6 w-6 shrink-0 rounded-full text-[9px]" />
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
                                    @foreach($bracket->members($group)->whereNull('participant') as $member)
                                        <tr class="text-zinc-400 italic">
                                            <td class="px-4 py-2.5 font-bold not-italic">–</td>
                                            <td class="px-2 py-2.5" colspan="7">{{ $member['text'] }}</td>
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
                                        <x-tournament-match :match="$match" :bracket="$bracket" :show-title="false" />
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </div>
                @endforeach
            </div>
            <x-tournament-scoring-note :groups="$current->groups" :tournament="$tournament" class="mt-6" />
        @endif

        {{-- MECZE: terminarz dniami i godzinami, z filtrem drużyny --}}
        @if($currentTab === 'mecze' && $current)
            <x-tournament-schedule :matches="$current->matches" :bracket="$bracket" />
            <x-tournament-scoring-note :groups="$current->groups" :tournament="$tournament" class="mt-10" />
        @endif

        {{-- DRABINKA: grupy z postępem, mecze z numerami w kolumnach (na telefonie jedna pod drugą), podium --}}
        @if($currentTab === 'drabinka' && $current)
            <p class="max-w-3xl text-sm text-zinc-500">{{ __('Każdy mecz ma numer. Plakietka przy drużynie mówi, skąd przychodzi (1A = 1. miejsce grupy A, #7 = mecz nr 7), a napis w rogu meczu, dokąd idą zwycięzca i przegrany. Szare nazwy to prognoza, dopóki grupa trwa.') }}</p>

            @if($current->groups->isNotEmpty())
                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($current->groups as $group)
                        @php $progress = $bracket->groupProgress($group); @endphp
                        <div class="rounded-2xl bg-white p-4 ring-1 ring-zinc-200" wire:key="board-group-{{ $group->id }}">
                            <p class="flex items-center justify-between gap-2 font-semibold text-zinc-900">
                                {{ $group->name }}
                                <x-tournament-group-status :progress="$progress" />
                            </p>
                            <ol class="mt-2 space-y-0.5 text-sm">
                                @foreach($bracket->groupTable($group) as $row)
                                    <li @class(['flex gap-2', 'italic text-zinc-400' => ! $progress['finished'], 'text-zinc-800' => $progress['finished']])>
                                        <span class="w-6 font-bold not-italic text-zinc-400">{{ $row['position'] }}{{ \App\Support\TournamentBracket::groupLetter($group) }}</span>
                                        <span class="truncate">{{ $row['participant']->name() }}</span>
                                    </li>
                                @endforeach
                                @foreach($bracket->members($group)->whereNull('participant') as $member)
                                    <li class="flex gap-2 text-zinc-400 italic"><span class="w-6 font-bold not-italic">–</span><span class="truncate">{{ $member['text'] }}</span></li>
                                @endforeach
                            </ol>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-8 grid gap-8 lg:auto-cols-[minmax(19rem,1fr)] lg:grid-flow-col lg:overflow-x-auto lg:pb-2">
                @foreach($board as $column)
                    <section wire:key="board-column-{{ $loop->index }}">
                        <h2 class="mb-3 text-xs font-semibold tracking-widest text-zinc-500 uppercase">{{ $column['name'] }}</h2>
                        <div class="space-y-3">
                            @foreach($column['matches'] as $match)
                                <x-tournament-bracket-match :match="$match" :bracket="$bracket" wire:key="board-{{ $match->id }}" />
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            @if($standings->isNotEmpty())
                <h2 class="heading-md mt-12">{{ __('Klasyfikacja końcowa') }}</h2>
                <x-tournament-podium :standings="$standings" class="mt-6" />
            @endif
        @endif

        {{-- KLASYFIKACJA KOŃCOWA --}}
        @if($currentTab === 'klasyfikacja' && $current)
            <h2 class="heading-md">{{ __('Klasyfikacja końcowa') }} <span class="text-zinc-400">· {{ $current->name }}</span></h2>
            <x-tournament-podium :standings="$standings" class="mt-8" />
        @endif

        {{-- ZASADY I INFORMATOR --}}
        @foreach(['zasady' => $tournament->rules, 'informator' => $tournament->guest_info] as $key => $content)
            @if($currentTab === $key)
                <div class="card mx-auto max-w-3xl p-8">
                    <h2 class="heading-md">{{ $tabs[$key] }}</h2>
                    <div class="prose-club mt-5">{!! strip_tags($content, '<p><br><strong><em><b><i><u><s><mark><blockquote><hr><ul><ol><li><h2><h3><h4><a><table><thead><tbody><tr><th><td>') !!}</div>
                </div>
            @endif
        @endforeach
    </section>
</div>

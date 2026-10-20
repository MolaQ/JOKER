<div>
    @if($this->competitions->isNotEmpty())
        <div class="sticky top-16 z-30 border-y border-zinc-200 bg-white/90 backdrop-blur-xl lg:top-20">
            <div class="container-site py-3">
                <div class="scrollbar-none -mx-4 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:flex-wrap sm:px-0" role="tablist" aria-label="{{ __('Rozgrywki') }}">
                    @foreach($this->competitions as $competitionOption)
                        <button type="button" role="tab" wire:click="select('{{ $competitionOption->getRouteKey() }}')" aria-selected="{{ $this->selected?->is($competitionOption) ? 'true' : 'false' }}" @class([
                            'chip shrink-0 transition',
                            'bg-ink text-white' => $this->selected?->is($competitionOption),
                            'bg-zinc-100 text-zinc-700 hover:bg-zinc-200' => ! $this->selected?->is($competitionOption),
                        ])>{{ $competitionOption->name }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <section class="container-site py-10 transition-opacity" wire:loading.class="opacity-50" wire:target="select,selectStage">
        @if($this->selected)
            <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="heading-md">{{ $this->selected->name }}</h2>
                    @if($this->selected->levelName())
                        <p class="mt-1 text-sm text-zinc-500">{{ $this->selected->levelName() }}</p>
                    @endif
                </div>
                <p class="text-sm text-zinc-500">
                    @if($this->selected->synced_at)
                        {{ __('Wyniki z wielkopolskasiatkowka.com.pl i WZPS, aktualizacja') }} {{ $this->selected->synced_at->diffForHumans() }}
                    @endif
                </p>
            </div>
        @endif

        @if($standings->isNotEmpty())
            @if($stages->count() > 1)
                <div class="mb-6 inline-flex rounded-full bg-zinc-100 p-1 text-sm font-semibold" role="tablist" aria-label="{{ __('Etap rozgrywek') }}">
                    @foreach($stages as $stageOption)
                        <button type="button" role="tab" wire:click="selectStage('{{ $stageOption }}')" aria-selected="{{ $stageOption === $currentStage ? 'true' : 'false' }}" @class([
                            'rounded-full px-4 py-2 transition',
                            'bg-ink text-white shadow' => $stageOption === $currentStage,
                            'text-zinc-600 hover:text-zinc-900' => $stageOption !== $currentStage,
                        ])>{{ $stageOption }}@if($loop->first) <span class="text-xs font-normal opacity-70">({{ __('trwa') }})</span>@endif</button>
                    @endforeach
                </div>
            @endif

            <div class="space-y-4">
                @foreach($groups as $group)
                    <details @if($group['ours'] || $groups->count() === 1) open @endif wire:key="group-{{ $currentStage }}-{{ $group['name'] }}" class="group/details">
                        @if($group['name'] !== '' || $groups->count() > 1)
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 py-3 [&::-webkit-details-marker]:hidden">
                                <span class="font-display text-lg font-semibold tracking-wide text-zinc-700 uppercase">
                                    {{ $group['name'] ?: __('Tabela') }}
                                    @if($group['ours'])
                                        <span class="ml-2 rounded-full bg-sky-club/15 px-2 py-0.5 align-middle font-sans text-xs font-semibold tracking-normal text-sky-club-dark normal-case">
                                            {{ __('gra') }} {{ $group['rows']->filter(fn ($row) => $row->isOwnTeam())->map(fn ($row) => $row->competitorLabel()['name'].($row->competitorLabel()['suffix'] ? ' '.$row->competitorLabel()['suffix'] : ''))->join(', ', ' '.__('i').' ') }}
                                        </span>
                                    @endif
                                </span>
                                <span class="text-sm text-zinc-400 transition group-open/details:rotate-180" aria-hidden="true">▾</span>
                            </summary>
                        @endif

                        <div class="card overflow-x-auto">
                            <table class="w-full min-w-[40rem] text-sm tabular-nums">
                                <thead class="border-b border-zinc-200 text-xs font-semibold tracking-wider text-zinc-500 uppercase">
                                    <tr>
                                        <th class="sticky left-0 bg-white py-3 pr-2 pl-4 text-left" scope="col">#</th>
                                        <th class="sticky left-10 bg-white px-2 py-3 text-left" scope="col">{{ __('Drużyna') }}</th>
                                        <th class="px-3 py-3 text-center" scope="col" title="{{ __('Mecze') }}">{{ __('M') }}</th>
                                        <th class="px-3 py-3 text-center" scope="col" title="{{ __('Wygrane') }}">{{ __('W') }}</th>
                                        <th class="px-3 py-3 text-center" scope="col" title="{{ __('Przegrane') }}">{{ __('P') }}</th>
                                        <th class="px-3 py-3 text-center" scope="col">{{ __('Sety') }}</th>
                                        <th class="px-3 py-3 text-center" scope="col">{{ __('Małe pkt') }}</th>
                                        <th class="py-3 pr-4 pl-3 text-center" scope="col">{{ __('Pkt') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100">
                                    @foreach($group['rows'] as $standing)
                                        @php $isOurs = $standing->isOwnTeam(); @endphp
                                        <tr wire:key="standing-{{ $standing->id }}" @class(['bg-sky-club/10 font-semibold' => $isOurs])>
                                            <td @class(['sticky left-0 py-3 pr-2 pl-4', 'bg-[color-mix(in_oklab,var(--color-sky-club)_10%,white)]' => $isOurs, 'bg-white' => ! $isOurs])>
                                                <span @class(['inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold', 'bg-ink text-white' => $standing->position <= 3, 'bg-zinc-100 text-zinc-600' => $standing->position > 3])>{{ $standing->position }}</span>
                                            </td>
                                            <td @class(['sticky left-10 px-2 py-3 whitespace-nowrap', 'bg-[color-mix(in_oklab,var(--color-sky-club)_10%,white)]' => $isOurs, 'bg-white' => ! $isOurs])>
                                                @if($isOurs && $standing->competitor)
                                                    <a href="{{ route('teams.show', $standing->competitor) }}" class="text-zinc-900 hover:text-sky-club-dark"><x-team-label :label="$standing->competitorLabel()" /></a>
                                                    @if($standing->source_name && ! $standing->competition?->teams->firstWhere('id', $standing->competitor_id)?->pivot?->display_name)
                                                        <span class="ml-1 text-xs font-normal text-zinc-500">{{ __('jako') }} {{ $standing->source_name }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-zinc-800">{{ $standing->competitorName() }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3 text-center text-zinc-600">{{ $standing->played }}</td>
                                            <td class="px-3 py-3 text-center text-emerald-700">{{ $standing->won }}</td>
                                            <td class="px-3 py-3 text-center text-red-600">{{ $standing->lost }}</td>
                                            <td class="px-3 py-3 text-center text-zinc-600">{{ $standing->sets_won }}:{{ $standing->sets_lost }}</td>
                                            <td class="px-3 py-3 text-center text-zinc-600">{{ $standing->points_for }}:{{ $standing->points_against }}</td>
                                            <td class="py-3 pr-4 pl-3 text-center font-display text-lg font-bold text-sky-club-dark">{{ $standing->points }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                @endforeach
            </div>
            <p class="mt-4 text-xs text-zinc-500">{{ __('M - mecze, W - wygrane, P - przegrane, Pkt - punkty ligowe. Na telefonie tabelę przesuniesz palcem w bok.') }}</p>
        @else
            <div class="card p-12 text-center">
                <p class="font-display text-2xl font-semibold text-zinc-900 uppercase">{{ __('Brak tabeli') }}</p>
                <p class="mt-2 text-zinc-500">{{ __('Tabela pojawi się po pierwszych rozegranych meczach.') }}</p>
            </div>
        @endif
    </section>
</div>

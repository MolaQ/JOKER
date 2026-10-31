<div class="space-y-3">
    @forelse($trips as $trip)
        <div wire:key="mini-approval-{{ $trip->id }}" class="admin-card p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">{{ $trip->dateLabel() }}{{ $trip->city ? ' · '.$trip->city : '' }}</p>
                    <p class="mt-0.5 font-semibold text-gray-900">{{ $trip->title ?: $trip->city }}</p>
                    @if($trip->venue || $trip->organizer)
                        <p class="text-sm text-gray-600">{{ collect([$trip->venue, $trip->organizer])->filter()->implode(' · ') }}</p>
                    @endif
                </div>
            </div>

            @if($trip->status === \App\Models\MiniTrip::STATUS_PENDING)
                <div class="mt-3 rounded-lg bg-amber-50 p-3 ring-1 ring-amber-200">
                    <p class="text-sm text-amber-900"><span class="font-semibold">Nowy turniej</span> zgłosił(a) {{ $trip->submitter?->name ?? '—' }}, {{ $trip->created_at->format('d.m.Y H:i') }}.</p>
                    @if($trip->description)
                        <p class="mt-1 text-sm whitespace-pre-line text-gray-700">{{ $trip->description }}</p>
                    @endif
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button type="button" wire:click="approveTrip({{ $trip->id }})" class="rounded-md bg-green-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-green-700">Opublikuj turniej</button>
                        <button type="button" wire:click="rejectTrip({{ $trip->id }})" wire:confirm="Usunąć to zgłoszenie turnieju?" class="rounded-md px-3 py-1.5 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Odrzuć</button>
                    </div>
                </div>
            @endif

            @if($trip->pending_results)
                @php($pending = $trip->pending_results)
                <div class="mt-3 rounded-lg bg-sky-50 p-3 ring-1 ring-sky-200">
                    <p class="text-sm text-sky-900"><span class="font-semibold">Wyniki do zatwierdzenia</span> od {{ $submitters[$pending['submitted_by'] ?? 0] ?? '—' }}{{ isset($pending['submitted_at']) ? ', '.\Illuminate\Support\Carbon::parse($pending['submitted_at'])->format('d.m.Y H:i') : '' }}.</p>
                    <ul class="mt-2 divide-y divide-sky-100 rounded-md bg-white text-sm ring-1 ring-sky-100">
                        @foreach($pending['teams'] ?? [] as $row)
                            <li class="flex flex-wrap items-baseline gap-x-3 px-3 py-2">
                                <span class="font-semibold text-gray-900">{{ $row['place'] ? $row['place'].'. miejsce' : 'bez miejsca' }}@if($row['teams_count']) <span class="font-normal text-gray-500">| {{ $row['teams_count'] }}</span>@endif</span>
                                <span class="text-gray-800">{{ $groups[$row['group']] ?? '' }} · {{ $row['name'] }}</span>
                                <span class="w-full text-xs text-gray-500">{{ collect($row['players'] ?? [])->map(fn ($id) => $players[$id] ?? null)->filter()->implode(', ') ?: 'bez składu' }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if($trip->teams->isNotEmpty())
                        <p class="mt-2 text-xs text-gray-600">Teraz na stronie: {{ $trip->teams->map(fn ($team) => $team->name.($team->place ? ' ('.$team->place.'.)' : ''))->implode(', ') }}. Zatwierdzenie zastąpi te wyniki.</p>
                    @endif
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button type="button" wire:click="approveResults({{ $trip->id }})" class="rounded-md bg-green-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-green-700">Zatwierdź wyniki</button>
                        <button type="button" wire:click="rejectResults({{ $trip->id }})" wire:confirm="Odrzucić zgłoszone wyniki?" class="rounded-md px-3 py-1.5 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Odrzuć</button>
                    </div>
                </div>
            @endif
        </div>
    @empty
        <p class="admin-card p-6 text-sm text-gray-500">Nie ma zgłoszonych turniejów ani wyników minisiatkówki. Relacje z turniejów są w „Propozycjach treści”.</p>
    @endforelse
</div>

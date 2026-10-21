<div>
    <div class="flex items-center justify-between gap-3 border-b bg-white p-4">
        <h3 class="text-lg font-semibold text-gray-900">Propozycje zawodników i rodziców</h3>
        <select wire:model.live="statusFilter" class="rounded-md border-gray-300 text-sm">
            <option value="pending">Oczekujące</option>
            <option value="approved">Zatwierdzone</option>
            <option value="rejected">Odrzucone</option>
            <option value="all">Wszystkie</option>
        </select>
    </div>

    <ul class="divide-y divide-gray-200 bg-white">
        @forelse($requests as $request)
            <li class="grid gap-4 p-4 lg:grid-cols-[14rem_1fr_16rem]" wire:key="player-request-{{ $request->id }}">
                <div class="text-sm">
                    <a href="{{ route('players.show', $request->player) }}" target="_blank" class="font-semibold text-gray-900 hover:underline">{{ $request->player->full_name }}</a>
                    <p class="text-gray-500">{{ $request->player->team?->name }}</p>
                    <p class="mt-2 text-xs text-gray-500">Zgłasza: {{ $request->submitter?->name }} ({{ $request->submitter?->role?->label() }})<br>{{ $request->created_at->format('d.m.Y H:i') }}</p>
                </div>

                <div class="text-sm">
                    @if($request->isPhoto())
                        <div class="flex items-end gap-4">
                            <figure class="text-center text-xs text-gray-500">
                                <div class="aspect-[4/5] w-24 overflow-hidden rounded-lg bg-gray-100">
                                    @if($request->player->photo_path)
                                        <img src="{{ \App\Support\Media::url($request->player->photo_path) }}" onerror="this.style.visibility='hidden'" alt="Obecne" class="h-full w-full object-cover">
                                    @endif
                                </div>
                                <figcaption class="mt-1">obecne</figcaption>
                            </figure>
                            <span class="pb-10 text-gray-400">→</span>
                            <figure class="text-center text-xs text-gray-500">
                                <div class="aspect-[4/5] w-32 overflow-hidden rounded-lg bg-gray-100 ring-2 ring-blue-500">
                                    @if($request->photo_path)
                                        <img src="{{ \App\Support\Media::url($request->photo_path) }}" onerror="this.style.visibility='hidden'" alt="Proponowane" class="h-full w-full object-cover">
                                    @endif
                                </div>
                                <figcaption class="mt-1">proponowane</figcaption>
                            </figure>
                        </div>
                    @else
                        <table class="w-full text-left">
                            <thead class="text-xs text-gray-500"><tr><th class="py-1 pr-3 font-medium">Pole</th><th class="py-1 pr-3 font-medium">Teraz</th><th class="py-1 font-medium">Propozycja</th></tr></thead>
                            <tbody>
                                @foreach($request->proposed_data ?? [] as $field => $value)
                                    <tr class="align-top">
                                        <td class="py-1 pr-3 text-gray-600">{{ $labels[$field] ?? $field }}</td>
                                        <td class="py-1 pr-3 text-gray-400 line-through">{{ $field === 'bio' ? \Illuminate\Support\Str::limit(\App\Models\PlayerChangeRequest::bioAsText($request->player->bio), 120) : ($request->player->{$field} ?? '—') }}</td>
                                        <td class="py-1 font-semibold whitespace-pre-line text-gray-900">{{ $value ?? '— (usuń)' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="text-sm">
                    @if($request->status === 'pending')
                        <textarea wire:model="notes.{{ $request->id }}" rows="2" placeholder="Notatka (opcjonalnie)" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                        <div class="mt-2 flex gap-2">
                            <button type="button" wire:click="approve({{ $request->id }})" class="flex-1 rounded-md bg-green-600 px-3 py-2 font-semibold text-white hover:bg-green-700">Zatwierdź</button>
                            <button type="button" wire:click="reject({{ $request->id }})" wire:confirm="Odrzucić tę propozycję?" class="flex-1 rounded-md bg-white px-3 py-2 font-semibold text-red-600 ring-1 ring-red-200 hover:bg-red-50">Odrzuć</button>
                        </div>
                    @else
                        <p @class(['font-semibold', 'text-green-700' => $request->status === 'approved', 'text-red-600' => $request->status === 'rejected'])>{{ $request->status === 'approved' ? 'Zatwierdzone' : 'Odrzucone' }}</p>
                        <p class="text-xs text-gray-500">{{ $request->reviewer?->name }}, {{ $request->reviewed_at?->format('d.m.Y H:i') }}</p>
                        @if($request->review_note)<p class="mt-1 text-xs text-gray-600">„{{ $request->review_note }}”</p>@endif
                    @endif
                </div>
            </li>
        @empty
            <li class="p-8 text-center text-sm text-gray-500">Brak propozycji{{ $statusFilter === 'pending' ? ' do zatwierdzenia' : '' }}.</li>
        @endforelse
    </ul>

    <div class="p-4">{{ $requests->links() }}</div>
</div>

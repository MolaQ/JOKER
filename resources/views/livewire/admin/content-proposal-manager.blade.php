<div>
    <div class="flex items-center justify-between gap-3 border-b bg-white p-4">
        <h3 class="text-lg font-semibold text-gray-900">Propozycje od rodziców i zawodników</h3>
        <select wire:model.live="statusFilter" class="rounded-md border-gray-300 text-sm">
            <option value="pending">Oczekujące</option>
            <option value="approved">Zatwierdzone</option>
            <option value="rejected">Odrzucone</option>
            <option value="all">Wszystkie</option>
        </select>
    </div>

    <ul class="divide-y divide-gray-200 bg-white">
        @forelse($proposals as $proposal)
            <li class="grid gap-4 p-4 lg:grid-cols-[14rem_1fr_16rem]" wire:key="proposal-{{ $proposal->id }}">
                <div class="text-sm">
                    <span class="inline-flex rounded-md bg-sky-50 px-2 py-1 text-xs font-semibold text-sky-700">{{ $proposal->typeLabel() }}</span>
                    <p class="mt-2 font-semibold text-gray-900">{{ $proposal->user?->name }}</p>
                    <p class="text-xs text-gray-500">{{ $proposal->user?->roles()->map->label()->join(', ') }}<br>{{ $proposal->created_at->format('d.m.Y H:i') }}</p>
                </div>

                <div class="min-w-0 text-sm">
                    @if($proposal->match)
                        <p class="text-gray-600">Mecz: <span class="font-medium text-gray-900">{{ $proposal->match->mediaLabel() }}</span></p>
                    @endif
                    @if($proposal->title)<p class="mt-1 text-base font-semibold text-gray-900">{{ $proposal->title }}</p>@endif
                    @if($proposal->content)<div class="mt-1 max-h-48 overflow-y-auto whitespace-pre-line text-gray-700">{{ $proposal->content }}</div>@endif
                    @if($proposal->url)<a href="{{ $proposal->url }}" target="_blank" rel="noopener" class="mt-1 block break-all text-sky-700 underline">{{ $proposal->url }}</a>@endif
                    @if($proposal->files)
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($proposal->files as $path)
                                <a href="{{ \App\Support\Media::url($path) }}" target="_blank" class="block h-20 w-20 overflow-hidden rounded-md bg-gray-100">
                                    <img src="{{ \App\Support\Media::url($path) }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="text-sm">
                    @if($proposal->status === 'pending')
                        <textarea wire:model="notes.{{ $proposal->id }}" rows="2" placeholder="Notatka dla autora (opcjonalnie)" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                        <div class="mt-2 flex gap-2">
                            <button type="button" wire:click="approve({{ $proposal->id }})" class="rounded-md bg-green-600 px-3 py-1.5 font-semibold text-white hover:bg-green-700">Zatwierdź i opublikuj</button>
                            <button type="button" wire:click="reject({{ $proposal->id }})" wire:confirm="Odrzucić tę propozycję?" class="rounded-md px-3 py-1.5 font-semibold text-red-600 ring-1 ring-red-200 hover:bg-red-50">Odrzuć</button>
                        </div>
                    @else
                        <p @class(['font-semibold', 'text-green-700' => $proposal->status === 'approved', 'text-red-700' => $proposal->status === 'rejected'])>{{ $proposal->status === 'approved' ? 'Zatwierdzona' : 'Odrzucona' }}</p>
                        <p class="text-xs text-gray-500">{{ $proposal->reviewer?->name }} · {{ $proposal->reviewed_at?->format('d.m.Y H:i') }}</p>
                        @if($proposal->review_note)<p class="mt-1 text-gray-600">{{ $proposal->review_note }}</p>@endif
                    @endif
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-sm text-gray-500">Brak propozycji.</li>
        @endforelse
    </ul>

    <div class="bg-white px-4 py-3">{{ $proposals->links() }}</div>
</div>

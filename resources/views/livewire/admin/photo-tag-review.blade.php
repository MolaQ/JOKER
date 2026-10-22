<div>
    @if($tags->isNotEmpty())
        <div class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-amber-300">
            <h3 class="border-b bg-amber-50 px-4 py-3 text-base font-semibold text-amber-900">Oznaczenia na zdjęciach do zatwierdzenia ({{ $tags->count() }})</h3>
            <ul class="divide-y divide-gray-200">
                @foreach($tags as $tag)
                    <li class="flex flex-wrap items-center gap-4 p-4 text-sm" wire:key="tag-{{ $tag->id }}">
                        <a href="{{ $tag->photo->url() }}" target="_blank" class="block h-16 w-24 flex-none overflow-hidden rounded bg-gray-100"><img src="{{ $tag->photo->url() }}" alt="" class="h-full w-full object-cover"></a>
                        <div class="min-w-0 flex-1">
                            <p><span class="font-semibold text-gray-900">{{ $tag->label() }}</span> na zdjęciu z galerii „{{ $tag->photo->gallery->title }}”</p>
                            <p class="text-gray-500">Proponuje: {{ $tag->proposer?->name }} · {{ $tag->created_at->format('d.m.Y H:i') }}@unless($tag->isVisible()) · <span class="text-red-700">brak zgody na wizerunek, oznaczenie nie będzie widoczne</span>@endunless</p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" wire:click="approve({{ $tag->id }})" class="rounded-md bg-green-600 px-3 py-1.5 font-semibold text-white hover:bg-green-700">Zatwierdź</button>
                            <button type="button" wire:click="reject({{ $tag->id }})" class="rounded-md px-3 py-1.5 font-semibold text-red-600 ring-1 ring-red-200 hover:bg-red-50">Odrzuć</button>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @elseif($showEmpty)
        <p class="rounded-lg bg-white p-6 text-sm text-gray-500 shadow ring-1 ring-black/5">Żadne oznaczenie nie czeka na zatwierdzenie.</p>
    @endif
</div>

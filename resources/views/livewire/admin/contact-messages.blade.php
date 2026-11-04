<div class="space-y-3">
    @forelse ($messages as $contact)
        <article wire:key="kontakt-{{ $contact->id }}" @class(['rounded-lg border p-4', 'border-sky-300 bg-sky-50/60' => ! $contact->read_at, 'border-zinc-200 bg-white' => $contact->read_at])>
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="font-semibold text-zinc-900">
                        @unless ($contact->read_at)<span class="mr-1 inline-flex rounded-md bg-sky-600 px-1.5 py-0.5 text-xs font-semibold text-white">Nowa</span>@endunless
                        {{ $contact->subject }}
                    </p>
                    <p class="text-sm text-zinc-600">{{ $contact->name }} · <a href="mailto:{{ $contact->email }}" class="text-sky-700 hover:underline">{{ $contact->email }}</a> · {{ $contact->created_at->format('d.m.Y H:i') }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <a href="mailto:{{ $contact->email }}?subject={{ rawurlencode('Re: '.$contact->subject) }}" wire:click="markRead({{ $contact->id }})" class="rounded-md bg-sky-600 px-3 py-1.5 font-semibold text-white hover:bg-sky-700">Odpowiedz</a>
                    @if ($contact->read_at)
                        <button type="button" wire:click="markUnread({{ $contact->id }})" class="rounded-md px-3 py-1.5 font-semibold text-zinc-700 ring-1 ring-zinc-300 hover:bg-zinc-50">Oznacz jako nową</button>
                    @else
                        <button type="button" wire:click="markRead({{ $contact->id }})" class="rounded-md px-3 py-1.5 font-semibold text-zinc-700 ring-1 ring-zinc-300 hover:bg-zinc-50">Przeczytana</button>
                    @endif
                    <button type="button" wire:click="delete({{ $contact->id }})" wire:confirm="Usunąć tę wiadomość na stałe?" class="rounded-md px-3 py-1.5 font-semibold text-red-700 hover:bg-red-50">Usuń</button>
                </div>
            </div>
            <p class="mt-3 whitespace-pre-line text-sm text-zinc-800">{{ $contact->message }}</p>
            @if ($contact->read_at)
                <p class="mt-2 text-xs text-zinc-500">Przeczytana {{ $contact->read_at->format('d.m.Y H:i') }}@if ($contact->reader) przez {{ $contact->reader->name }}@endif</p>
            @endif
        </article>
    @empty
        <p class="rounded-lg border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500">Nikt jeszcze nie napisał przez formularz.</p>
    @endforelse

    {{ $messages->links() }}
</div>

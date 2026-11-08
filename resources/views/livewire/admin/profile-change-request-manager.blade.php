<div>
    <div class="flex items-center justify-between gap-3 border-b bg-white p-4">
        <h3 class="text-lg font-semibold text-gray-900">Wnioski o zmianę profilu</h3>
        <select wire:model.live="statusFilter" class="rounded-md border-gray-300 text-sm">
            <option value="pending">Oczekujące</option>
            <option value="approved">Zaakceptowane</option>
            <option value="rejected">Odrzucone</option>
            <option value="all">Wszystkie</option>
        </select>
    </div>

    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-300 bg-white">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Użytkownik</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Proponowane zmiany</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Status</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Akcje</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($requests as $request)
                <tr>
                    <td class="px-4 py-3 align-top text-sm text-gray-900">{{ $request->user->name }}<br><span class="text-xs text-gray-500">{{ $request->user->email }}</span></td>
                    <td class="px-4 py-3 align-top text-xs text-gray-700">
                        @if(filled($request->proposed_data['avatar_path'] ?? null))
                            <p class="mb-1 font-semibold">Nowe zdjęcie:</p>
                            <img src="{{ \App\Support\Media::url($request->proposed_data['avatar_path']) }}" alt="" class="aspect-[4/5] w-24 rounded-lg object-cover ring-1 ring-gray-200">
                        @endif
                        @php $changes = \App\Livewire\Admin\ProfileChangeRequestManager::changes($request); @endphp
                        @if($changes !== [])
                            <dl class="max-w-3xl space-y-3">
                                @foreach($changes as $change)
                                    <div @class(['opacity-60' => ! $change['changed']])>
                                        <dt class="text-[11px] font-semibold tracking-wide text-gray-500 uppercase">{{ $change['label'] }} @unless($change['changed'])<span class="font-normal normal-case">· bez zmian</span>@endunless</dt>
                                        @if($change['changed'] && $change['current'] !== null)
                                            <dd class="mt-0.5 text-sm text-gray-500 line-through decoration-red-300 [&_p]:my-0.5">
                                                @if($change['html']){!! strip_tags($change['current'], '<p><br><strong><em><b><i><ul><ol><li>') !!}@else<span class="whitespace-pre-line">{{ $change['current'] }}</span>@endif
                                            </dd>
                                        @endif
                                        <dd @class(['mt-0.5 text-sm [&_p]:my-0.5', 'rounded-md bg-green-50 px-2 py-1 text-gray-900 ring-1 ring-green-200' => $change['changed'], 'text-gray-700' => ! $change['changed']])>
                                            @if($change['proposed'] === null)
                                                <span class="text-gray-400 italic">(puste)</span>
                                            @elseif($change['html'])
                                                {!! strip_tags($change['proposed'], '<p><br><strong><em><b><i><ul><ol><li>') !!}
                                            @else
                                                <span class="whitespace-pre-line break-all">{{ $change['proposed'] }}</span>
                                            @endif
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <span @class(['rounded-full px-2 py-0.5 text-xs font-semibold', 'bg-amber-100 text-amber-800' => $request->status === 'pending', 'bg-green-100 text-green-800' => $request->status === 'approved', 'bg-red-100 text-red-800' => $request->status === 'rejected'])>{{ ['pending' => 'Oczekuje', 'approved' => 'Zaakceptowany', 'rejected' => 'Odrzucony'][$request->status] ?? $request->status }}</span>
                        <span class="mt-1 block text-xs text-gray-500">{{ $request->created_at?->format('d.m.Y H:i') }}</span>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        @if($request->status === 'pending')
                            <button wire:click="selectForReview({{ $request->id }})" class="text-blue-700 hover:underline">Oceń</button>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>

    <div class="p-4">{{ $requests->links() }}</div>

    @if($reviewingId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-lg rounded-xl bg-white p-6">
                <h4 class="mb-3 text-lg font-semibold">Decyzja dla wniosku #{{ $reviewingId }}</h4>
                <textarea wire:model="reviewNote" rows="4" placeholder="Notatka (opcjonalnie)" class="w-full rounded-md border-gray-300"></textarea>
                <div class="mt-4 flex justify-end gap-2">
                    <button wire:click="$set('reviewingId', null)" class="rounded-md border px-4 py-2">Anuluj</button>
                    <button wire:click="reject({{ $reviewingId }})" class="rounded-md bg-red-600 px-4 py-2 text-white">Odrzuć</button>
                    <button wire:click="approve({{ $reviewingId }})" class="rounded-md bg-green-600 px-4 py-2 text-white">Akceptuj</button>
                </div>
            </div>
        </div>
    @endif
</div>

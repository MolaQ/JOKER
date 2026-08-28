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
                    <td class="px-4 py-3 text-sm text-gray-900">{{ $request->user->name }}<br><span class="text-xs text-gray-500">{{ $request->user->email }}</span></td>
                    <td class="px-4 py-3 text-xs text-gray-700"><pre class="whitespace-pre-wrap">{{ json_encode($request->proposed_data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></td>
                    <td class="px-4 py-3 text-sm">{{ $request->status }}</td>
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

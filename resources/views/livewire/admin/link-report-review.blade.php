<div class="space-y-3">
    @forelse($reports as $row)
        @php($report = $row['report'])
        <div wire:key="link-report-{{ $report->id }}" class="admin-card p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900">{{ $report->reportable?->mediaLabel() ?? 'Mecz albo turniej został usunięty' }}</p>
                    <a href="{{ $report->url }}" target="_blank" rel="noopener" class="mt-1 block truncate text-sm text-sky-700 hover:underline">{{ $report->url }} ↗</a>
                    <p class="mt-2 text-xs text-gray-500">Zgłosił(a): {{ $row['users']->implode(', ') ?: '—' }} · {{ $row['last']->format('d.m.Y H:i') }}</p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <button type="button" wire:click="remove({{ $report->id }})" wire:confirm="Odpiąć ten link od strony?" class="rounded-md bg-red-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-red-700">Odepnij link</button>
                    <button type="button" wire:click="dismiss({{ $report->id }})" class="rounded-md px-3 py-1.5 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Działa</button>
                </div>
            </div>
        </div>
    @empty
        <p class="admin-card p-6 text-sm text-gray-500">Nikt nie zgłosił niedziałającego linku.</p>
    @endforelse
</div>

<div style="position:sticky;top:0;z-index:9999" class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 bg-amber-400 px-4 py-2 text-center text-sm font-semibold text-amber-950 shadow">
    <span>Podgląd propozycji ({{ $proposal->typeLabel() }} od {{ $proposal->user?->name }}). Tak strona będzie wyglądać po zatwierdzeniu, na razie nikt inny jej nie widzi.</span>
    <a href="{{ route('admin.content-proposals.index') }}" class="rounded-md bg-amber-950 px-3 py-1 text-white hover:bg-black">← Wróć do zatwierdzania</a>
</div>

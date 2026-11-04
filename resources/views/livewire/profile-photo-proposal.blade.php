<div class="rounded-xl bg-white p-6 shadow">
    <h2 class="text-lg font-semibold text-zinc-900">{{ __('Zdjęcie na profilu') }}</h2>
    <p class="mt-1 text-sm text-zinc-500">{{ __('Zdjęcie pokaże się na Twoim profilu trenera po zatwierdzeniu przez klub.') }}</p>
    <form wire:submit="submit" class="mt-4 space-y-4">
        <div class="flex items-start gap-4">
            <div class="aspect-[4/5] w-28 shrink-0 overflow-hidden rounded-xl bg-zinc-100 ring-1 ring-zinc-200">
                @if($photo)
                    <img src="{{ $photo->temporaryUrl() }}" alt="{{ __('Nowe zdjęcie') }}" class="h-full w-full object-cover">
                @elseif($pending)
                    <img src="{{ \App\Support\Media::url($pending->proposed_data['avatar_path']) }}" alt="{{ __('Nowe zdjęcie') }}" class="h-full w-full object-cover">
                @elseif(auth()->user()->avatar_path)
                    <img src="{{ \App\Support\Media::url(auth()->user()->avatar_path) }}" alt="{{ __('Obecne zdjęcie') }}" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full items-center justify-center text-xs text-zinc-400">{{ __('brak zdjęcia') }}</span>
                @endif
            </div>
            <div class="min-w-0 flex-1 space-y-2">
                <x-photo-cropper model="photo" :preview="false" aspect="4/5" :width="800" />
                @if($pending && ! $photo)
                    <p class="text-xs font-semibold text-amber-700">{{ __('Zdjęcie czeka na zatwierdzenie.') }}</p>
                @endif
                @error('photo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        @if($photo)
            <button type="submit" class="rounded-lg bg-ink px-5 py-2 text-white hover:bg-graphite">{{ __('Wyślij zdjęcie do zatwierdzenia') }}</button>
        @endif
    </form>
</div>

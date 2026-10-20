<div>
    @if($canPropose)
        <div class="card p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-display text-xl font-semibold text-zinc-900 uppercase">To Twój profil?</p>
                    <p class="text-sm text-zinc-500">Zaproponuj aktualne dane lub nowe zdjęcie. Zmiany zatwierdza trener lub administrator.</p>
                </div>
                <button type="button" wire:click="toggle" class="btn-primary">{{ $open ? 'Zamknij' : 'Zaproponuj zmiany' }}</button>
            </div>

            @if($flashMessage)
                <p class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ $flashMessage }}</p>
            @endif

            @if($pending->isNotEmpty())
                <p class="mt-4 text-sm text-amber-700">Czeka na zatwierdzenie: {{ $pending->map(fn ($request) => $request->isPhoto() ? 'nowe zdjęcie' : 'zmiana danych')->unique()->join(', ', ' i ') }}.</p>
            @endif

            @if($open)
                <form wire:submit="submit" class="mt-6 space-y-5">
                    <div>
                        <p class="mb-2 text-sm font-semibold text-zinc-700">Zdjęcie profilowe</p>
                        <x-photo-cropper model="photo" :current="$player->photo_path ? Storage::url($player->photo_path) : null" :pending="$photo && ! $errors->has('photo') ? $photo->temporaryUrl() : null" />
                        @error('photo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        @foreach(\App\Models\PlayerChangeRequest::EDITABLE_FIELDS as $field => $label)
                            @continue($field === 'bio')
                            <label class="block text-sm">
                                <span class="font-medium text-zinc-700">{{ $label }}</span>
                                <input type="number" wire:model="data.{{ $field }}" class="mt-1 block w-full rounded-xl border-zinc-300">
                                @error('data.'.$field) <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                        @endforeach
                    </div>

                    <label class="block text-sm">
                        <span class="font-medium text-zinc-700">O mnie</span>
                        <textarea wire:model="data.bio" rows="4" class="mt-1 block w-full rounded-xl border-zinc-300"></textarea>
                        @error('data.bio') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>

                    @error('data') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="submit,photo">Wyślij do zatwierdzenia</button>
                </form>
            @endif
        </div>
    @endif
</div>

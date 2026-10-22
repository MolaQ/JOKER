<div>
    @if($canPropose)
        <div class="card p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-display text-xl font-semibold text-zinc-900 uppercase">{{ __('To Twój profil?') }}</p>
                    <p class="text-sm text-zinc-500">{{ __('Zaproponuj aktualne dane lub nowe zdjęcie. Zmiany zatwierdza trener lub administrator.') }}</p>
                </div>
                <button type="button" wire:click="toggle" class="btn-primary">{{ $open ? __('Zamknij') : __('Zaproponuj zmiany') }}</button>
            </div>

            @if($pending->isNotEmpty())
                <p class="mt-4 text-sm text-amber-700">{{ __('Czeka na zatwierdzenie') }}: {{ $pending->map(fn ($request) => $request->isPhoto() ? __('nowe zdjęcie') : __('zmiana danych'))->unique()->join(', ', ' '.__('i').' ') }}.</p>
            @endif

            @if($open)
                <form wire:submit="submit" class="mt-6 space-y-5">
                    <div>
                        <p class="mb-2 text-sm font-semibold text-zinc-700">{{ __('Zdjęcie profilowe') }}</p>
                        <x-photo-cropper model="photo" :current="$player->photo_path ? \App\Support\Media::url($player->photo_path) : null" :pending="$photo && ! $errors->has('photo') ? $photo->temporaryUrl() : null" />
                        @error('photo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        @foreach(\App\Models\PlayerChangeRequest::NUMERIC_FIELDS as $field)
                            @php $label = \App\Models\PlayerChangeRequest::EDITABLE_FIELDS[$field]; @endphp
                            <label class="block text-sm">
                                <span class="font-medium text-zinc-700">{{ __($label) }}</span>
                                <input type="number" wire:model="data.{{ $field }}" class="mt-1 block w-full rounded-xl border-zinc-300">
                                @error('data.'.$field) <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                        @endforeach
                    </div>

                    <label class="block text-sm">
                        <span class="font-medium text-zinc-700">{{ __('O mnie') }}</span>
                        <textarea wire:model="data.bio" rows="4" class="mt-1 block w-full rounded-xl border-zinc-300"></textarea>
                        @error('data.bio') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block text-sm">
                        <span class="font-medium text-zinc-700">{{ __('Zainteresowania') }}</span>
                        <input type="text" wire:model="data.interests" maxlength="500" placeholder="{{ __('np. gitara, góry, matematyka') }}" class="mt-1 block w-full rounded-xl border-zinc-300">
                        @error('data.interests') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>

                    @if($socialPlatforms)
                        <div>
                            <p class="text-sm font-medium text-zinc-700">{{ __('Media społecznościowe') }}</p>
                            <div class="mt-1 grid gap-3 sm:grid-cols-2">
                                @foreach($socialPlatforms as $platformKey => $platform)
                                    <label class="flex items-center gap-2 text-sm" wire:key="proposal-social-{{ $platformKey }}">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-700" title="{{ $platform['label'] }}"><x-social-icon :network="$platform['icon']" class="h-4 w-4" /></span>
                                        <input type="url" wire:model="data.social_links.{{ $platformKey }}" placeholder="{{ $platform['label'] }}: https://..." class="min-w-0 flex-1 rounded-xl border-zinc-300" aria-label="{{ $platform['label'] }}">
                                    </label>
                                    @error('data.social_links.'.$platformKey) <span class="block text-xs text-red-600 sm:col-span-2">{{ $message }}</span> @enderror
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @unless($player->user_id)
                        <label class="block text-sm">
                            <span class="font-medium text-zinc-700">{{ __('E-mail zawodnika') }}</span>
                            <input type="email" wire:model="data.email" placeholder="{{ __('np. adam.nowak@gmail.com') }}" class="mt-1 block w-full rounded-xl border-zinc-300">
                            <span class="mt-1 block text-xs text-zinc-500">{{ __('Po zatwierdzeniu zawodnik dostanie własne konto i link do ustawienia hasła.') }}</span>
                            @error('data.email') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>
                    @endunless

                    @error('data') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="submit,photo">{{ __('Wyślij do zatwierdzenia') }}</button>
                </form>
            @endif
        </div>
    @endif
</div>

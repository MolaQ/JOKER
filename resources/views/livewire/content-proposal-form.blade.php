@php $field = 'mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 shadow-sm focus:border-sky-club focus:ring-sky-club'; @endphp
<div class="rounded-xl bg-white p-6 shadow">
    @if($types === [])
        <p class="text-sm text-zinc-600">{{ __('Proponować treści mogą aktywne konta rodziców i zawodników.') }}</p>
    @else
        <form wire:submit="submit" class="space-y-5">
            <fieldset>
                <legend class="text-sm font-medium text-zinc-700">{{ __('Co chcesz zaproponować?') }}</legend>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach($types as $option)
                        <label class="cursor-pointer rounded-full border border-zinc-300 px-4 py-2 text-sm has-[:checked]:border-sky-club has-[:checked]:bg-sky-club/10 has-[:checked]:font-semibold">
                            <input type="radio" wire:model.live="type" value="{{ $option }}" class="sr-only">
                            {{ __(\App\Models\ContentProposal::TYPES[$option]) }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('Mecz') }} @if($type === 'article')<span class="text-zinc-400">({{ __('opcjonalnie') }})</span>@endif</label>
                <select wire:model="match" class="{{ $field }}">
                    <option value="">{{ __('Wybierz mecz') }}</option>
                    @foreach($matchOptions as $group => $options)
                        <optgroup label="{{ $group }}">
                            @foreach($options as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('match')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            @if($type === 'article')
                <div>
                    <label class="block text-sm font-medium text-zinc-700">{{ __('Tytuł') }}</label>
                    <input type="text" wire:model="title" class="{{ $field }}">
                    @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700">{{ __('Treść') }}</label>
                    <textarea wire:model="content" rows="10" class="{{ $field }}" placeholder="{{ __('Napisz relację, zapowiedź albo ciekawostkę. Akapity oddziel pustą linią.') }}"></textarea>
                    @error('content')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            @endif

            @if($type === 'stream')
                <div>
                    <label class="block text-sm font-medium text-zinc-700">{{ __('Adres transmisji (YouTube, Facebook, Twitch)') }}</label>
                    <input type="url" wire:model="url" placeholder="https://" class="{{ $field }}">
                    @error('url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            @else
                <div>
                    <label class="block text-sm font-medium text-zinc-700">{{ $type === 'article' ? __('Zdjęcie do artykułu (opcjonalnie)') : __('Zdjęcia') }}</label>
                    <input type="file" wire:model="photos" multiple accept="image/*" class="mt-1 block w-full text-sm">
                    <div wire:loading wire:target="photos" class="mt-1 text-sm text-zinc-500">{{ __('Wysyłanie zdjęć...') }}</div>
                    @if($photos)
                        <p class="mt-1 text-sm text-zinc-600">{{ trans_choice(':count zdjęcie|:count zdjęcia|:count zdjęć', count($photos), ['count' => count($photos)]) }}</p>
                    @endif
                    @error('photos')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    @error('photos.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            @endif

            <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-ink px-5 py-2 font-semibold text-white hover:bg-graphite disabled:opacity-60">{{ __('Wyślij do zatwierdzenia') }}</button>
        </form>
    @endif
</div>

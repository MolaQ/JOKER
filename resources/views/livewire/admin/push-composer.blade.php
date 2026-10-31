<div class="space-y-6">
    @unless($enabled)
        <p class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">Powiadomienia są wyłączone w <a href="{{ route('admin.settings.index') }}" class="font-semibold underline">ustawieniach systemu</a> (Powiadomienia na telefon). Po włączeniu wybory kibiców wrócą bez zmian.</p>
    @endunless

    <form wire:submit="send" class="space-y-4 rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200">
        <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900"><x-admin-icon name="bell" class="text-zinc-400" /> Wyślij powiadomienie</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="push-title" class="block text-sm font-medium text-gray-700">Tytuł</label>
                <input id="push-title" type="text" wire:model="title" maxlength="80" placeholder="np. Zmiana godziny meczu" class="mt-1 block w-full">
                @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="push-target" class="block text-sm font-medium text-gray-700">Do kogo</label>
                <select id="push-target" wire:model.live="target" class="mt-1 block w-full">
                    <option value="all">Wszyscy z włączonymi powiadomieniami</option>
                    <optgroup label="Kibice, którzy wybrali temat">
                        @foreach(\App\Models\PushSubscription::TOPICS as $topic => $topicLabel)
                            <option value="topic:{{ $topic }}">{{ $topicLabel }}</option>
                        @endforeach
                    </optgroup>
                    @if($teams->isNotEmpty())
                        <optgroup label="Kibice drużyny">
                            @foreach($teams as $team)
                                <option value="team:{{ $team->id }}">{{ $team->name }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                </select>
            </div>
            <div class="sm:col-span-2">
                <label for="push-body" class="block text-sm font-medium text-gray-700">Treść</label>
                <textarea id="push-body" wire:model="body" rows="2" maxlength="200" placeholder="np. Sobotni mecz kadetek zaczyna się o 12:00, nie o 11:00." class="mt-1 block w-full"></textarea>
                @error('body') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="push-url" class="block text-sm font-medium text-gray-700">Link po kliknięciu (opcjonalnie)</label>
                <input id="push-url" type="text" wire:model="url" placeholder="np. /tournaments/memorial albo https://…; puste = strona główna" class="mt-1 block w-full">
                @error('url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-4">
            <button type="submit" wire:confirm="Wysłać powiadomienie na {{ $recipients }} urządzeń? Tego nie da się cofnąć." @disabled(! $enabled) class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700 disabled:opacity-50">Wyślij</button>
            <p class="text-sm text-gray-600">Dostanie: <strong>{{ $recipients }}</strong> z {{ $total }} urządzeń z włączonymi powiadomieniami.</p>
        </div>
    </form>

    <p class="text-sm text-gray-600">Automatyczne powiadomienia (aktualności, wyniki, galerie, turnieje, przypomnienia przed meczem) i ich treść ustawisz w <a href="{{ route('admin.settings.index') }}" class="font-medium text-sky-700 hover:text-sky-900">ustawieniach systemu</a>: „Powiadomienia na telefon” i „Powiadomienia: treść”.</p>
</div>

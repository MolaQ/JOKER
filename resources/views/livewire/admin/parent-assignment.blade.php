<div class="space-y-6">
    @forelse($groups as $title => $accounts)
        <section>
            <h2 class="mb-2 text-sm font-semibold tracking-wide text-gray-500 uppercase">{{ $title }} <span class="text-gray-400">({{ $accounts->count() }})</span></h2>
            <ul class="admin-card divide-y divide-gray-100">
                @foreach($accounts as $account)
                    @php $matches = $suggestions[$account->id]; @endphp
                    <li class="p-4" wire:key="parent-{{ $account->id }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 text-sm">
                                <p class="font-semibold text-gray-900">{{ $account->name }} <span class="font-normal break-all text-gray-500">{{ $account->email }}</span></p>
                                <p class="mt-0.5 text-gray-600">
                                    @if($account->requestedTeam)Drużyna: <span class="font-medium text-gray-800">{{ $account->requestedTeam->name }}</span> · @endif
                                    {{ $account->is_active ? 'konto aktywne' : 'zgłoszenie z '.$account->created_at->format('d.m.Y') }}
                                    @unless($account->is_active)
                                        · @if($account->hasVerifiedEmail())<span class="text-green-700">e-mail potwierdzony</span>@else<span class="text-amber-700">e-mail niepotwierdzony</span>@endif
                                    @endunless
                                </p>
                            </div>
                            @unless($account->is_active)
                                <div class="flex gap-2">
                                    <button type="button" wire:click="startRejecting({{ $account->id }})" class="rounded-md px-3 py-1.5 text-sm font-semibold text-red-600 ring-1 ring-red-200 hover:bg-red-50">Odrzuć</button>
                                    <button type="button" wire:click="deleteSpam({{ $account->id }})" wire:confirm="Usunąć konto {{ $account->name }} jako spam? Tego nie da się cofnąć." class="rounded-md px-2 py-1.5 text-xs text-gray-500 hover:text-red-700" title="Konto założone przez robota albo dla żartu">Usuń (spam)</button>
                                </div>
                            @endunless
                        </div>

                        @if($account->registration_note)
                            <div class="mt-3 rounded-lg bg-amber-50/70 px-3 py-2 text-sm ring-1 ring-amber-100">
                                <p class="text-xs font-semibold text-amber-800">Rodzic napisał o dziecku:</p>
                                <p class="mt-0.5 text-gray-900">„{{ $account->registration_note }}”</p>
                            </div>
                        @endif

                        @if($account->children->isNotEmpty())
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                                <span class="text-gray-600">Przypisane dzieci:</span>
                                @foreach($account->children as $child)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 py-0.5 pr-1 pl-2.5 text-emerald-800 ring-1 ring-emerald-200" wire:key="child-{{ $account->id }}-{{ $child->id }}">
                                        {{ $child->full_name }}@if($child->team) <span class="text-emerald-600">({{ $child->team->name }})</span>@endif
                                        <button type="button" wire:click="unassign({{ $account->id }}, {{ $child->id }})" wire:confirm="Odpiąć {{ $child->full_name }} od konta {{ $account->name }}?" class="rounded-full px-1 text-emerald-500 hover:bg-emerald-100 hover:text-red-600" title="Odepnij" aria-label="Odepnij {{ $child->full_name }}">✕</button>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            @if($matches->isNotEmpty() && ! $matches->first()['sure'])
                                <span class="text-sm text-gray-600">Zgadza się tylko nazwisko, sprawdź:</span>
                            @endif
                            @foreach($matches as ['player' => $player, 'sure' => $sure])
                                <button type="button" wire:click="assign({{ $account->id }}, {{ $player->id }})" wire:loading.attr="disabled" @class([
                                    'rounded-md px-3 py-2 text-sm font-semibold shadow-xs',
                                    'bg-sky-600 text-white hover:bg-sky-700' => $sure,
                                    'bg-white text-sky-800 ring-1 ring-sky-300 hover:bg-sky-50' => ! $sure,
                                ])>
                                    {{ ($account->children->isEmpty() ? 'Przypisz do: ' : 'Dodaj dziecko: ').$player->full_name.($player->team ? ' ('.$player->team->name.')' : '') }}
                                </button>
                            @endforeach
                            @if($matches->isEmpty() && $account->children->isEmpty())
                                <span class="text-sm text-gray-500">{{ $account->registration_note ? 'Nie rozpoznałem zawodnika z opisu.' : 'Rodzic nie napisał, czyje to dziecko.' }} Wybierz zawodnika z listy:</span>
                            @endif
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <x-search-select model="otherChild.{{ $account->id }}" class="w-full sm:w-80" empty="— inne dziecko —" placeholder="{{ $matches->isEmpty() ? 'Szukaj zawodnika…' : 'Inne dziecko? Szukaj…' }}" :options="$playerOptions" />
                            <button type="button" wire:click="assign({{ $account->id }})" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50">Przypisz wybrane</button>
                        </div>

                        @if($rejectingId === $account->id)
                            <form wire:submit="reject" class="mt-4 space-y-3 rounded-lg border border-red-200 bg-red-50/40 p-4">
                                <div>
                                    <label for="rejection-reason" class="block text-sm font-medium text-gray-700">Powód odrzucenia (zobaczy go ta osoba w e-mailu i w „Mój profil”)</label>
                                    <textarea id="rejection-reason" wire:model="rejectionReason" rows="2" class="mt-1 w-full text-sm"></textarea>
                                    @error('rejectionReason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach(['Nie znaleźliśmy tego zawodnika. Podaj pełne imię i nazwisko dziecka oraz drużynę.', 'Wybrana drużyna się nie zgadza. Wybierz drużynę, w której trenuje dziecko.'] as $template)
                                            <button type="button" wire:click="$set('rejectionReason', @js($template))" class="rounded-full bg-white px-2.5 py-1 text-xs text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50">{{ \Illuminate\Support\Str::limit($template, 48) }}</button>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Odrzuć z tym powodem</button>
                                    <button type="button" wire:click="cancelRejecting" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50">Anuluj</button>
                                </div>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <div class="admin-card p-6 text-center text-sm text-gray-500">Wszyscy rodzice są przypisani do dzieci.</div>
    @endforelse
</div>

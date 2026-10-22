<div class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-black/5">
    <ul class="divide-y divide-gray-200">
        @forelse($pending as $account)
            <li class="p-4" wire:key="pending-{{ $account->id }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="text-sm">
                        <p class="font-semibold text-gray-900">{{ $account->name }} <span class="font-normal text-gray-500">{{ $account->email }}</span></p>
                        <p class="mt-1 text-gray-700">
                            {{ $account->requested_role?->label() ?? 'bez deklaracji' }}
                            @if($account->requestedTeam) · {{ $account->requestedTeam->name }} @endif
                            · {{ $account->created_at->format('d.m.Y H:i') }}
                            · @if($account->hasVerifiedEmail())<span class="text-green-700">e-mail potwierdzony</span>@else<span class="text-amber-700">e-mail niepotwierdzony</span>@endif
                        </p>
                        @if($account->registration_note)
                            <p class="mt-1 rounded bg-gray-50 px-2 py-1 text-gray-700">„{{ $account->registration_note }}”</p>
                        @endif
                    </div>
                    @if($activatingId !== $account->id)
                        <div class="flex gap-2">
                            <button type="button" wire:click="start({{ $account->id }})" class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-700">Aktywuj i powiąż</button>
                            <button type="button" wire:click="startRejecting({{ $account->id }})" class="rounded-md px-3 py-1.5 text-sm font-semibold text-red-600 ring-1 ring-red-200 hover:bg-red-50">Odrzuć</button>
                            <button type="button" wire:click="deleteSpam({{ $account->id }})" wire:confirm="Usunąć konto {{ $account->name }} jako spam? Tego nie da się cofnąć." class="rounded-md px-2 py-1.5 text-xs text-gray-500 hover:text-red-700" title="Konto założone przez robota albo dla żartu">Usuń (spam)</button>
                        </div>
                    @endif
                </div>

                @if($rejectingId === $account->id)
                    <form wire:submit="reject" class="mt-4 space-y-3 rounded-lg border border-red-200 bg-red-50/40 p-4">
                        <div>
                            <label for="rejection-reason" class="block text-sm font-medium text-gray-700">Powód odrzucenia (zobaczy go ta osoba w e-mailu i w „Mój profil”)</label>
                            <textarea id="rejection-reason" wire:model="rejectionReason" rows="2" placeholder="np. Nie znaleźliśmy zawodnika o tym nazwisku w drużynie Kadeci. Podaj pełne imię i nazwisko dziecka." class="mt-1 w-full text-sm"></textarea>
                            @error('rejectionReason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach(['Nie rozpoznajemy tej osoby. Podaj pełne imię i nazwisko oraz drużynę.', 'Podaj imię i nazwisko dziecka, które trenuje w klubie.', 'Wybrana drużyna się nie zgadza. Wybierz drużynę, w której trenujesz.', 'Konto trenera zakłada klub. Skontaktuj się z nami telefonicznie.'] as $template)
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

                @if($activatingId === $account->id)
                    <form wire:submit="activate" class="mt-4 space-y-4 rounded-lg border border-blue-200 bg-blue-50/40 p-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Rola</label>
                            <select wire:model.live="role" class="mt-1 rounded-md border-gray-300 text-sm">
                                @foreach($this->rolesToGrant() as $option)
                                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                @endforeach
                            </select>
                            @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        @if(in_array($role, ['player', 'parent'], true))
                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ $role === 'player' ? 'Profil zawodnika' : 'Dzieci (zaznacz wszystkie)' }}</label>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <select wire:model.live="teamId" class="rounded-md border-gray-300 text-sm">
                                        <option value="">Wszystkie drużyny</option>
                                        @foreach($teams as $team)
                                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="search" wire:model.live.debounce.300ms="playerSearch" placeholder="Szukaj po nazwisku..." class="rounded-md border-gray-300 text-sm">
                                </div>
                                <div class="mt-2 grid max-h-64 gap-1 overflow-y-auto rounded-md border border-gray-200 bg-white p-2 sm:grid-cols-2">
                                    @forelse($players as $player)
                                        <label class="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-gray-50" wire:key="pick-{{ $player->id }}">
                                            @if($role === 'player')
                                                <input type="radio" wire:model="playerId" value="{{ $player->id }}" @disabled($player->user_id) class="text-blue-600">
                                            @else
                                                <input type="checkbox" wire:model="childIds" value="{{ $player->id }}" class="rounded text-blue-600">
                                            @endif
                                            <span>{{ $player->full_name }} <span class="text-gray-500">{{ $player->team?->name }}@if($role === 'player' && $player->user_id) · ma już konto @endif</span></span>
                                        </label>
                                    @empty
                                        <p class="px-2 py-1 text-sm text-gray-500">Brak zawodników. Zmień drużynę albo wyszukiwanie.</p>
                                    @endforelse
                                </div>
                                @error('playerId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                @error('childIds')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        @elseif(in_array($role, ['trainer', 'staff'], true))
                            <div class="grid gap-3 sm:grid-cols-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Drużyna</label>
                                    <select wire:model="teamId" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                        <option value="">Wybierz</option>
                                        @foreach($teams as $team)
                                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('teamId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Funkcja</label>
                                    <input type="text" wire:model="function" placeholder="np. asystent trenera" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                </div>
                                <label class="flex items-end gap-2 pb-2 text-sm text-gray-700">
                                    <input type="checkbox" wire:model="headCoach" class="rounded text-blue-600"> Trener główny
                                </label>
                            </div>
                        @endif

                        <div class="flex gap-2">
                            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Aktywuj konto</button>
                            <button type="button" wire:click="cancel" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50">Anuluj</button>
                        </div>
                    </form>
                @endif
            </li>
        @empty
            <li class="p-6 text-center text-sm text-gray-500">Nie ma kont czekających na aktywację.</li>
        @endforelse
    </ul>
</div>

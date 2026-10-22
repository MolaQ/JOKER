{{-- Uprawnienia jednego konta: wynik z ról i wyjątki. Zmienne: $user, $permissionGroups, $rolePermissions, $overrides, $editable --}}
@if($user->isAdmin())
    <p class="rounded-md bg-sky-50 p-3 text-sm text-sky-900">Administrator ma zawsze wszystkie uprawnienia.</p>
@else
    @if(! $user->is_active)
        <p class="mb-3 rounded-md bg-amber-50 p-3 text-sm text-amber-800">Konto nie jest aktywne, więc teraz nie działa żadne uprawnienie. Poniżej to, co będzie miało po aktywacji.</p>
    @endif
    @unless($editable)
        <p class="mb-3 rounded-md bg-gray-50 p-3 text-sm text-gray-600">{{ $user->id === auth()->id() ? 'Własnych uprawnień nie można sobie zmieniać.' : 'Wyjątki ustawia osoba z uprawnieniem „Role i uprawnienia”.' }}</p>
    @endunless
    <div class="divide-y divide-gray-100 overflow-hidden rounded-lg border border-gray-200">
        @foreach($permissionGroups as $group => $permissions)
            <p class="bg-gray-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $group }}</p>
            @foreach($permissions as $permission)
                @php
                    $fromRole = in_array($permission->value, $rolePermissions, true);
                    $mode = array_key_exists($permission->value, $overrides) ? ($overrides[$permission->value] ? 'grant' : 'deny') : 'role';
                    $effective = $mode === 'role' ? $fromRole : $mode === 'grant';
                @endphp
                <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 text-sm" wire:key="override-{{ $user->id }}-{{ $permission->value }}">
                    <span class="flex min-w-0 items-center gap-2">
                        <span @class(['inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full', 'bg-emerald-100 text-emerald-700' => $effective, 'bg-gray-100 text-gray-400' => ! $effective]) title="{{ $effective ? 'Ma' : 'Nie ma' }}">
                            <x-admin-icon :name="$effective ? 'check' : 'close'" class="h-3 w-3" />
                        </span>
                        <span class="min-w-0">
                            <span @class(['text-gray-900', 'line-through decoration-red-400' => $mode === 'deny'])>{{ $permission->label() }}</span>
                            @if($mode === 'deny')<span class="ml-1 rounded bg-red-50 px-1.5 py-0.5 text-[11px] font-semibold text-red-700">odebrane</span>@endif
                            @if($mode === 'grant')<span class="ml-1 rounded bg-emerald-50 px-1.5 py-0.5 text-[11px] font-semibold text-emerald-700">nadane osobno</span>@endif
                        </span>
                    </span>
                    @if($editable)
                        <select wire:change="setOverride('{{ $permission->value }}', $event.target.value)" class="rounded-md border-gray-300 py-1 text-sm" aria-label="{{ $permission->label() }}">
                            <option value="role" @selected($mode === 'role')>Jak w roli ({{ $fromRole ? 'tak' : 'nie' }})</option>
                            <option value="grant" @selected($mode === 'grant')>Nadaj</option>
                            <option value="deny" @selected($mode === 'deny')>Odbierz</option>
                        </select>
                    @else
                        <span class="text-xs text-gray-500">{{ ['role' => 'z roli', 'grant' => 'nadane', 'deny' => 'odebrane'][$mode] }}</span>
                    @endif
                </div>
            @endforeach
        @endforeach
    </div>
@endif

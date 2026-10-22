<div class="space-y-8">
    <section class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-zinc-200">
        <h2 class="border-b border-gray-200 px-4 py-3 text-lg font-semibold text-gray-900">Co może każda rola</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="sticky left-0 bg-gray-50 py-3 pl-4 pr-3 text-left font-semibold text-gray-900">Uprawnienie</th>
                        @foreach($roles as $role)
                            <th class="px-2 py-3 text-center font-semibold text-gray-900" title="{{ $role->description() }}">{{ $role->label() }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($permissionGroups as $group => $permissions)
                        <tr class="bg-gray-50/60"><td colspan="{{ count($roles) + 1 }}" class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $group }}</td></tr>
                        @foreach($permissions as $permission)
                            <tr wire:key="perm-{{ $permission->value }}">
                                <td class="sticky left-0 bg-white py-2 pl-4 pr-3">
                                    <span class="font-medium text-gray-900">{{ $permission->label() }}</span>
                                    @if($permission->hint())<span class="block text-xs text-gray-500">{{ $permission->hint() }}</span>@endif
                                </td>
                                @foreach($roles as $role)
                                    @php $checked = $role === \App\UserRole::Admin || in_array($permission->value, $map[$role->value] ?? [], true); @endphp
                                    <td class="px-2 py-2 text-center">
                                        <input type="checkbox" aria-label="{{ $role->label() }}: {{ $permission->label() }}"
                                            @checked($checked)
                                            @disabled(! $this->mayEditRole($role))
                                            wire:click="toggle('{{ $role->value }}', '{{ $permission->value }}')"
                                            class="h-4 w-4 rounded border-gray-300 text-sky-700 disabled:opacity-50">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="border-t border-gray-200 px-4 py-3 text-xs text-gray-500">Administrator ma zawsze wszystko. Uprawnienia szefa zmienia tylko administrator. Rodzic, zawodnik i kibic korzystają ze strony (profil, propozycje, komentarze); panel klubu dostają dopiero po zaznaczeniu czegoś tutaj.</p>
    </section>

    <section class="rounded-lg bg-white p-4 shadow ring-1 ring-zinc-200">
        <h2 class="text-lg font-semibold text-gray-900">Wyjątki dla jednego konta</h2>
        <p class="mt-1 text-sm text-gray-600">Np. rodzic, który pomaga przy transmisjach, dostaje tylko „Transmisje i galerie”, bez zmiany roli.</p>

        <div class="relative mt-4 max-w-md">
            <input type="search" wire:model.live.debounce.300ms="userSearch" placeholder="Szukaj konta po nazwisku lub e-mailu..." class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            @if($matches->isNotEmpty())
                <ul class="absolute z-10 mt-1 w-full overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-black/10">
                    @foreach($matches as $match)
                        <li wire:key="match-{{ $match->id }}">
                            <button type="button" wire:click="selectUser({{ $match->id }})" class="block w-full px-3 py-2 text-left text-sm hover:bg-blue-50">
                                {{ $match->name }} <span class="text-gray-500">{{ $match->email }}</span>
                                <span class="block text-xs text-gray-500">{{ $match->roles()->map->label()->join(', ') ?: 'bez roli' }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if($user)
            <div class="mt-5">
                <p class="font-semibold text-gray-900">{{ $user->name }} <span class="font-normal text-gray-500">({{ $user->roles()->map->label()->join(', ') ?: 'bez roli' }})</span></p>
                @if(! $this->mayEditUser($user))
                    <p class="mt-2 rounded-md bg-amber-50 p-3 text-sm text-amber-800">{{ $user->isAdmin() ? 'Administrator ma zawsze wszystkie uprawnienia.' : 'Własnych uprawnień nie można sobie zmieniać.' }}</p>
                @else
                    <div class="mt-3 divide-y divide-gray-100 rounded-md border border-gray-200">
                        @foreach($permissionGroups->flatten() as $permission)
                            @php
                                $fromRole = in_array($permission->value, $rolePermissions, true);
                                $mode = array_key_exists($permission->value, $overrides) ? ($overrides[$permission->value] ? 'grant' : 'deny') : 'role';
                            @endphp
                            <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 text-sm" wire:key="override-{{ $user->id }}-{{ $permission->value }}">
                                <span class="text-gray-900">{{ $permission->label() }}</span>
                                <select wire:change="setOverride('{{ $permission->value }}', $event.target.value)" class="rounded-md border-gray-300 py-1 text-sm">
                                    <option value="role" @selected($mode === 'role')>Jak w roli ({{ $fromRole ? 'tak' : 'nie' }})</option>
                                    <option value="grant" @selected($mode === 'grant')>Nadaj</option>
                                    <option value="deny" @selected($mode === 'deny')>Odbierz</option>
                                </select>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </section>

    <section class="rounded-lg bg-white p-4 shadow ring-1 ring-zinc-200">
        <h2 class="text-lg font-semibold text-gray-900">Ostatnie zmiany</h2>
        <ul class="mt-3 divide-y divide-gray-100 text-sm">
            @forelse($log as $entry)
                <li class="py-2" wire:key="log-{{ $entry->id }}">
                    <span class="text-gray-900">{{ $entry->description }}</span>
                    @if(! empty($entry->details['dodane']) || ! empty($entry->details['odebrane']))
                        <span class="text-gray-600">
                            @if(! empty($entry->details['dodane'])) + {{ implode(', ', $entry->details['dodane']) }} @endif
                            @if(! empty($entry->details['odebrane'])) − {{ implode(', ', $entry->details['odebrane']) }} @endif
                        </span>
                    @endif
                    <span class="block text-xs text-gray-500">{{ $entry->actor?->name ?? 'system' }} · {{ $entry->created_at->format('d.m.Y H:i') }}</span>
                </li>
            @empty
                <li class="py-2 text-gray-500">Brak zmian.</li>
            @endforelse
        </ul>
    </section>
</div>

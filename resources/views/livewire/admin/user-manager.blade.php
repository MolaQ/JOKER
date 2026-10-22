<div>
    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 bg-white border-b border-gray-200">
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Szukaj użytkownika..."
                class="w-full sm:w-64 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            >
            <select wire:model.live="roleFilter" class="w-full sm:w-48 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="">Wszystkie role</option>
                <option value="none">Bez roli</option>
                @foreach($roleOptions as $r)
                    <option value="{{ $r->value }}">{{ $r->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="statusFilter" class="w-full sm:w-52 text-sm" aria-label="Stan konta">
                <option value="">Każdy stan konta</option>
                @foreach($statusOptions as $statusOption)
                    <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" wire:click="openCreateModal" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            + Dodaj użytkownika
        </button>
    </div>

    <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
            <tr>
                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Imię i nazwisko</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Email</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Role</th>
                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Akcje</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
            @forelse($users as $user)
                <tr wire:key="user-{{ $user->id }}">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                        {{ $user->name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        {{ $user->email }}
                    </td>
                    <td class="px-3 py-4 text-sm">
                        <div class="flex flex-wrap gap-1">
                            @forelse($user->roles() as $userRole)
                                <span @class([
                                    'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                                    'bg-red-50 text-red-700 ring-red-700/10' => $userRole === \App\UserRole::Admin,
                                    'bg-blue-50 text-blue-700 ring-blue-700/10' => $userRole !== \App\UserRole::Admin,
                                ])>{{ $userRole->label() }}</span>
                            @empty
                                <span class="text-xs text-gray-400">bez roli</span>
                            @endforelse
                            @php $accountStatus = $user->accountStatus(); @endphp
                            @if($accountStatus !== \App\AccountStatus::Active)
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $accountStatus->badge() }}" @if($accountStatus === \App\AccountStatus::Rejected && $user->rejection_reason) title="Powód: {{ $user->rejection_reason }}" @endif>{{ $accountStatus->label() }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <button type="button" wire:click="openEditModal({{ $user->id }})" class="text-blue-600 hover:text-blue-900 mr-4">Edytuj</button>
                        <button type="button" wire:click="delete({{ $user->id }})" wire:confirm="Czy na pewno chcesz usunąć użytkownika {{ $user->name }}?" class="text-red-600 hover:text-red-900">Usuń</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-3 py-4 text-sm text-gray-500 text-center">Brak użytkowników do wyświetlenia</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4 px-4 pb-4">
        {{ $users->links() }}
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeModal"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg" @click.stop>
                    <form wire:submit.prevent="save">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $editingId ? 'Edytuj użytkownika' : 'Dodaj użytkownika' }}
                            </h3>
                        </div>

                        <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Imię i nazwisko</label>
                                <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" wire:model="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Hasło {{ $editingId ? '(zostaw puste, aby nie zmieniać)' : '' }}
                                </label>
                                <input type="password" wire:model="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-blue-600">
                                Konto aktywne (może się logować i korzystać z uprawnień)
                            </label>
                            @error('is_active') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                            <fieldset>
                                <legend class="block text-sm font-medium text-gray-700">Role (można zaznaczyć kilka)</legend>
                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                    @foreach($assignableRoles as $r)
                                        <label class="flex items-start gap-2 rounded-md border border-gray-200 p-2 text-sm hover:bg-gray-50">
                                            <input type="checkbox" wire:model="selectedRoles" value="{{ $r->value }}" class="mt-0.5 rounded border-gray-300 text-blue-600">
                                            <span><span class="font-medium text-gray-900">{{ $r->label() }}</span><span class="block text-xs text-gray-500">{{ $r->description() }}</span></span>
                                        </label>
                                    @endforeach
                                </div>
                                @can('roles.manage')
                                    <p class="mt-1 text-xs text-gray-500">Co dokładnie może każda rola, ustawisz w sekcji <a href="{{ route('admin.roles.index') }}" class="text-blue-600 underline">Role i uprawnienia</a>.</p>
                                @endcan
                                @error('selectedRoles') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                @error('selectedRoles.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </fieldset>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Data urodzenia</label>
                                    <input type="date" wire:model="birth_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('birth_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Urodziny w kalendarzu 🎂</label>
                                    <select wire:model="birthday_display" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        @foreach(\App\BirthdayDisplay::cases() as $mode)
                                            <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Dotyczy trenerów i sztabu.</p>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
                            <button type="button" wire:click="closeModal" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Anuluj
                            </button>
                            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                                <span wire:loading.remove wire:target="save">Zapisz</span>
                                <span wire:loading wire:target="save">Zapisywanie...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

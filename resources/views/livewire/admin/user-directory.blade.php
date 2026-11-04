<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-zinc-600">
            @if ($allTeams)
                {{ __('Wszystkie konta w klubie.') }}
            @else
                {{ __('Zawodnicy i rodzice z drużyn, które prowadzisz.') }}
            @endif
            {{ __('Tylko do odczytu: role i hasła zmienia szef klubu lub administrator.') }}
        </p>
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Szukaj po nazwisku lub e-mailu') }}"
            aria-label="{{ __('Szukaj osób') }}" class="block w-full rounded-lg border border-zinc-300 text-sm sm:w-72">
    </div>

    <ul class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 bg-white">
        @forelse ($users as $user)
            @php
                $status = $user->accountStatus();
                $player = $user->playerProfile && ($allTeams || $teamIds->contains($user->playerProfile->team_id)) ? $user->playerProfile : null;
                $children = $user->children->filter(fn ($child) => $allTeams || $teamIds->contains($child->team_id));
            @endphp
            <li wire:key="osoba-{{ $user->id }}" class="flex flex-col gap-1 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="font-semibold text-zinc-900">{{ $user->name }}</p>
                    <p class="truncate text-sm text-zinc-600"><a href="mailto:{{ $user->email }}" class="hover:underline">{{ $user->email }}</a></p>
                    @if ($player)
                        <p class="text-sm text-zinc-700">{{ __('Zawodnik') }}: {{ $player->full_name }}@if ($player->team) · {{ $player->team->name }}@endif</p>
                    @endif
                    @foreach ($children as $child)
                        <p class="text-sm text-zinc-700">{{ __('Rodzic') }}: {{ $child->full_name }}@if ($child->team) · {{ $child->team->name }}@endif</p>
                    @endforeach
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2 text-xs">
                    <span class="text-zinc-500">{{ $user->rolesLabel() }}</span>
                    <span class="inline-flex rounded-md px-2 py-1 font-medium ring-1 ring-inset {{ $status->badge() }}">{{ __($status->label()) }}</span>
                </div>
            </li>
        @empty
            <li class="px-4 py-6 text-center text-sm text-zinc-500">{{ __('Brak osób do pokazania.') }}</li>
        @endforelse
    </ul>

    {{ $users->links() }}
</div>

@foreach($adminNavigation as $group => $items)
    @php $visibleItems = array_filter($items, fn (array $item) => ! isset($item['can']) || ($item['can'] instanceof \Closure ? ($item['can'])() : auth()->user()->can($item['can']))); @endphp
    @continue(empty($visibleItems))
    @php
        $groupActive = collect($visibleItems)->contains(fn (array $item) => request()->routeIs(...(array) ($item['active'] ?? (str_ends_with($item['route'], '.index') ? \Illuminate\Support\Str::beforeLast($item['route'], '.').'.*' : $item['route']))));
        $groupKey = \Illuminate\Support\Str::slug($group);
    @endphp
    {{-- Grupa zwija się jak akordeon; otwarta zostaje grupa bieżącej strony i te, które ktoś sam rozwinął. --}}
    <div x-data="{ key: 'admin-nav-{{ $groupKey }}', open: {{ $groupActive || $loop->first ? 'true' : 'false' }} }"
         x-init="if (! {{ $groupActive ? 'true' : 'false' }}) { try { const saved = localStorage.getItem(key); if (saved !== null) open = saved === '1'; } catch (e) {} }"
         class="mt-3 first:mt-0">
    <button type="button" @click="open = ! open; try { localStorage.setItem(key, open ? '1' : '0') } catch (e) {}" :aria-expanded="open"
        class="flex w-full items-center justify-between rounded-md px-3 py-1.5 text-[11px] font-semibold tracking-wider text-zinc-500 uppercase hover:text-zinc-300">
        {{ $group }}
        <x-admin-icon name="chevron" class="size-3.5 transition-transform" ::class="open ? 'rotate-90' : ''" />
    </button>
    <ul class="mt-1 space-y-0.5" x-show="open" x-collapse.duration.150ms>
        @foreach($visibleItems as $item)
            @php
                $activePattern = $item['active'] ?? (str_ends_with($item['route'], '.index') ? \Illuminate\Support\Str::beforeLast($item['route'], '.').'.*' : $item['route']);
                $isActive = request()->routeIs(...(array) $activePattern);
                $badge = isset($item['badge']) ? value($item['badge']) : null;
            @endphp
            <li>
                <a href="{{ route($item['route']) }}" @if($isActive) aria-current="page" @endif @class([
                    'group relative flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-medium transition',
                    'bg-white/[0.08] text-white' => $isActive,
                    'text-zinc-400 hover:bg-white/[0.04] hover:text-zinc-100' => ! $isActive,
                ])>
                    @if($isActive)<span class="absolute inset-y-1.5 left-0 w-0.5 rounded-full bg-sky-400" aria-hidden="true"></span>@endif
                    <x-admin-icon :name="$item['icon']" @class(['size-[18px]', 'text-sky-400' => $isActive, 'text-zinc-500 group-hover:text-zinc-300' => ! $isActive]) />
                    <span class="truncate">{{ $item['label'] }}</span>
                    @if(! empty($badge))
                        <span class="ml-auto rounded-full bg-amber-400/90 px-2 py-0.5 text-[11px] font-bold text-zinc-900" title="Oczekuje na zatwierdzenie">{{ $badge }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
    </div>
@endforeach

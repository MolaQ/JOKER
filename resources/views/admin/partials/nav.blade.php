@foreach($adminNavigation as $group => $items)
    <p class="mt-4 mb-1 px-3 text-[11px] font-semibold tracking-wider text-zinc-500 uppercase first:mt-0">{{ $group }}</p>
    @foreach($items as $item)
        @php $activePattern = str_ends_with($item['route'], '.index') ? \Illuminate\Support\Str::beforeLast($item['route'], '.').'.*' : $item['route']; @endphp
        <a href="{{ route($item['route']) }}" @class([
            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition',
            'bg-sky-500 text-white font-semibold' => request()->routeIs($activePattern),
            'text-zinc-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs($activePattern),
        ])>
            <span aria-hidden="true">{{ $item['icon'] }}</span> {{ $item['label'] }}
        </a>
    @endforeach
@endforeach

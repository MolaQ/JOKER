@props(['segmented' => false])
{{-- Przełącznik języka: polska i brytyjska flaga, bieżący język wyróżniony; segmented = pigułka jak przełącznik motywu (menu na telefonie). --}}
@if($segmented)
    <div role="group" aria-label="{{ __('Język strony') }}" {{ $attributes->class(['inline-flex rounded-full bg-white/[0.06] p-0.5 text-xs font-semibold ring-1 ring-white/10']) }}>
        @foreach(['pl' => ['pl', 'Polski', 'PL'], 'en' => ['gb', 'English', 'EN']] as $locale => [$country, $name, $code])
            @php
                $isCurrent = app()->getLocale() === $locale;
            @endphp
            <a href="{{ route('locale.switch', $locale) }}" hreflang="{{ $locale }}" lang="{{ $locale }}" title="{{ $name }}" @if($isCurrent) aria-current="true" @endif rel="nofollow" @class([
                'flex min-h-9 flex-1 items-center justify-center gap-1.5 rounded-full px-3 transition',
                'bg-white/10 text-white' => $isCurrent,
                'text-zinc-400 hover:text-white' => ! $isCurrent,
            ])>
                <x-flag :country="$country" class="h-3 w-4 rounded-[2px] ring-1 ring-black/20" />
                {{ $code }}
            </a>
        @endforeach
    </div>
@else
<div {{ $attributes->merge(['class' => 'flex items-center gap-1']) }} role="group" aria-label="{{ __('Język strony') }}">
    @foreach(['pl' => ['pl', 'Polski'], 'en' => ['gb', 'English']] as $locale => [$country, $name])
        @php
            $isCurrent = app()->getLocale() === $locale;
        @endphp
        <a href="{{ route('locale.switch', $locale) }}" hreflang="{{ $locale }}" lang="{{ $locale }}" title="{{ $name }}" aria-label="{{ $name }}" @if($isCurrent) aria-current="true" @endif rel="nofollow" @class([
            'flex h-9 w-9 items-center justify-center rounded-full transition',
            'bg-white/15 ring-2 ring-sky-club' => $isCurrent,
            'opacity-60 hover:bg-white/10 hover:opacity-100' => ! $isCurrent,
        ])>
            <x-flag :country="$country" class="h-3.5 w-5 rounded-[2px] shadow-sm ring-1 ring-black/20" />
        </a>
    @endforeach
</div>
@endif

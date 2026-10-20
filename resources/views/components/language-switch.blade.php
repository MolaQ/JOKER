{{-- Przełącznik języka: polska i brytyjska flaga, bieżący język wyróżniony --}}
<div {{ $attributes->merge(['class' => 'flex items-center gap-1']) }} role="group" aria-label="{{ __('Język strony') }}">
    @foreach(['pl' => ['pl', 'Polski'], 'en' => ['gb', 'English']] as $locale => [$country, $name])
        @php($isCurrent = app()->getLocale() === $locale)
        <a href="{{ route('locale.switch', $locale) }}" hreflang="{{ $locale }}" lang="{{ $locale }}" title="{{ $name }}" aria-label="{{ $name }}" @if($isCurrent) aria-current="true" @endif rel="nofollow" @class([
            'flex h-9 w-9 items-center justify-center rounded-full transition',
            'bg-white/15 ring-2 ring-sky-club' => $isCurrent,
            'opacity-60 hover:bg-white/10 hover:opacity-100' => ! $isCurrent,
        ])>
            <x-flag :country="$country" class="h-3.5 w-5 rounded-[2px] shadow-sm ring-1 ring-black/20" />
        </a>
    @endforeach
</div>

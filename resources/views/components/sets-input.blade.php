@props([
    'model',
    'home' => '',
    'away' => '',
    'live' => false,
])
{{--
    Sety na telefonie: dwa pola liczbowe na każdy set zamiast tekstu z dwukropkami (klawiatura liczbowa nie ma „:”).
    Po dwóch cyfrach kursor sam przechodzi dalej. Do Livewire trafia tekst „25:20, 23:25”, ten sam co z pola tekstowego.
    W trwającym meczu (live) ostatni wpisany set, który nie ma jeszcze 25 pkt i 2 pkt przewagi, jest oznaczony „trwa”
    (tak samo liczy TournamentMatch::currentSet()).
--}}
<div {{ $attributes->merge(['class' => 'space-y-2']) }}
    x-data="{
        raw: $wire.$entangle(@js($model)),
        sets: [],
        text() {
            return this.sets.filter((set) => set[0] !== '' || set[1] !== '').map((set) => set[0] + ':' + set[1]).join(', ');
        },
        load(value) {
            if (value === this.text() && this.sets.length) return;
            const pairs = String(value || '').split(/[,;\s]+/).filter(Boolean).map((chunk) => chunk.split(/[:\-./]/));
            this.sets = pairs.map((pair) => [pair[0] ?? '', pair[1] ?? '']);
            while (this.sets.length < 3) this.sets.push(['', '']);
        },
        live: @js((bool) $live),
        open(index) {
            if (!this.live) return false;
            const filled = this.sets.map((set, position) => set[0] !== '' && set[1] !== '' ? position : -1).filter((position) => position >= 0);
            if (!filled.length || filled[filled.length - 1] !== index) return false;
            const [home, away] = this.sets[index].map(Number);
            return !(Math.max(home, away) >= 25 && Math.abs(home - away) >= 2);
        },
        sync() {
            this.raw = this.text();
        },
        next(event) {
            if (event.target.value.length < 2) return;
            const fields = [...$root.querySelectorAll('input')];
            fields[fields.indexOf(event.target) + 1]?.focus();
        },
    }"
    x-init="load(raw); $watch('raw', (value) => load(value))">
    <div class="grid grid-cols-[3rem_1fr_0.75rem_1fr] items-end gap-x-2 text-xs font-medium text-zinc-500">
        <span></span>
        <span class="truncate text-center">{{ $home }}</span>
        <span></span>
        <span class="truncate text-center">{{ $away }}</span>
    </div>
    <template x-for="(set, index) in sets" :key="index">
        <div class="grid grid-cols-[3rem_1fr_0.75rem_1fr] items-center gap-x-2">
            <span class="text-sm leading-tight font-semibold" :class="open(index) ? 'text-red-600' : 'text-zinc-600'">
                <span x-text="'{{ __('Set') }} ' + (index + 1)"></span>
                <span x-show="open(index)" x-cloak class="block text-[10px] font-bold tracking-wide uppercase">● {{ __('trwa') }}</span>
            </span>
            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="2" enterkeyhint="next" autocomplete="off"
                x-model="set[0]" x-on:input="set[0] = $event.target.value.replace(/\D/g, ''); sync(); next($event)"
                :aria-label="'{{ __('Set') }} ' + (index + 1) + ': ' + @js($home)"
                class="block w-full rounded-lg border border-zinc-300 text-center text-lg font-semibold tabular-nums">
            <span class="text-center font-bold text-zinc-400">:</span>
            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="2" enterkeyhint="next" autocomplete="off"
                x-model="set[1]" x-on:input="set[1] = $event.target.value.replace(/\D/g, ''); sync(); next($event)"
                :aria-label="'{{ __('Set') }} ' + (index + 1) + ': ' + @js($away)"
                class="block w-full rounded-lg border border-zinc-300 text-center text-lg font-semibold tabular-nums">
        </div>
    </template>
    <button type="button" x-show="sets.length < 5" x-on:click="sets.push(['', ''])" class="text-sm font-semibold text-sky-club-dark">+ {{ __('Dodaj set') }}</button>
</div>

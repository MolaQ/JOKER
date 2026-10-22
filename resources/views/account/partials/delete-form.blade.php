<form method="POST" action="{{ route('account.destroy') }}" class="mt-3 space-y-3" x-data>
    @csrf
    @method('DELETE')
    <input type="hidden" name="reason" value="{{ $reason }}">
    <p class="text-zinc-600">{{ __('Usuniemy imię i nazwisko, e-mail, hasło i dane profilu oraz odłączymy konto od zawodników i drużyn. Komentarze zostaną podpisane „Konto usunięte”. Tego nie da się cofnąć.') }}</p>
    <div>
        <label for="delete-password-{{ $reason }}" class="block text-sm font-medium text-zinc-700">{{ __('Hasło (dla potwierdzenia)') }}</label>
        <input type="password" id="delete-password-{{ $reason }}" name="password" required autocomplete="current-password" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2">
        @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <button type="submit" x-on:click="if (! confirm(@js(__('Na pewno usunąć konto? Tego nie da się cofnąć.')))) $event.preventDefault()" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('Usuń konto i moje dane') }}</button>
</form>

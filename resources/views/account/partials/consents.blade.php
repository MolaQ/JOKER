{{-- Zgody wymagane do prowadzenia konta (rejestracja i strona zgody). --}}
<label class="flex items-start gap-2 text-sm text-zinc-700">
    <input type="checkbox" name="privacy" value="1" class="mt-0.5 rounded border-zinc-300 text-sky-club-dark" @checked(old('privacy'))>
    <span>{{ __('Wyrażam zgodę na przetwarzanie moich danych osobowych (imię i nazwisko, e-mail, dane podane w profilu) przez :club w celu prowadzenia konta w serwisie klubu.', ['club' => $site['club_legal_name']]) }} <span class="text-red-600">*</span></span>
</label>
@error('privacy')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

<label class="flex items-start gap-2 text-sm text-zinc-700">
    <input type="checkbox" name="policy" value="1" class="mt-0.5 rounded border-zinc-300 text-sky-club-dark" @checked(old('policy'))>
    <span>{!! __('Zapoznałem(-am) się z :link: wiem, że mogę w każdej chwili cofnąć zgodę, pobrać swoje dane albo usunąć konto w „Mój profil”.', ['link' => '<a href="'.route('pages.show', 'polityka-prywatnosci').'" target="_blank" class="font-semibold text-sky-club-dark underline">'.e(__('polityką prywatności')).'</a>']) !!} <span class="text-red-600">*</span></span>
</label>
@error('policy')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

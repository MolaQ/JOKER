@extends('layouts.public')

@section('title', __('Mój profil'))

@section('robots', 'noindex, follow')

@section('content')
<div class="container mx-auto max-w-3xl px-4 py-12">
    <h1 class="mb-6 text-3xl font-bold text-zinc-900">{{ __('Mój profil') }}</h1>

    <div class="mb-6 rounded-xl bg-white p-6 shadow">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('Konto') }}</h2>
        @if(! $user->hasVerifiedEmail())
            <div class="mt-3 rounded-lg bg-amber-50 p-4 text-sm text-amber-900">
                {{ __('Potwierdź adres e-mail: kliknij link, który wysłaliśmy na :email.', ['email' => $user->email]) }}
                <form method="POST" action="{{ route('verification.send') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="font-semibold underline">{{ __('Wyślij link jeszcze raz') }}</button>
                </form>
            </div>
        @endif
        @php $status = $user->accountStatus(); @endphp
        @if($status === \App\AccountStatus::Active)
            <p class="mt-3 text-sm text-green-700">✓ {{ __('Konto aktywne') }}</p>
        @elseif($status === \App\AccountStatus::Disabled)
            <div class="mt-3 rounded-lg bg-zinc-50 p-4 text-sm text-zinc-700">
                <p class="font-semibold text-zinc-900">{{ __('Konto wyłączone przez Ciebie') }} ({{ $user->disabled_at->format('d.m.Y') }})</p>
                <p class="mt-1">{{ __('Dane są zachowane, ale konto nic nie może: nie komentuje, nie widzi dokumentów dla członków i nie ma dostępu do panelu.') }}</p>
                <form method="POST" action="{{ route('account.enable') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="rounded-lg bg-ink px-4 py-2 font-semibold text-white hover:bg-graphite">{{ __('Włącz konto ponownie') }}</button>
                </form>
            </div>
        @elseif($status === \App\AccountStatus::Rejected)
            <div class="mt-3 rounded-lg bg-red-50 p-4 text-sm text-red-900">
                <p class="font-semibold">{{ __('Klub odrzucił prośbę o aktywację konta') }} ({{ $user->rejected_at->format('d.m.Y') }})</p>
                @if($user->rejection_reason)
                    <p class="mt-1">{{ __('Powód') }}: „{{ $user->rejection_reason }}”</p>
                @endif
                <p class="mt-2 text-red-800">{{ __('Popraw dane poniżej i wyślij prośbę jeszcze raz.') }}</p>
            </div>
            <form method="POST" action="{{ route('account.resubmit') }}" class="mt-4 space-y-4" x-data="{ kind: @js(old('kind', $user->requested_role?->value ?? 'fan')) }">
                @csrf
                <div>
                    <label for="resubmit-name" class="block text-sm font-medium text-zinc-700">{{ __('Imię i nazwisko') }}</label>
                    <input type="text" id="resubmit-name" name="name" value="{{ old('name', $user->name) }}" required class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="resubmit-kind" class="block text-sm font-medium text-zinc-700">{{ __('Kim jesteś?') }}</label>
                    <select id="resubmit-kind" name="kind" x-model="kind" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2">
                        @foreach(['fan' => __('Kibicem'), 'parent' => __('Rodzicem zawodnika'), 'player' => __('Zawodnikiem'), 'trainer' => __('Trenerem lub w sztabie')] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="kind !== 'fan'" x-cloak>
                    <label for="resubmit-team" class="block text-sm font-medium text-zinc-700">{{ __('Drużyna') }}</label>
                    <select id="resubmit-team" name="team_id" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2">
                        <option value="">{{ __('Wybierz drużynę') }}</option>
                        @foreach($allTeams as $team)
                            <option value="{{ $team->id }}" @selected(old('team_id', $user->requested_team_id) == $team->id)>{{ $team->name }}</option>
                        @endforeach
                    </select>
                    @error('team_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div x-show="kind !== 'fan'" x-cloak>
                    <label for="resubmit-note" class="block text-sm font-medium text-zinc-700">
                        <span x-show="kind === 'parent'">{{ __('Imię i nazwisko dziecka (lub dzieci)') }}</span>
                        <span x-show="kind !== 'parent'">{{ __('Wiadomość do klubu: coś, co pomoże nas przekonać') }}</span>
                    </label>
                    <textarea id="resubmit-note" name="note" rows="2" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2">{{ old('note', $user->registration_note) }}</textarea>
                    @error('note')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm font-semibold text-white hover:bg-graphite">{{ __('Wyślij prośbę ponownie') }}</button>
            </form>
        @else
            <p class="mt-3 rounded-lg bg-zinc-50 p-4 text-sm text-zinc-700">
                @if($status === \App\AccountStatus::Suspended)
                    {{ __('Konto jest nieaktywne. Jeśli to pomyłka, napisz do klubu.') }}
                @elseif($user->requested_role === \App\UserRole::Fan)
                    {{ __('Konto kibica zacznie działać po potwierdzeniu adresu e-mail.') }}
                @else
                    {{ __('Konto czeka na aktywację przez klub. Trener lub administrator powiąże je z profilem (:role) i dostaniesz e-mail.', ['role' => __($user->requested_role?->label() ?? 'Kibic')]) }}
                @endif
            </p>
        @endif
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach($user->roles() as $role)
                <span class="rounded-full bg-sky-club/10 px-3 py-1 text-sm font-medium text-sky-club-dark">{{ __($role->label()) }}</span>
            @endforeach
        </div>
        @if($teams->isNotEmpty())
            <p class="mt-3 text-sm text-zinc-600">{{ __('Drużyny') }}: {{ $teams->pluck('name')->join(', ') }}</p>
        @endif
        @if($user->canAccessPanel())
            <a href="{{ route('admin.dashboard') }}" class="mt-4 inline-flex rounded-lg bg-ink px-4 py-2 text-sm font-semibold text-white hover:bg-graphite">{{ __('Panel klubu') }} →</a>
        @endif
    </div>

    @if($players->isNotEmpty())
        <div class="mb-6 rounded-xl bg-white p-6 shadow">
            <h2 class="text-lg font-semibold text-zinc-900">{{ __('Profile zawodników') }}</h2>
            <p class="mt-1 text-sm text-zinc-500">{{ __('Na stronie zawodnika kliknij „Zaproponuj zmiany”, aby wysłać nowe zdjęcie lub dane do zatwierdzenia.') }}</p>
            <ul class="mt-4 divide-y divide-zinc-100">
                @foreach($players as $player)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <span><span class="font-semibold">{{ $player->full_name }}</span> <span class="text-sm text-zinc-500">{{ $player->team?->name }}</span></span>
                        <a href="{{ route('players.show', $player) }}" class="text-sm font-semibold text-sky-club-dark hover:underline">{{ __('Otwórz profil') }} →</a>
                        <form method="POST" action="{{ route('profile.consent', $player) }}" class="w-full text-sm" x-data>
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="image_consent" value="0">
                            <label class="flex items-center gap-2 text-zinc-700">
                                <input type="checkbox" name="image_consent" value="1" @checked($player->image_consent) x-on:change="$el.form.submit()" class="rounded border-zinc-300 text-sky-club-dark">
                                {{ __('Zgoda na oznaczanie imieniem i nazwiskiem na zdjęciach') }}
                            </label>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($canPropose)
        <h2 id="zaproponuj" class="mb-3 text-xl font-bold text-zinc-900">{{ __('Zaproponuj treść') }}</h2>
        <p class="mb-3 text-sm text-zinc-600">{{ __('Relacja z meczu, zdjęcia albo link do transmisji. Po zatwierdzeniu przez klub pojawią się na stronie.') }}</p>
        <div class="mb-6">@livewire('content-proposal-form')</div>
    @endif

    @if($contentProposals->isNotEmpty())
        <div class="mb-6 rounded-xl bg-white p-6 shadow">
            <h2 class="text-lg font-semibold text-zinc-900">{{ __('Moje propozycje treści') }}</h2>
            <ul class="mt-3 divide-y divide-zinc-100 text-sm">
                @foreach($contentProposals as $proposal)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                        <span>{{ __($proposal->typeLabel()) }}@if($proposal->title): {{ $proposal->title }}@endif <span class="text-zinc-500">{{ $proposal->created_at->format('d.m.Y') }}</span></span>
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs font-semibold',
                            'bg-amber-100 text-amber-800' => $proposal->status === 'pending',
                            'bg-green-100 text-green-800' => $proposal->status === 'approved',
                            'bg-red-100 text-red-800' => $proposal->status === 'rejected',
                        ])>{{ ['pending' => __('czeka'), 'approved' => __('zatwierdzona'), 'rejected' => __('odrzucona')][$proposal->status] ?? $proposal->status }}</span>
                        @if($proposal->review_note)<span class="w-full text-zinc-500">{{ $proposal->review_note }}</span>@endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($proposals->isNotEmpty())
        <div class="mb-6 rounded-xl bg-white p-6 shadow">
            <h2 class="text-lg font-semibold text-zinc-900">{{ __('Moje propozycje zmian w profilach') }}</h2>
            <ul class="mt-3 divide-y divide-zinc-100 text-sm">
                @foreach($proposals as $proposal)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                        <span>{{ $proposal->isPhoto() ? __('Zdjęcie') : __('Dane') }}: {{ $proposal->player?->full_name }} <span class="text-zinc-500">{{ $proposal->created_at->format('d.m.Y') }}</span></span>
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs font-semibold',
                            'bg-amber-100 text-amber-800' => $proposal->status === 'pending',
                            'bg-green-100 text-green-800' => $proposal->status === 'approved',
                            'bg-red-100 text-red-800' => $proposal->status === 'rejected',
                        ])>{{ ['pending' => __('czeka'), 'approved' => __('zatwierdzona'), 'rejected' => __('odrzucona')][$proposal->status] ?? $proposal->status }}</span>
                        @if($proposal->review_note)<span class="w-full text-zinc-500">{{ $proposal->review_note }}</span>@endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 rounded-xl bg-white p-6 shadow">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('Zmiana hasła') }}</h2>
        <form method="POST" action="{{ route('profile.password') }}" class="mt-4 grid gap-4 sm:grid-cols-3">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('Obecne hasło') }}</label>
                <input type="password" name="current_password" autocomplete="current-password" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 shadow-sm">
                @error('current_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('Nowe hasło') }}</label>
                <input type="password" name="password" autocomplete="new-password" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 shadow-sm">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('Powtórz hasło') }}</label>
                <input type="password" name="password_confirmation" autocomplete="new-password" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 shadow-sm">
            </div>
            <div class="sm:col-span-3">
                <button type="submit" class="rounded-lg bg-ink px-5 py-2 text-white hover:bg-graphite">{{ __('Zmień hasło') }}</button>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white p-6 shadow">
        <h2 class="mb-4 text-lg font-semibold text-zinc-900">{{ __('Dane konta') }}</h2>
        <form method="POST" action="{{ route('profile.request-change') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('Imię i nazwisko') }}</label>
                @if($user->nameIsLocked())
                    <input type="text" value="{{ $user->name }}" disabled class="mt-1 block w-full cursor-not-allowed rounded-md border border-zinc-200 bg-zinc-100 px-3 py-2 text-zinc-600">
                    <p class="mt-1 text-xs text-zinc-500">🔒 {{ __('Konto jest zatwierdzone przez klub, więc imię i nazwisko zmienia tylko klub. W razie błędu napisz do nas.') }}</p>
                @else
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700">Instagram</label>
                <input type="text" name="instagram" value="{{ old('instagram', $user->instagram) }}" placeholder="https://instagram.com/..." class="mt-1 block w-full rounded-md border border-zinc-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('Dodatkowe informacje') }}</label>
                <x-rich-text-editor name="additional_info" :value="old('additional_info', (string) $user->additional_info)" placeholder="{{ __('Opisz zmiany, które chcesz wprowadzić...') }}" height="min-h-[180px]" />
            </div>

            <button type="submit" class="rounded-lg bg-ink px-5 py-2 text-white hover:bg-graphite">{{ __('Wyślij propozycję zmian') }}</button>
        </form>

        @if($latestRequest)
            <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-sm">
                {{ __('Ostatni wniosek') }}: <strong>{{ __($latestRequest->status) }}</strong>
                @if($latestRequest->review_note)
                    <div class="mt-1 text-zinc-600">{{ __('Notatka') }}: {{ $latestRequest->review_note }}</div>
                @endif
            </div>
        @endif
    </div>

    <div class="mt-6 rounded-xl bg-white p-6 shadow" id="prywatnosc">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('Prywatność i konto') }}</h2>
        <p class="mt-2 text-sm text-zinc-600">
            @if($user->privacy_consent_at)
                {{ __('Zgoda na przetwarzanie danych wyrażona :date.', ['date' => $user->privacy_consent_at->format('d.m.Y')]) }}
            @endif
            <a href="{{ route('pages.show', 'polityka-prywatnosci') }}" class="font-semibold text-sky-club-dark underline">{{ __('Polityka prywatności') }}</a>
        </p>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <a href="{{ route('account.export') }}" class="rounded-lg p-4 text-sm ring-1 ring-zinc-200 hover:bg-zinc-50">
                <span class="font-semibold text-zinc-900">⬇️ {{ __('Pobierz moje dane') }}</span>
                <span class="mt-1 block text-zinc-500">{{ __('Plik z danymi, które klub ma o Tobie w serwisie.') }}</span>
            </a>
            @if(! $user->disabled_at)
                <details class="rounded-lg p-4 text-sm ring-1 ring-zinc-200">
                    <summary class="cursor-pointer font-semibold text-zinc-900">⏸️ {{ __('Wyłącz konto na jakiś czas') }}</summary>
                    <form method="POST" action="{{ route('account.disable') }}" class="mt-3 space-y-2">
                        @csrf
                        <p class="text-zinc-500">{{ __('Dane zostają, a konto przestaje działać. Włączysz je tutaj w każdej chwili, bez ponownej aktywacji.') }}</p>
                        <input type="password" name="password" required autocomplete="current-password" placeholder="{{ __('Hasło') }}" class="block w-full rounded-md border border-zinc-300 px-3 py-2">
                        <button type="submit" class="rounded-lg bg-zinc-800 px-3 py-1.5 font-semibold text-white hover:bg-zinc-700">{{ __('Wyłącz konto') }}</button>
                    </form>
                </details>
            @endif
        </div>

        <details class="mt-3 rounded-lg p-4 text-sm ring-1 ring-red-200">
            <summary class="cursor-pointer font-semibold text-red-700">🗑️ {{ __('Cofnij zgodę i usuń konto') }}</summary>
            @include('account.partials.delete-form', ['reason' => 'consent_withdrawn'])
        </details>
    </div>
</div>
@endsection

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\RequestedGroup;
use App\UserRole;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

/**
 * Zakładanie konta. Konto kibica działa po potwierdzeniu e-maila, a konto rodzica,
 * zawodnika i trenera dopiero po aktywacji przez klub i powiązaniu z profilem.
 */
class RegisterController extends Controller
{
    /** Kim może się przedstawić osoba zakładająca konto. */
    public const KINDS = ['fan', 'parent', 'player', 'trainer'];

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        // Pole niewidoczne dla ludzi: wypełniają je tylko roboty.
        if (filled($request->input('website'))) {
            return redirect()->route('home');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'kind' => ['required', Rule::in(self::KINDS)],
            'team_id' => ['nullable', 'required_unless:kind,fan', RequestedGroup::rule()],
            'note' => 'nullable|required_if:kind,parent|string|max:1000',
            'privacy' => 'accepted',
            'policy' => 'accepted',
            'age' => 'accepted_if:kind,player',
        ], [
            'privacy.accepted' => __('Bez tej zgody nie możemy prowadzić konta.'),
            'policy.accepted' => __('Potwierdź, że znasz zasady przetwarzania danych.'),
            'age.accepted_if' => __('Osoba poniżej 16 lat potrzebuje zgody rodzica lub opiekuna.'),
            'team_id.required_unless' => __('Wybierz drużynę lub grupę, z którą jesteś związany.'),
            'note.required_if' => __('Napisz imię i nazwisko dziecka.'),
        ]);

        [$team, $miniGroup] = RequestedGroup::resolve($validated['team_id'] ?? null);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_active' => false,
            'requested_role' => UserRole::from($validated['kind']),
            'requested_team_id' => $validated['kind'] === 'fan' ? null : $team,
            'requested_mini_group_id' => $validated['kind'] === 'fan' ? null : $miniGroup,
            'registration_note' => $validated['note'] ?? null,
            'privacy_consent_at' => now(),
        ]);

        // Gdy poczta na serwerze nie działa, konto i tak zostaje założone: bez tego rodzic widział stronę błędu
        // i zakładał drugie konto. Błąd trafia do logu (Ustawienia systemu → Poczta).
        try {
            event(new Registered($user));
            $mailed = true;
        } catch (Throwable $exception) {
            Log::error('Nie udało się wysłać e-maila: VerifyEmail', ['to' => $user->email, 'error' => $exception->getMessage()]);
            $mailed = false;
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('profile.edit')->with('success', $mailed
            ? __('Konto założone. Kliknij link, który wysłaliśmy na Twój e-mail, aby potwierdzić adres.')
            : __('Konto założone. Nie udało się teraz wysłać e-maila z potwierdzeniem, ale nie musisz zakładać konta drugi raz: klub aktywuje je ręcznie.'));
    }
}

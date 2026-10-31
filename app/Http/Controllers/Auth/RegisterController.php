<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\UserRole;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

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
        return view('auth.register', [
            'teams' => Team::orderBy('display_order')->get(['id', 'name']),
        ]);
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
            'team_id' => ['nullable', 'required_unless:kind,fan', 'exists:teams,id'],
            'note' => 'nullable|required_if:kind,parent|string|max:1000',
            'privacy' => 'accepted',
            'policy' => 'accepted',
            'age' => 'accepted_if:kind,player',
        ], [
            'privacy.accepted' => __('Bez tej zgody nie możemy prowadzić konta.'),
            'policy.accepted' => __('Potwierdź, że znasz zasady przetwarzania danych.'),
            'age.accepted_if' => __('Osoba poniżej 16 lat potrzebuje zgody rodzica lub opiekuna.'),
            'team_id.required_unless' => __('Wybierz drużynę, z którą jesteś związany.'),
            'note.required_if' => __('Napisz imię i nazwisko dziecka.'),
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_active' => false,
            'requested_role' => UserRole::from($validated['kind']),
            'requested_team_id' => $validated['kind'] === 'fan' ? null : $validated['team_id'],
            'registration_note' => $validated['note'] ?? null,
            'privacy_consent_at' => now(),
        ]);

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('profile.edit')->with('success', __('Konto założone. Kliknij link, który wysłaliśmy na Twój e-mail, aby potwierdzić adres.'));
    }
}

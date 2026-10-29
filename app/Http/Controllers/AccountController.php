<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\RegisterController;
use App\Models\AuditLog;
use App\Support\SiteSettings;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Stan konta po stronie użytkownika: zgoda RODO, ponowna prośba o aktywację po odrzuceniu,
 * wyłączenie i włączenie konta, pobranie swoich danych i usunięcie konta (cofnięcie zgody).
 */
class AccountController extends Controller
{
    public function consent(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasPrivacyConsent()) {
            return redirect()->route('profile.edit');
        }

        return view('account.consent');
    }

    public function storeConsent(Request $request): RedirectResponse
    {
        $request->validate(['privacy' => 'accepted', 'policy' => 'accepted'], [
            'privacy.accepted' => __('Bez tej zgody nie możemy prowadzić konta.'),
            'policy.accepted' => __('Potwierdź, że znasz zasady przetwarzania danych.'),
        ]);

        $user = $request->user();
        $user->forceFill(['privacy_consent_at' => now()])->save();
        AuditLog::record('consent.privacy', 'Zgoda na przetwarzanie danych konta: '.$user->name, $user);

        return redirect()->intended(route('profile.edit'))->with('success', __('Dziękujemy. Zgoda zapisana.'));
    }

    /**
     * Po odrzuceniu: użytkownik poprawia dane (imię i nazwisko, rola, drużyna, wiadomość) i prosi jeszcze raz.
     */
    public function resubmit(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->rejected_at !== null && ! $user->is_active, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'kind' => ['required', Rule::in(RegisterController::KINDS)],
            'team_id' => ['nullable', 'required_unless:kind,fan', 'exists:teams,id'],
            'note' => 'nullable|required_if:kind,parent|string|max:1000',
        ], [
            'team_id.required_unless' => __('Wybierz drużynę, z którą jesteś związany.'),
            'note.required_if' => __('Napisz imię i nazwisko dziecka.'),
        ]);

        $user->resubmitActivation([
            'name' => $validated['name'],
            'requested_role' => UserRole::from($validated['kind']),
            'requested_team_id' => $validated['kind'] === 'fan' ? null : $validated['team_id'],
            'registration_note' => $validated['note'] ?? null,
        ]);

        return back()->with('success', __('Prośba wysłana ponownie. Klub ją sprawdzi i dostaniesz e-mail.'));
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['password' => 'required|current_password'], [
            'password.current_password' => __('Hasło jest nieprawidłowe.'),
        ]);

        $request->user()->disableByUser();

        return back()->with('success', __('Konto wyłączone. Możesz je włączyć w każdej chwili na tej stronie.'));
    }

    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->disabled_at !== null, 403);

        $user->enableByUser();

        return back()->with('success', $user->is_active
            ? __('Konto znów działa.')
            : __('Konto włączone. Czeka na aktywację przez klub.'));
    }

    /**
     * Kopia danych konta (RODO: prawo dostępu i przenoszenia danych).
     */
    public function export(Request $request): JsonResponse
    {
        $user = $request->user()->load(['children', 'playerProfile', 'profileChangeRequests']);

        $data = [
            'konto' => $user->only(['name', 'email', 'instagram', 'additional_info', 'birth_date', 'created_at', 'email_verified_at', 'activated_at', 'privacy_consent_at']),
            'stan_konta' => $user->accountStatus()->label(),
            'role' => $user->roles()->map(fn (UserRole $role) => $role->label())->values(),
            'profil_zawodnika' => $user->playerProfile?->only(['first_name', 'last_name', 'birth_date', 'position', 'jersey_number']),
            'dzieci' => $user->children->map->only(['first_name', 'last_name'])->values(),
            'prosby_o_zmiane_danych' => $user->profileChangeRequests->map->only(['proposed_data', 'status', 'created_at'])->values(),
        ];

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="moje-dane-'.Str::slug(app(SiteSettings::class)['club_name']).'.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Cofnięcie zgody albo usunięcie konta: dane osobowe znikają, konto przestaje działać.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|current_password',
            'reason' => ['required', Rule::in(['consent_withdrawn', 'user_request'])],
        ], [
            'password.current_password' => __('Hasło jest nieprawidłowe.'),
        ]);

        $user = $request->user();
        $user->anonymize($request->string('reason')->toString());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', __('Konto zostało usunięte razem z danymi osobowymi.'));
    }
}

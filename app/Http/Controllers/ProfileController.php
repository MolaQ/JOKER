<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ContentProposal;
use App\Models\Player;
use App\Models\PlayerChangeRequest;
use App\Models\ProfileChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        $latestRequest = ProfileChangeRequest::where('user_id', $user->id)
            ->latest('id')
            ->first();

        // Profile zawodnicze, którymi użytkownik może się opiekować (własny albo dzieci).
        $players = Player::with('team')
            ->where('user_id', $user->id)
            ->orWhereHas('guardians', fn ($query) => $query->whereKey($user->id))
            ->orderBy('last_name')
            ->get();

        // Ostatnie propozycje wysłane przez użytkownika i ich status.
        $proposals = PlayerChangeRequest::with('player')
            ->where('submitted_by', $user->id)
            ->latest('id')
            ->limit(10)
            ->get();

        return view('profile.edit', [
            'user' => $user,
            'latestRequest' => $latestRequest,
            'players' => $players,
            'proposals' => $proposals,
            'contentProposals' => ContentProposal::where('user_id', $user->id)->latest('id')->limit(10)->get(),
            'canPropose' => ContentProposal::typesFor($user) !== [],
            'teams' => $user->allCoachedTeams(),
        ]);
    }

    /**
     * Rodzic (albo pełnoletni zawodnik) udziela lub wycofuje zgodę na oznaczanie na zdjęciach.
     */
    public function updateConsent(Request $request, Player $player): RedirectResponse
    {
        $user = $request->user();
        abort_unless($player->user_id === $user->id || $player->guardians()->whereKey($user->id)->exists(), 403);

        $consent = $request->boolean('image_consent');
        $player->update(['image_consent' => $consent, 'image_consent_changed_at' => now()]);
        AuditLog::record('consent.image', ($consent ? 'Udzielono' : 'Wycofano').' zgody na wizerunek: '.$player->full_name, $player);

        return back()->with('success', $consent
            ? __('Zgoda zapisana. Oznaczenia na zdjęciach są widoczne.')
            : __('Zgoda wycofana. Oznaczenia na zdjęciach są ukryte.'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => __('Obecne hasło jest nieprawidłowe.'),
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return back()->with('success', __('Hasło zostało zmienione.'));
    }

    public function requestChange(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'instagram' => 'nullable|string|max:255',
            'additional_info' => 'nullable|string|max:2000',
        ]);

        ProfileChangeRequest::create([
            'user_id' => $user->id,
            'proposed_data' => $validated,
            'status' => 'pending',
        ]);

        return back()->with('success', __('Propozycja zmian została wysłana do akceptacji trenera/admina.'));
    }
}

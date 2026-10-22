<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\UserRole;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Potwierdzenie adresu z linku w e-mailu. Kibic może od razu dawać serduszka i komentować.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();
        $user = $request->user();

        if ($user->requested_role === UserRole::Fan && ! $user->is_active) {
            $user->activate(UserRole::Fan);

            return redirect()->route('profile.edit')->with('success', __('Adres potwierdzony. Konto kibica jest aktywne.'));
        }

        return redirect()->route('profile.edit')->with('success', $user->is_active
            ? __('Adres e-mail potwierdzony.')
            : __('Adres potwierdzony. Konto czeka teraz na aktywację przez klub.'));
    }

    public function send(Request $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('success', __('Wysłaliśmy nowy link potwierdzający.'));
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\UserRole;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmailVerificationController extends Controller
{
    /**
     * Potwierdzenie adresu z linku w e-mailu. Kibic może od razu dawać serduszka i komentować.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();
        $user = $request->user();

        if ($user->requested_role === UserRole::Fan && ! $user->is_active && ! $user->disabled_at && ! $user->rejected_at) {
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
            try {
                $request->user()->sendEmailVerificationNotification();
            } catch (Throwable $exception) {
                Log::error('Nie udało się wysłać e-maila: VerifyEmail', ['to' => $request->user()->email, 'error' => $exception->getMessage()]);

                return back()->with('error', __('Nie udało się teraz wysłać e-maila. Spróbuj później; konto i tak aktywuje klub.'));
            }
        }

        return back()->with('success', __('Wysłaliśmy nowy link potwierdzający.'));
    }
}

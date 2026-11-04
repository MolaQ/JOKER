<?php

namespace App\Http\Controllers;

use App\AccountStatus;
use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use App\Support\SiteSettings;
use App\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ContactController extends Controller
{
    public function index(SiteSettings $site)
    {
        // Administrator strony: do niego piszą osoby, które znalazły błąd albo mają pomysł na stronę.
        $chosen = collect(preg_split('/[\s,;]+/', (string) $site['contact_site_admin']))->filter()->map(fn ($email) => mb_strtolower($email));
        $siteAdmins = $site['contact_show_site_admin']
            ? User::withAccountStatus(AccountStatus::Active)
                ->when($chosen->isNotEmpty(), fn ($query) => $query->whereIn(DB::raw('lower(email)'), $chosen->all()), fn ($query) => $query->withRole(UserRole::Admin))
                ->orderBy('name')->get()
            : collect();

        return view('contact.index', ['siteAdmins' => $siteAdmins]);
    }

    public function send(Request $request, SiteSettings $site)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        // Pole-pułapka: ludzie go nie widzą, wypełniają je tylko roboty. Udajemy sukces, nic nie zapisujemy.
        if (filled($request->input('website'))) {
            return redirect()->route('contact.index')->with('contact_sent', true);
        }

        $contact = ContactMessage::create($validated + ['user_id' => Auth::id()]);

        if ($site['contact_forward_email'] && filter_var($site['contact_email'], FILTER_VALIDATE_EMAIL)) {
            try {
                Notification::route('mail', $site['contact_email'])->notify(new ContactMessageReceived($contact));
            } catch (Throwable $exception) {
                // Wiadomość i tak czeka w panelu; brak poczty na serwerze nie może zgubić zgłoszenia.
                Log::warning('Formularz kontaktowy: nie udało się wysłać kopii e-mailem', ['error' => $exception->getMessage()]);
            }
        }

        return redirect()->route('contact.index')->with('contact_sent', true);
    }
}

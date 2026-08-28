<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function index()
    {
        return view('contact.index');
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        // TODO: Implementacja wysyłania maila
        // Mail::to('kontakt@jokerpila.pl')->send(new ContactMail($validated));

        return redirect()->back()->with('success', 'Wiadomość została wysłana!');
    }
}

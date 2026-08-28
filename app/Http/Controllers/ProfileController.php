<?php

namespace App\Http\Controllers;

use App\Models\ProfileChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        $latestRequest = ProfileChangeRequest::where('user_id', $user->id)
            ->latest('id')
            ->first();

        return view('profile.edit', compact('user', 'latestRequest'));
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

        return back()->with('success', 'Propozycja zmian została wysłana do akceptacji trenera/admina.');
    }
}

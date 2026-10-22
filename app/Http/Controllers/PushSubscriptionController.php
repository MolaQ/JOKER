<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\PushNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Zapisanie i usunięcie zgody przeglądarki na powiadomienia push.
 */
class PushSubscriptionController extends Controller
{
    public function key(PushNotifier $push): JsonResponse
    {
        abort_unless($push->enabled(), 404);

        return response()->json(['publicKey' => $push->publicKey()]);
    }

    public function store(Request $request, PushNotifier $push): JsonResponse
    {
        abort_unless($push->enabled(), 404);

        $data = $request->validate([
            'endpoint' => ['required', 'url:https', 'max:1000'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'in:aes128gcm,aesgcm'],
        ]);

        PushSubscription::updateOrCreate(['endpoint_hash' => PushSubscription::hashEndpoint($data['endpoint'])], [
            'endpoint' => $data['endpoint'],
            'public_key' => $data['keys']['p256dh'],
            'auth_token' => $data['keys']['auth'],
            'content_encoding' => $data['contentEncoding'] ?? 'aes128gcm',
            'user_id' => $request->user()?->id,
        ]);

        return response()->json(['ok' => true], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $endpoint = (string) $request->validate(['endpoint' => ['required', 'string', 'max:1000']])['endpoint'];
        PushSubscription::where('endpoint_hash', PushSubscription::hashEndpoint($endpoint))->delete();

        return response()->json(['ok' => true]);
    }
}

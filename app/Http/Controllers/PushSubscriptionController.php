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

    /**
     * Wybór kibica w okienku dzwonka: tematy, drużyny klubu i obserwowane drużyny turniejowe.
     * Bez pól tylko zwraca zapisany wybór; null w polu = wszystko.
     */
    public function preferences(Request $request, PushNotifier $push): JsonResponse
    {
        abort_unless($push->enabled(), 404);

        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:1000'],
            'topics' => ['nullable', 'array'],
            'topics.*' => ['string', 'in:'.implode(',', array_keys(PushSubscription::TOPICS))],
            'teams' => ['nullable', 'array', 'max:50'],
            'teams.*' => ['integer'],
            'participants' => ['nullable', 'array', 'max:50'],
            'participants.*' => ['integer'],
        ]);

        $subscription = PushSubscription::where('endpoint_hash', PushSubscription::hashEndpoint($data['endpoint']))->firstOrFail();
        $changes = [];

        foreach (['topics', 'teams', 'participants'] as $field) {
            if ($request->exists($field)) {
                $changes[$field] = $data[$field] === null ? null : array_values(array_unique($field === 'topics' ? $data[$field] : array_map('intval', $data[$field])));
            }
        }

        if ($changes !== []) {
            $subscription->update($changes);
        }

        return response()->json($subscription->only(['topics', 'teams', 'participants']));
    }

    public function destroy(Request $request): JsonResponse
    {
        $endpoint = (string) $request->validate(['endpoint' => ['required', 'string', 'max:1000']])['endpoint'];
        PushSubscription::where('endpoint_hash', PushSubscription::hashEndpoint($endpoint))->delete();

        return response()->json(['ok' => true]);
    }
}

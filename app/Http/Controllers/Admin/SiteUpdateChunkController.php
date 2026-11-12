<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SiteUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Przyjmuje paczkę aktualizacji strony w kawałkach (panel: Aktualizacja strony).
 */
class SiteUpdateChunkController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'upload' => 'required|string',
            'index' => 'required|integer|min:0',
            'total' => 'required|integer|min:1|max:2000',
            'chunk' => 'required|file|max:'.(int) ceil(SiteUpdate::CHUNK_BYTES / 1024 * 2),
        ]);

        try {
            $package = SiteUpdate::receiveChunk($data['upload'], (int) $data['index'], (int) $data['total'], $request->file('chunk'), $request->user());
        } catch (RuntimeException $error) {
            return response()->json(['message' => $error->getMessage()], 422);
        }

        return response()->json(['done' => $package !== null]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Support\SearchIndex;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        $results = $query === '' ? [] : SearchIndex::search($query, user: $request->user());

        return view('search.index', [
            'query' => $query,
            // Grupy w kolejności najlepszego trafienia.
            'results' => collect($results)->groupBy('type'),
            'total' => count($results),
        ]);
    }

    /**
     * Indeks dla wyszukiwarki w przeglądarce. ETag pozwala pobrać go ponownie tylko po zmianie treści.
     */
    public function data(Request $request): JsonResponse
    {
        $entries = SearchIndex::entriesFor($request->user());
        $etag = '"'.md5(serialize($entries)).'"';

        if ($request->headers->get('If-None-Match') === $etag) {
            return response()->json(null, 304)->setEtag(trim($etag, '"'));
        }

        $response = response()->json($entries)->setEtag(trim($etag, '"'))->setMaxAge(60);

        // Z osobami ze społeczności: tylko w przeglądarce zalogowanego, nigdy we wspólnej pamięci podręcznej.
        return $request->user() ? $response->setPrivate() : $response->setPublic();
    }
}

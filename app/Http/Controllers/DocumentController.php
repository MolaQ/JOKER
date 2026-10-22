<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $order = array_flip(array_keys(Document::CATEGORIES));

        $documents = Document::query()
            ->when(! $user?->is_active, fn ($query) => $query->public())
            ->orderBy('title')
            ->get()
            ->sortBy(fn (Document $document) => $order[$document->category] ?? count($order))
            ->groupBy('category');

        return view('documents.index', [
            'documents' => $documents,
            'hiddenCount' => $user?->is_active ? 0 : Document::where('is_public', false)->count(),
        ]);
    }

    /**
     * Pobranie przez stronę klubu: liczy pobrania i pilnuje dokumentów tylko dla zalogowanych.
     */
    public function download(Request $request, Document $document): StreamedResponse
    {
        abort_unless($document->isAvailableTo($request->user()), $request->user() ? 403 : 401);
        abort_unless(Storage::disk('public')->exists($document->file_path), 404);

        $document->increment('download_count');

        return Storage::disk('public')->download($document->file_path, Str::slug($document->title).'.'.$document->file_type);
    }
}

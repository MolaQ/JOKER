<?php

namespace App\Http\Controllers;

use App\Models\Document;

class DocumentController extends Controller
{
    public function index()
    {
        $documents = Document::public()
            ->orderBy('category')
            ->orderBy('title')
            ->get()
            ->groupBy('category');

        return view('documents.index', compact('documents'));
    }
}

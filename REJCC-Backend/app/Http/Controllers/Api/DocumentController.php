<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $documents = Document::orderBy('category')->orderBy('title')
            ->get(['id', 'title', 'description', 'category', 'url', 'size', 'created_at']);

        return response()->json(['ok' => true, 'documents' => $documents]);
    }
}

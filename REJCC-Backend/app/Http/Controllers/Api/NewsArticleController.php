<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;

class NewsArticleController extends Controller
{
    public function index()
    {
        return response()->json(['ok' => true, 'articles' => NewsArticle::orderBy('published_at', 'desc')->get()]);
    }

    public function show(string $slug)
    {
        $article = NewsArticle::where('slug', $slug)->first();

        if (! $article) {
            return response()->json(['ok' => false, 'message' => 'Article introuvable.'], 404);
        }

        return response()->json(['ok' => true, 'article' => $article]);
    }
}

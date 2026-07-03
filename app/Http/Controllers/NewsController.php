<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;

class NewsController extends Controller
{
    public function index()
    {
        return view('pages.actualites.index');
    }

    public function show(string $slug)
    {
        $article = NewsArticle::where('slug', $slug)->firstOrFail();

        return view('pages.actualites.show', ['article' => $article]);
    }
}

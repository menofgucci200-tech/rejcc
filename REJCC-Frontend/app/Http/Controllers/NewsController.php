<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Carbon\Carbon;

class NewsController extends Controller
{
    public function index()
    {
        return view('pages.actualites.index');
    }

    public function show(string $slug)
    {
        $result = Api::get("/news/{$slug}");

        if (! ($result['ok'] ?? false)) {
            abort(404);
        }

        $article = (object) $result['article'];
        $article->published_at = Carbon::parse($article->published_at);

        return view('pages.actualites.show', ['article' => $article]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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

    // ------------------------------------------------------------------
    // Administration
    // ------------------------------------------------------------------

    public function adminIndex()
    {
        return response()->json(['ok' => true, 'articles' => NewsArticle::orderByDesc('published_at')->get()]);
    }

    public function store(Request $request)
    {
        return $this->persist($request, new NewsArticle());
    }

    public function update(Request $request, int $id)
    {
        $article = NewsArticle::find($id);
        if (! $article) {
            return response()->json(['ok' => false, 'message' => 'Article introuvable.'], 404);
        }

        return $this->persist($request, $article);
    }

    public function destroy(int $id)
    {
        NewsArticle::where('id', $id)->delete();

        return response()->json(['ok' => true]);
    }

    private function persist(Request $request, NewsArticle $article)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:2|max:180',
            'category' => 'required|string|min:2|max:60',
            'excerpt' => 'required|string|min:10|max:300',
            'body' => 'required|string|min:20|max:20000',
            'author' => 'nullable|string|max:120',
            'published_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $data = $validator->validated();

        // Le corps arrive en texte brut : un paragraphe par bloc séparé
        // d'une ligne vide (format attendu par les vues de la vitrine).
        $paragraphs = array_values(array_filter(array_map(
            'trim',
            preg_split('/\R{2,}/u', $data['body'])
        )));
        $data['body'] = $paragraphs;

        $words = str_word_count(strip_tags(implode(' ', $paragraphs)));
        $data['reading_time'] = max(1, (int) ceil($words / 200)).' min';

        $data['author'] = $data['author'] ?? 'La rédaction REJCC';
        $data['published_at'] = $data['published_at'] ?? now();

        if (! $article->exists) {
            $base = Str::slug($data['title']) ?: 'article';
            $slug = $base;
            for ($i = 2; NewsArticle::where('slug', $slug)->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }
            $data['slug'] = $slug;
        }

        $article->fill($data)->save();

        return response()->json(['ok' => true, 'article' => $article]);
    }
}

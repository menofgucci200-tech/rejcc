<?php

namespace App\Http\Controllers;

use App\Support\Content\LegalPages;

class LegalPageController extends Controller
{
    public function __invoke(string $slug)
    {
        $page = LegalPages::find($slug);
        abort_unless($page, 404);

        return view('pages.legal', [
            'page' => $page,
            'rendu' => $page['publie'] ? LegalPages::rendu($page['contenu']) : null,
            'autres' => array_values(array_filter(LegalPages::all(), fn ($p) => $p['slug'] !== $slug)),
        ]);
    }
}

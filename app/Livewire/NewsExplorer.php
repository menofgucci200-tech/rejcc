<?php

namespace App\Livewire;

use App\Models\NewsArticle;
use Livewire\Component;

class NewsExplorer extends Component
{
    public string $category = 'Toutes';

    public string $query = '';

    public function render()
    {
        $q = trim(mb_strtolower($this->query));

        $articles = NewsArticle::query()
            ->when($this->category !== 'Toutes', fn ($qr) => $qr->where('category', $this->category))
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w->whereRaw('LOWER(title) LIKE ?', ["%{$q}%"])->orWhereRaw('LOWER(excerpt) LIKE ?', ["%{$q}%"])))
            ->orderByDesc('published_at')
            ->get();

        $categories = NewsArticle::query()->distinct()->orderBy('category')->pluck('category');

        return view('livewire.news-explorer', [
            'articles' => $articles,
            'categories' => ['Toutes', ...$categories],
        ]);
    }
}

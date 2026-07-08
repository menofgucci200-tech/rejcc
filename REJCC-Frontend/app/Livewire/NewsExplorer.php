<?php

namespace App\Livewire;

use App\Support\Api;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class NewsExplorer extends Component
{
    public string $category = 'Toutes';

    public string $query = '';

    public function render()
    {
        $all = Collection::make(Api::get('/news')['articles'] ?? [])
            ->map(function (array $a) {
                $a['published_at'] = Carbon::parse($a['published_at']);

                return (object) $a;
            });

        $q = trim(mb_strtolower($this->query));

        $articles = $all
            ->when($this->category !== 'Toutes', fn ($c) => $c->where('category', $this->category))
            ->when($q !== '', fn ($c) => $c->filter(
                fn ($a) => str_contains(mb_strtolower($a->title), $q) || str_contains(mb_strtolower($a->excerpt), $q)
            ))
            ->values();

        $categories = $all->pluck('category')->unique()->sort()->values();

        return view('livewire.news-explorer', [
            'articles' => $articles,
            'categories' => ['Toutes', ...$categories],
        ]);
    }
}

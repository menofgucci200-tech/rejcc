<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsArticle extends Model
{
    protected $fillable = ['slug', 'category', 'title', 'excerpt', 'body', 'author', 'reading_time', 'published_at'];

    protected $casts = [
        'body' => 'array',
        'published_at' => 'datetime',
    ];
}

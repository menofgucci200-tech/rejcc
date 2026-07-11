<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    protected $fillable = ['title', 'type', 'description', 'url', 'size', 'downloads', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }
}

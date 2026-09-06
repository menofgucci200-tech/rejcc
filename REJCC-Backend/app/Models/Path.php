<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Path extends Model
{
    protected $fillable = ['title', 'slug', 'description', 'objectif', 'badge_icon', 'badge_couleur', 'ordre', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function formations(): BelongsToMany
    {
        return $this->belongsToMany(Formation::class, 'path_formation')->withPivot('ordre')->orderByPivot('ordre');
    }
}

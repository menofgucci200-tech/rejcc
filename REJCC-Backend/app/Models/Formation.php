<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Formation extends Model
{
    protected $fillable = [
        'title', 'category', 'description', 'duration', 'level',
        'is_free', 'is_certifying', 'modules_count', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_free' => 'boolean',
            'is_certifying' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(FormationEnrollment::class);
    }
}

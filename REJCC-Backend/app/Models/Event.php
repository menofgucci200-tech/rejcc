<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = ['title', 'slug', 'description', 'excerpt', 'body', 'location', 'category', 'starts_at', 'time_label', 'image', 'capacity'];

    protected $casts = ['starts_at' => 'datetime', 'body' => 'array'];

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }
}

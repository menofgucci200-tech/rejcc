<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    protected $fillable = ['title', 'description', 'type', 'entreprise', 'site_url', 'lieu', 'contact', 'deadline', 'author_id', 'media_url', 'media_name'];

    protected $casts = ['deadline' => 'date'];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}

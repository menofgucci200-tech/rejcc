<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    protected $fillable = ['title', 'description', 'type', 'contact', 'deadline', 'author_id'];

    protected $casts = ['deadline' => 'date'];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}

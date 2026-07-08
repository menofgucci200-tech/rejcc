<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    protected $fillable = ['icon', 'title', 'blurb', 'items', 'ordre'];

    protected $casts = ['items' => 'array'];
}

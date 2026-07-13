<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageSection extends Model
{
    protected $fillable = ['page', 'section', 'content', 'visible'];

    protected $casts = ['content' => 'json', 'visible' => 'boolean'];
}

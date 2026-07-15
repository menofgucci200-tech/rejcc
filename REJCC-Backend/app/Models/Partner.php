<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $fillable = ['name', 'initials', 'sector', 'logo', 'site_url', 'ordre'];
}

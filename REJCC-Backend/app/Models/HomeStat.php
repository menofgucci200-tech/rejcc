<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeStat extends Model
{
    protected $fillable = ['label', 'value', 'suffix', 'ordre'];
}

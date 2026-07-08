<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipStep extends Model
{
    protected $fillable = ['icon', 'title', 'text', 'ordre'];
}

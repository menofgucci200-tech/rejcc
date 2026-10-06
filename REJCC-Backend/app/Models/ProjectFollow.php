<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectFollow extends Model
{
    protected $fillable = ['project_id', 'user_id'];
}

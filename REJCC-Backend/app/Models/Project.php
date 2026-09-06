<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    protected $fillable = ['user_id', 'title', 'description', 'members_count', 'status'];

    public function porteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

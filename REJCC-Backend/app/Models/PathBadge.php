<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PathBadge extends Model
{
    protected $fillable = ['user_id', 'path_id', 'obtenu_at'];

    protected function casts(): array
    {
        return ['obtenu_at' => 'datetime'];
    }

    public function path(): BelongsTo
    {
        return $this->belongsTo(Path::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

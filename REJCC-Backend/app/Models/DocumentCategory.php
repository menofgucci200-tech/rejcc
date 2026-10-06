<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentCategory extends Model
{
    protected $fillable = ['nom', 'icone', 'ordre'];

    public function documents()
    {
        return $this->hasMany(Document::class, 'category_id');
    }
}

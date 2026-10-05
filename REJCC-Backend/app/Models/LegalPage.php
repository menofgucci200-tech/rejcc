<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalPage extends Model
{
    protected $fillable = ['slug', 'titre', 'resume', 'contenu', 'publie', 'version', 'publie_at', 'ordre'];

    protected function casts(): array
    {
        return ['publie' => 'boolean', 'publie_at' => 'datetime'];
    }
}

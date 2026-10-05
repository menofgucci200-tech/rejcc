<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalPage extends Model
{
    protected $fillable = ['slug', 'titre', 'resume', 'contenu', 'publie', 'version', 'publie_at', 'ordre'];

    /** Documents acceptés à l'adhésion. */
    public const A_ACCEPTER = ['cgu', 'politique-de-confidentialite', 'charte-du-membre'];

    /** Versions en vigueur des documents à accepter (null si pas encore publiés). */
    public static function versionsEnVigueur(): array
    {
        $pages = static::whereIn('slug', self::A_ACCEPTER)->get()->keyBy('slug');

        return collect(self::A_ACCEPTER)->mapWithKeys(fn ($s) => [$s => ($pages[$s]->publie ?? false) ? $pages[$s]->version : null])->all();
    }

    protected function casts(): array
    {
        return ['publie' => 'boolean', 'publie_at' => 'datetime'];
    }
}

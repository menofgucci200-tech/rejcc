<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Clics d'un lien de suivi pour une journée (un simple compteur). */
class LienSuiviClic extends Model
{
    protected $table = 'liens_suivi_clics';

    public $timestamps = false;

    protected $fillable = ['lien_id', 'jour', 'clics'];

    // `jour` reste une chaîne AAAA-MM-JJ : comparaisons et regroupements identiques en MySQL et SQLite.
    protected $casts = ['clics' => 'integer'];
}

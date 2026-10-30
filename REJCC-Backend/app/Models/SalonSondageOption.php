<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Choix proposé dans un sondage de salon. */
class SalonSondageOption extends Model
{
    protected $fillable = ['salon_sondage_id', 'libelle', 'ordre'];
}
